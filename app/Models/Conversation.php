<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_one_id',
    'user_two_id',
    'last_message_at',
  ];

  protected $casts = [
    'last_message_at' => 'datetime',
  ];

  /**
   * Get user one
   */
  public function userOne()
  {
    return $this->belongsTo(User::class, 'user_one_id');
  }

  /**
   * Get user two
   */
  public function userTwo()
  {
    return $this->belongsTo(User::class, 'user_two_id');
  }

  /**
   * Get all messages for this conversation
   */
  public function messages()
  {
    return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
  }

  /**
   * Get the latest message
   */
  public function latestMessage()
  {
    return $this->hasOne(Message::class)->latest();
  }

  /**
   * Get or create a conversation between two users
   */
  public static function findOrCreateBetween($userOneId, $userTwoId)
  {
    // Pastikan user_one_id selalu lebih kecil untuk konsistensi
    $ids = [$userOneId, $userTwoId];
    sort($ids);

    return self::firstOrCreate(
      ['user_one_id' => $ids[0], 'user_two_id' => $ids[1]]
    );
  }

  /**
   * Check if user is part of conversation
   */
  public function hasParticipant($userId)
  {
    return $this->user_one_id == $userId || $this->user_two_id == $userId;
  }

  /**
   * Get the other participant
   */
  public function getOtherParticipant($userId)
  {
    return $this->user_one_id == $userId ? $this->userTwo : $this->userOne;
  }

  /**
   * Get unread messages count for a user
   */
  public function unreadCountFor($userId)
  {
    return $this->messages()
      ->where('sender_id', '!=', $userId)
      ->whereNull('read_at')
      ->count();
  }

  /**
   * Scope to get conversations for a specific user
   */
  public function scopeForUser($query, $userId)
  {
    return $query->where('user_one_id', $userId)
      ->orWhere('user_two_id', $userId);
  }
}
