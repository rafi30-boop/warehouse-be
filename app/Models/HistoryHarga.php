<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryHarga extends Model
{
    public $timestamps = false;

    protected $table = 'history_harga';

    protected $fillable = [
        'barang_id', 'harga_beli', 'harga_jual', 'tanggal_efektif', 'created_by',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}