<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokOpname extends Model
{
    protected $fillable = ['no_opname', 'tanggal', 'barang_id', 'gudang_id', 'stok_sistem', 'stok_fisik', 'selisih', 'keterangan', 'user_id'];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
