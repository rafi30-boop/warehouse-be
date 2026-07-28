<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LokasiRak extends Model
{
    use SoftDeletes;

    protected $table = 'lokasi_rak';

    protected $fillable = [
        'gudang_id', 'kode_rak', 'zona', 'kapasitas', 'deskripsi', 'status',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function barangMasukDetail()
    {
        return $this->hasMany(BarangMasukDetail::class);
    }

    public function barangKeluarDetail()
    {
        return $this->hasMany(BarangKeluarDetail::class);
    }

    public function stokOpnameDetail()
    {
        return $this->hasMany(StokOpnameDetail::class);
    }
}