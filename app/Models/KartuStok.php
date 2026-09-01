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

    protected $appends = ['referensi_no'];

    public function getReferensiNoAttribute(): ?string
    {
        if (!$this->referensi_type || !$this->referensi_id) {
            return null;
        }
        try {
            $type = $this->referensi_type;
            // Normalisasikan jika hanya short name tanpa namespace
            if (!str_contains($type, '\\')) {
                $type = "App\\Models\\{$type}";
            }
            if (!class_exists($type)) {
                return null;
            }
            $model = $type::find($this->referensi_id);
            if (!$model) {
                return null;
            }
            // Semua dokumen sumber punya no_referensi
            return $model->no_referensi ?? "#{$this->referensi_id}";
        } catch (\Throwable $e) {
            return null;
        }
    }

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