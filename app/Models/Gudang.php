<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    protected $fillable = ['kode', 'nama', 'alamat', 'keterangan', 'is_active'];
}
