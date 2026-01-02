<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'type',
    'started_at',
    'expired_at',
  ];

  protected $casts = [
    'started_at' => 'datetime',
    'expired_at' => 'datetime',
  ];

  /**
   * Get the user that owns the membership
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
