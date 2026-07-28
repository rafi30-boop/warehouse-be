<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'supplier';

    protected $fillable = [
        'kode', 'tipe', 'nama', 'kontak', 'telepon',
        'email', 'alamat', 'npwp',
    ];

    public function barangMasuk()
    {
        return $this->hasMany(BarangMasuk::class);
    }
}