<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\UserTyping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pusher\Pusher;

class ChatController extends Controller
{
  /**
   * Get all conversations for the authenticated user
   */
  public function conversations(Request $request)
  {
    try {
      $userId = Auth::id();

      $conversations = Conversation::forUser($userId)
        ->with(['userOne:id,username,thumbnail', 'userTwo:id,username,thumbnail', 'latestMessage'])
        ->orderBy('last_message_at', 'desc')
        ->get()
        ->map(function ($conversation) use ($userId) {
          $otherUser = $conversation->getOtherParticipant($userId);
          return [
            'id' => $conversation->id,
            'user' => [
              'id' => $otherUser->id,
              'username' => $otherUser->username,
              'thumbnail' => $otherUser->thumbnail,
            ],
            'last_message' => $conversation->latestMessage ? [
              'message' => $conversation->latestMessage->message,
              'created_at' => $conversation->latestMessage->created_at,
              'sender_id' => $conversation->latestMessage->sender_id,
            ] : null,
            'unread_count' => $conversation->unreadCountFor($userId),
            'updated_at' => $conversation->last_message_at ?? $conversation->created_at,
          ];
        });

      return response()->json([
        'success' => true,
        'data' => $conversations,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to fetch conversations',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get or create a conversation with a user
   */
  public function startConversation(Request $request)
  {
    try {
      $request->validate([
        'user_id' => 'required|exists:users,id',
      ]);

      $authUserId = Auth::id();
      $targetUserId = $request->user_id;

      if ($authUserId == $targetUserId) {
        return response()->json([
          'success' => false,
          'message' => 'Cannot start conversation with yourself',
        ], 400);
      }

      $conversation = Conversation::findOrCreateBetween($authUserId, $targetUserId);

      $otherUser = $conversation->getOtherParticipant($authUserId);

      return response()->json([
        'success' => true,
        'data' => [
          'id' => $conversation->id,
          'user' => [
            'id' => $otherUser->id,
            'username' => $otherUser->username,
            'thumbnail' => $otherUser->thumbnail,
          ],
        ],
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to start conversation',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get messages for a conversation
   */
  public function messages(Request $request, $conversationId)
  {
    try {
      $conversation = Conversation::findOrFail($conversationId);

      if (!$conversation->hasParticipant(Auth::id())) {
        return response()->json([
          'success' => false,
          'message' => 'Unauthorized',
        ], 403);
      }

      $messages = $conversation->messages()
        ->with('sender:id,username,thumbnail')
        ->orderBy('created_at', 'asc')
        ->paginate(50);

      // Mark messages as read
      Message::where('conversation_id', $conversationId)
        ->where('sender_id', '!=', Auth::id())
        ->whereNull('read_at')
        ->update(['read_at' => now()]);

      return response()->json([
        'success' => true,
        'data' => $messages,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to fetch messages',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Send a message
   */
  public function sendMessage(Request $request, $conversationId)
  {
    try {
      $request->validate([
        'message' => 'required|string|max:2000',
        'type' => 'in:text,image,file',
      ]);

      $conversation = Conversation::findOrFail($conversationId);

      if (!$conversation->hasParticipant(Auth::id())) {
        return response()->json([
          'success' => false,
          'message' => 'Unauthorized',
        ], 403);
      }

      $message = Message::create([
        'conversation_id' => $conversationId,
        'sender_id' => Auth::id(),
        'message' => $request->message,
        'type' => $request->type ?? 'text',
      ]);

      // Update last_message_at
      $conversation->update(['last_message_at' => now()]);

      // Load sender relation
      $message->load('sender:id,username,thumbnail');

      // Broadcast the message via Pusher
      $this->broadcastMessage($message);

      return response()->json([
        'success' => true,
        'data' => [
          'id' => $message->id,
          'conversation_id' => $message->conversation_id,
          'sender_id' => $message->sender_id,
          'sender' => [
            'id' => $message->sender->id,
            'username' => $message->sender->username,
            'thumbnail' => $message->sender->thumbnail,
          ],
          'message' => $message->message,
          'type' => $message->type,
          'created_at' => $message->created_at,
        ],
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to send message',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Broadcast message via Pusher
   */
  private function broadcastMessage(Message $message)
  {
    try {
      $pusher = new Pusher(
        env('PUSHER_APP_KEY'),
        env('PUSHER_APP_SECRET'),
        env('PUSHER_APP_ID'),
        [
          'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
          'useTLS' => true,
        ]
      );

      $pusher->trigger(
        'conversation-' . $message->conversation_id,
        'message-sent',
        [
          'id' => $message->id,
          'conversation_id' => $message->conversation_id,
          'sender_id' => $message->sender_id,
          'sender' => [
            'id' => $message->sender->id,
            'username' => $message->sender->username,
            'thumbnail' => $message->sender->thumbnail,
          ],
          'message' => $message->message,
          'type' => $message->type,
          'file_path' => $message->file_path,
          'read_at' => $message->read_at,
          'created_at' => $message->created_at->toISOString(),
        ]
      );
    } catch (\Exception $e) {
      \Log::error('Pusher broadcast failed: ' . $e->getMessage());
    }
  }

  /**
   * Typing indicator
   */
  public function typing(Request $request, $conversationId)
  {
    try {
      $conversation = Conversation::findOrFail($conversationId);

      if (!$conversation->hasParticipant(Auth::id())) {
        return response()->json([
          'success' => false,
          'message' => 'Unauthorized',
        ], 403);
      }

      $user = Auth::user();

      // Broadcast typing event
      $pusher = new Pusher(
        env('PUSHER_APP_KEY'),
        env('PUSHER_APP_SECRET'),
        env('PUSHER_APP_ID'),
        [
          'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
          'useTLS' => true,
        ]
      );

      $pusher->trigger(
        'conversation-' . $conversationId,
        'user-typing',
        [
          'user_id' => $user->id,
          'username' => $user->username,
          'is_typing' => $request->is_typing ?? true,
        ]
      );

      return response()->json([
        'success' => true,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to send typing indicator',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get list of users to chat with
   */
  public function getUsers(Request $request)
  {
    try {
      $search = $request->query('search');

      $users = User::select('id', 'username', 'thumbnail')
        ->where('id', '!=', Auth::id())
        ->when($search, function ($query) use ($search) {
          $query->where('username', 'like', '%' . $search . '%');
        })
        ->limit(20)
        ->get();

      return response()->json([
        'success' => true,
        'data' => $users,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to fetch users',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Mark messages as read
   */
  public function markAsRead(Request $request, $conversationId)
  {
    try {
      $conversation = Conversation::findOrFail($conversationId);

      if (!$conversation->hasParticipant(Auth::id())) {
        return response()->json([
          'success' => false,
          'message' => 'Unauthorized',
        ], 403);
      }

      $updated = Message::where('conversation_id', $conversationId)
        ->where('sender_id', '!=', Auth::id())
        ->whereNull('read_at')
        ->update(['read_at' => now()]);

      return response()->json([
        'success' => true,
        'updated' => $updated,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to mark as read',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}
