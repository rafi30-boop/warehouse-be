<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang';

    protected $fillable = [
        'sku', 'barcode', 'nama', 'kategori_id', 'satuan_id',
        'min_stok', 'max_stok', 'berat', 'foto', 'harga_beli',
        'harga_jual', 'deskripsi', 'status',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    public function barangMasukDetail()
    {
        return $this->hasMany(BarangMasukDetail::class);
    }

    public function barangKeluarDetail()
    {
        return $this->hasMany(BarangKeluarDetail::class);
    }

    public function kartuStok()
    {
        return $this->hasMany(KartuStok::class);
    }

    public function historyHarga()
    {
        return $this->hasMany(HistoryHarga::class);
    }

    public function batchBarang()
    {
        return $this->hasMany(BatchBarang::class);
    }
}