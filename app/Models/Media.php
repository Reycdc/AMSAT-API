<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'image',
        'document',
        'link',
        'video',
    ];

    /**
     * Get the content that owns the media
     */
    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
