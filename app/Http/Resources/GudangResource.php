<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GudangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'pic' => $this->pic,
            'telepon' => $this->telepon,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'lokasi_rak' => LokasiRakResource::collection($this->whenLoaded('lokasiRak')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}