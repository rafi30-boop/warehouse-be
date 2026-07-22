<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['kode', 'nama', 'alamat', 'telepon', 'email', 'kontak_person', 'is_active'];
}
