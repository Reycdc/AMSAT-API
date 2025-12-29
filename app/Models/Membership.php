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
    ];

    /**
     * Get the user that owns the membership
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
