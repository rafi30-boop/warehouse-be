<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $fillable = ['kode', 'nama', 'kategori_id', 'satuan', 'harga_beli', 'harga_jual', 'stok_minimum', 'deskripsi', 'is_active'];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
}
