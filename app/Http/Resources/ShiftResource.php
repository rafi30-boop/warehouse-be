<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'jam_masuk' => $this->jam_masuk,
            'jam_pulang' => $this->jam_pulang,
            'toleransi_masuk' => $this->toleransi_masuk,
            'toleransi_pulang' => $this->toleransi_pulang,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}