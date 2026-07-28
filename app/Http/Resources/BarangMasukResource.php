<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangMasukResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_referensi' => $this->no_referensi,
            'nomor_surat_jalan' => $this->nomor_surat_jalan,
            'gudang_id' => $this->gudang_id,
            'supplier_id' => $this->supplier_id,
            'tanggal' => $this->tanggal,
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'dokumen' => $this->dokumen,
            'gudang' => new GudangResource($this->whenLoaded('gudang')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'createdBy' => new UserResource($this->whenLoaded('createdBy')),
            'approvedBy' => new UserResource($this->whenLoaded('approvedBy')),
            'details' => BarangMasukDetailResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}