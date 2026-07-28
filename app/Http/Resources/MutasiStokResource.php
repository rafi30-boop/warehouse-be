<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MutasiStokResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_referensi' => $this->no_referensi,
            'barang_id' => $this->barang_id,
            'gudang_asal_id' => $this->gudang_asal_id,
            'gudang_tujuan_id' => $this->gudang_tujuan_id,
            'lokasi_rak_asal_id' => $this->lokasi_rak_asal_id,
            'lokasi_rak_tujuan_id' => $this->lokasi_rak_tujuan_id,
            'qty' => $this->qty,
            'tanggal' => $this->tanggal,
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'barang' => new BarangResource($this->whenLoaded('barang')),
            'gudang_asal' => new GudangResource($this->whenLoaded('gudangAsal')),
            'gudang_tujuan' => new GudangResource($this->whenLoaded('gudangTujuan')),
            'lokasi_rak_asal' => new LokasiRakResource($this->whenLoaded('lokasiRakAsal')),
            'lokasi_rak_tujuan' => new LokasiRakResource($this->whenLoaded('lokasiRakTujuan')),
            'createdBy' => new UserResource($this->whenLoaded('createdBy')),
            'approvedBy' => new UserResource($this->whenLoaded('approvedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}