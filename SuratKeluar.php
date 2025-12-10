<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    use HasFactory;

    protected $table = 'surat_keluar';

    protected $fillable = [
        'nomor_surat',
        'tanggal_surat',
        'tujuan_surat',
        'isi',
        'created_by',
        'approved_by',
        'file_surat',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    /**
     * Get the user who created the surat keluar
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the surat keluar
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope untuk filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    /**
     * Check if user can approve this surat
     */
    public function canBeApprovedBy($user)
    {
        return $user->hasRole(['admin', 'redaktur']) && $this->status === 'pending';
    }

    /**
     * Check if user can edit this surat
     */
    public function canBeEditedBy($user)
    {
        return ($this->created_by === $user->id && in_array($this->status, ['draft', 'rejected'])) 
            || $user->hasRole('admin');
    }
}
