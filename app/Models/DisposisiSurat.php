<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisposisiSurat extends Model
{
    use HasFactory;

    protected $table = 'disposisi_surat';

    protected $fillable = [
        'id_surat_masuk',
        'dari_user_id',
        'kepada_user_id',
        'instruksi',
        'catatan',
        'status',
        'dibaca_pada',
        'selesai_pada',
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
        'selesai_pada' => 'datetime',
    ];

    /**
     * Get the surat masuk
     */
    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class, 'id_surat_masuk');
    }

    /**
     * Get the user who sent the disposition
     */
    public function dariUser()
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    /**
     * Get the user who receives the disposition
     */
    public function kepadaUser()
    {
        return $this->belongsTo(User::class, 'kepada_user_id');
    }

    /**
     * Mark disposition as read
     */
    public function markAsRead()
    {
        $this->update([
            'status' => 'dibaca',
            'dibaca_pada' => now(),
        ]);
    }

    /**
     * Mark disposition as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'selesai',
            'selesai_pada' => now(),
        ]);
    }

    /**
     * Scope untuk filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk user specific dispositions
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('kepada_user_id', $userId);
    }
}
