<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $table = 'shift';

    protected $fillable = [
        'nama', 'jam_masuk', 'jam_pulang',
        'toleransi_masuk', 'toleransi_pulang', 'status',
    ];

    public function jadwalPetugas()
    {
        return $this->hasMany(JadwalPetugas::class);
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }
}