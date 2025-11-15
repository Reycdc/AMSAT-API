<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'urutan',
        'title',
        'parent_id',
    ];

    /**
     * Get the parent menu
     */
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Get the child menus
     */
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('urutan');
    }

    /**
     * Get the contents for the menu
     */
    public function contents()
    {
        return $this->hasMany(Content::class);
    }
}