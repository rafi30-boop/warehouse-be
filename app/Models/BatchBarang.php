<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchBarang extends Model
{
    protected $table = 'batch_barang';

    protected $fillable = [
        'barang_id', 'batch_number', 'expired_date', 'qty',
    ];

    protected function casts(): array
    {
        return [
            'expired_date' => 'date',
        ];
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}