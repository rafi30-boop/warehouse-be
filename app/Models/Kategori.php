<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kategori extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kategori_barang';

    protected $fillable = [
        'parent_id', 'nama', 'deskripsi',
    ];

    public function parent()
    {
        return $this->belongsTo(Kategori::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Kategori::class, 'parent_id');
    }

    public function barang()
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }
}