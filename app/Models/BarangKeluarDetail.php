<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangKeluarDetail extends Model
{
    public $timestamps = false;

    protected $table = 'barang_keluar_detail';

    protected $fillable = [
        'barang_keluar_id', 'barang_id', 'lokasi_rak_id',
        'qty', 'harga_satuan', 'diskon', 'pajak', 'subtotal',
    ];

    public function barangKeluar()
    {
        return $this->belongsTo(BarangKeluar::class);
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