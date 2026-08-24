<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $fillable = [
        'user_id', 'petugas_id', 'gudang_id', 'shift_id', 'tanggal',
        'jam_masuk', 'jam_pulang', 'status',
        'lokasi_checkin', 'lokasi_checkout', 'radius_validasi',
        'foto_masuk', 'foto_pulang', 'keterangan',
        'approved_by', 'approved_at',
        'sumber', 'di_luar_jadwal',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function petugas()
    {
        return $this->belongsTo(Petugas::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
