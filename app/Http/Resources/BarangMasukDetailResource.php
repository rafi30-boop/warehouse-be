<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangMasukDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'barang_masuk_id' => $this->barang_masuk_id,
            'barang_id' => $this->barang_id,
            'lokasi_rak_id' => $this->lokasi_rak_id,
            'qty' => $this->qty,
            'harga_satuan' => $this->harga_satuan,
            'diskon' => $this->diskon,
            'pajak' => $this->pajak,
            'subtotal' => $this->subtotal,
            'barang' => new BarangResource($this->whenLoaded('barang')),
            'lokasi_rak' => new LokasiRakResource($this->whenLoaded('lokasiRak')),
        ];
    }
}