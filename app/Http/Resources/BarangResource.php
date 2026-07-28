<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'nama' => $this->nama,
            'kategori_id' => $this->kategori_id,
            'satuan_id' => $this->satuan_id,
            'min_stok' => $this->min_stok,
            'max_stok' => $this->max_stok,
            'berat' => $this->berat,
            'foto' => $this->foto,
            'harga_beli' => $this->harga_beli,
            'harga_jual' => $this->harga_jual,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status,
            'kategori' => new KategoriResource($this->whenLoaded('kategori')),
            'satuan' => new SatuanResource($this->whenLoaded('satuan')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}