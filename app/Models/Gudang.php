<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gudang extends Model
{
    use SoftDeletes;

    protected $table = 'gudang';

    protected $fillable = [
        'kode', 'nama', 'alamat', 'pic', 'telepon',
        'latitude', 'longitude', 'status',
    ];

    public function lokasiRak()
    {
        return $this->hasMany(LokasiRak::class);
    }

    public function barangMasuk()
    {
        return $this->hasMany(BarangMasuk::class);
    }

    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluar::class);
    }

    public function stokOpname()
    {
        return $this->hasMany(StokOpname::class);
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}