<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $conversationId;
  public $userId;
  public $username;
  public $isTyping;

  /**
   * Create a new event instance.
   */
  public function __construct(int $conversationId, int $userId, string $username, bool $isTyping = true)
  {
    $this->conversationId = $conversationId;
    $this->userId = $userId;
    $this->username = $username;
    $this->isTyping = $isTyping;
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return array<int, \Illuminate\Broadcasting\Channel>
   */
  public function broadcastOn(): array
  {
    return [
      new Channel('conversation.' . $this->conversationId),
    ];
  }

  /**
   * The event's broadcast name.
   */
  public function broadcastAs(): string
  {
    return 'user.typing';
  }

  /**
   * Get the data to broadcast.
   *
   * @return array<string, mixed>
   */
  public function broadcastWith(): array
  {
    return [
      'user_id' => $this->userId,
      'username' => $this->username,
      'is_typing' => $this->isTyping,
    ];
  }
}
