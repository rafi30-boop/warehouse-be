<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $fillable = ['no_mutasi', 'tanggal', 'barang_id', 'gudang_asal_id', 'gudang_tujuan_id', 'jumlah', 'keterangan', 'user_id'];

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
