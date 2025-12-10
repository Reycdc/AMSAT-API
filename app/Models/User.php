<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The guard name for Spatie permissions
     *
     * @var string
     */
    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'jenis_kelamin',
        'thumbnail',
        'alamat',
        'is_verified',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Relationships
     */

    // Content yang dibuat oleh user
    public function contents()
    {
        return $this->hasMany(\App\Models\Content::class, 'user_id');
    }

    // Content yang diverifikasi oleh user (sebagai redaktur)
    public function verifiedContents()
    {
        return $this->hasMany(\App\Models\Content::class, 'redaktur_id');
    }

    // Comments
    public function comments()
    {
        return $this->hasMany(\App\Models\Comment::class);
    }

    // Likes
    public function likes()
    {
        return $this->hasMany(\App\Models\Like::class);
    }

    // Membership
    public function membership()
    {
        return $this->hasOne(\App\Models\Membership::class);
    }
}