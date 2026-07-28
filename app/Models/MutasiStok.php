<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $table = 'mutasi_stok';

    protected $fillable = [
        'no_referensi', 'barang_id', 'gudang_asal_id', 'gudang_tujuan_id',
        'lokasi_rak_asal_id', 'lokasi_rak_tujuan_id', 'qty', 'tanggal',
        'keterangan', 'status', 'created_by', 'approved_by', 'approved_at',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudangAsal()
    {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id');
    }

    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }

    public function lokasiRakAsal()
    {
        return $this->belongsTo(LokasiRak::class, 'lokasi_rak_asal_id');
    }

    public function lokasiRakTujuan()
    {
        return $this->belongsTo(LokasiRak::class, 'lokasi_rak_tujuan_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}