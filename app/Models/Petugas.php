<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Petugas extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'petugas';

    protected $fillable = [
        'nama', 'user_id', 'kode', 'telepon', 'jabatan', 'area_kerja',
        'tanggal_bergabung', 'status_operasional',
        'qr_version', 'qr_revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bergabung' => 'date',
            'deleted_at' => 'datetime',
            'qr_revoked_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
