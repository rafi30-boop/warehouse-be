<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AktivitasLog extends Model
{
    public $timestamps = false;

    protected $table = 'aktivitas_log';

    protected $fillable = [
        'user_id', 'ip_address', 'user_agent', 'url', 'method',
        'action', 'model', 'model_id', 'data_old', 'data_new',
    ];

    protected function casts(): array
    {
        return [
            'data_old' => 'array',
            'data_new' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}