<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StokOpnameDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stok_opname_id' => $this->stok_opname_id,
            'barang_id' => $this->barang_id,
            'lokasi_rak_id' => $this->lokasi_rak_id,
            'stok_sistem' => $this->stok_sistem,
            'stok_fisik' => $this->stok_fisik,
            'selisih' => $this->selisih,
            'keterangan' => $this->keterangan,
            'barang' => new BarangResource($this->whenLoaded('barang')),
            'lokasi_rak' => new LokasiRakResource($this->whenLoaded('lokasiRak')),
        ];
    }
}