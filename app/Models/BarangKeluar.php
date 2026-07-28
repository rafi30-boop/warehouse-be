<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangKeluar extends Model
{
    use HasFactory;
    protected $table = 'barang_keluar';

    protected $fillable = [
        'no_referensi', 'nomor_surat_jalan', 'gudang_id', 'customer_id',
        'tanggal', 'keterangan', 'status', 'created_by', 'approved_by',
        'approved_at', 'delivered_by', 'delivered_at', 'dokumen',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function details()
    {
        return $this->hasMany(BarangKeluarDetail::class);
    }
}