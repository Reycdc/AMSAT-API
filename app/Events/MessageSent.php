<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $message;

  /**
   * Create a new event instance.
   */
  public function __construct(Message $message)
  {
    $this->message = $message->load('sender:id,username,thumbnail');
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return array<int, \Illuminate\Broadcasting\Channel>
   */
  public function broadcastOn(): array
  {
    return [
      new Channel('conversation.' . $this->message->conversation_id),
    ];
  }

  /**
   * The event's broadcast name.
   */
  public function broadcastAs(): string
  {
    return 'message.sent';
  }

  /**
   * Get the data to broadcast.
   *
   * @return array<string, mixed>
   */
  public function broadcastWith(): array
  {
    return [
      'id' => $this->message->id,
      'conversation_id' => $this->message->conversation_id,
      'sender_id' => $this->message->sender_id,
      'sender' => [
        'id' => $this->message->sender->id,
        'username' => $this->message->sender->username,
        'thumbnail' => $this->message->sender->thumbnail,
      ],
      'message' => $this->message->message,
      'type' => $this->message->type,
      'file_path' => $this->message->file_path,
      'read_at' => $this->message->read_at,
      'created_at' => $this->message->created_at->toISOString(),
    ];
  }
}
