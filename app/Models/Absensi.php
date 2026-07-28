<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Absensi extends Model
{
    use SoftDeletes;

    protected $table = 'absensi';

    protected $fillable = [
        'user_id', 'gudang_id', 'shift_id', 'tanggal',
        'jam_masuk', 'jam_pulang', 'status',
        'lokasi_checkin', 'lokasi_checkout', 'radius_validasi',
        'foto_masuk', 'foto_pulang', 'keterangan',
        'approved_by', 'approved_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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