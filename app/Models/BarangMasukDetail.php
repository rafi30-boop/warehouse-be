<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangMasukDetail extends Model
{
    public $timestamps = false;

    protected $table = 'barang_masuk_detail';

    protected $fillable = [
        'barang_masuk_id', 'barang_id', 'lokasi_rak_id',
        'qty', 'harga_satuan', 'diskon', 'pajak', 'subtotal',
    ];

    public function barangMasuk()
    {
        return $this->belongsTo(BarangMasuk::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function lokasiRak()
    {
        return $this->belongsTo(LokasiRak::class);
    }
}