<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratMasuk extends Model
{
    use HasFactory;

    protected $table = 'surat_masuk';

    protected $fillable = [
        'user_id',
        'nomor_surat',
        'tanggal_surat',
        'pengirim',
        'perihal',
        'isi',
        'file_surat',
        'status',
        'prioritas',
        'catatan',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    /**
     * Get the user who received the surat masuk
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dispositions for the surat masuk
     */
    public function disposisi()
    {
        return $this->hasMany(DisposisiSurat::class, 'id_surat_masuk');
    }

    /**
     * Get the latest disposition
     */
    public function latestDisposisi()
    {
        return $this->hasOne(DisposisiSurat::class, 'id_surat_masuk')->latest();
    }

    /**
     * Scope untuk filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter by prioritas
     */
    public function scopeByPrioritas($query, $prioritas)
    {
        return $query->where('prioritas', $prioritas);
    }

    /**
     * Scope untuk filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    /**
     * Check if surat can be dispositioned
     */
    public function canBeDispositioned()
    {
        return $this->status !== 'selesai';
    }
}
