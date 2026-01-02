<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $table = 'content';

    protected $fillable = [
        'user_id',
        'menu_id',
        'title',
        'isi',
        'has_read',
        'cover',
        'date',
        'status',
        'redaktur_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * Get the user that owns the content
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the redaktur that verified the content
     */
    public function redaktur()
    {
        return $this->belongsTo(User::class, 'redaktur_id');
    }

    /**
     * Get the menu that the content belongs to
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the categories for the content
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'content_categories', 'content_id', 'categories_id');
    }

    /**
     * Get the media for the content
     */
    public function media()
    {
        return $this->hasMany(Media::class);
    }

    /**
     * Get the comments for the content
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'news_id');
    }

    /**
     * Get the likes for the content
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}