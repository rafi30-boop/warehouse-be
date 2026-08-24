<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinRequest extends Model
{
    use HasFactory;

    protected $table = 'izin_requests';

    protected $fillable = [
        'user_id', 'petugas_id', 'jenis', 'tanggal_mulai', 'tanggal_selesai',
        'alasan', 'bukti', 'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function petugas()
    {
        return $this->belongsTo(Petugas::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }
}
