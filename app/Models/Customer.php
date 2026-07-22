<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['kode', 'nama', 'alamat', 'telepon', 'email', 'kontak_person', 'is_active'];
}
