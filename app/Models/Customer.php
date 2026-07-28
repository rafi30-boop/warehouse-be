<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer';

    protected $fillable = [
        'kode', 'tipe', 'nama', 'kontak', 'telepon',
        'email', 'alamat', 'npwp',
    ];

    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluar::class);
    }
}