<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPetugas extends Model
{
    protected $table = 'jadwal_petugas';

    protected $fillable = [
        'user_id', 'shift_id', 'tanggal', 'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}