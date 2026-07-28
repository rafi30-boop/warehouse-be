<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokOpnameDetail extends Model
{
    public $timestamps = false;

    protected $table = 'stok_opname_detail';

    protected $fillable = [
        'stok_opname_id', 'barang_id', 'lokasi_rak_id',
        'stok_sistem', 'stok_fisik', 'selisih', 'keterangan',
    ];

    public function stokOpname()
    {
        return $this->belongsTo(StokOpname::class);
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