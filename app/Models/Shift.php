<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['nama', 'jam_mulai', 'jam_selesai', 'is_active'];
}
