<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
  use HasFactory;

  protected $fillable = [
    'conversation_id',
    'sender_id',
    'message',
    'type',
    'file_path',
    'read_at',
  ];

  protected $casts = [
    'read_at' => 'datetime',
  ];

  /**
   * Get the conversation that owns the message
   */
  public function conversation()
  {
    return $this->belongsTo(Conversation::class);
  }

  /**
   * Get the sender of the message
   */
  public function sender()
  {
    return $this->belongsTo(User::class, 'sender_id');
  }

  /**
   * Mark message as read
   */
  public function markAsRead()
  {
    if (!$this->read_at) {
      $this->update(['read_at' => now()]);
    }
  }

  /**
   * Check if message is read
   */
  public function isRead()
  {
    return $this->read_at !== null;
  }

  /**
   * Scope for unread messages
   */
  public function scopeUnread($query)
  {
    return $query->whereNull('read_at');
  }
}
