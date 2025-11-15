<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the contents for the category
     */
    public function contents()
    {
        return $this->belongsToMany(Content::class, 'content_categories', 'categories_id', 'content_id');
    }
}