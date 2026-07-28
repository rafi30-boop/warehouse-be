<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KartuStok extends Model
{
    public $timestamps = false;

    protected $table = 'kartu_stok';

    protected $fillable = [
        'barang_id', 'gudang_id', 'lokasi_rak_id', 'tipe',
        'qty', 'saldo_sebelum', 'saldo_sesudah',
        'referensi_type', 'referensi_id', 'keterangan', 'created_by',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function lokasiRak()
    {
        return $this->belongsTo(LokasiRak::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}