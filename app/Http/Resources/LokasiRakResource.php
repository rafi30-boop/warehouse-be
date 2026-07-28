<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LokasiRakResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gudang_id' => $this->gudang_id,
            'kode_rak' => $this->kode_rak,
            'zona' => $this->zona,
            'kapasitas' => $this->kapasitas,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}