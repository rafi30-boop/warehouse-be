<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangMasuk extends Model
{
    protected $fillable = ['no_transaksi', 'tanggal', 'barang_id', 'gudang_id', 'supplier_id', 'jumlah', 'harga_satuan', 'total_harga', 'keterangan', 'user_id'];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
