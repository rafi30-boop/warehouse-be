<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangMasuk extends Model
{
    use HasFactory;
    protected $table = 'barang_masuk';

    protected $fillable = [
        'no_referensi', 'nomor_surat_jalan', 'gudang_id', 'supplier_id',
        'tanggal', 'keterangan', 'status', 'created_by', 'approved_by',
        'approved_at', 'dokumen',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details()
    {
        return $this->hasMany(BarangMasukDetail::class);
    }
}