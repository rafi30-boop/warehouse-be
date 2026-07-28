<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangKeluarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_referensi' => $this->no_referensi,
            'nomor_surat_jalan' => $this->nomor_surat_jalan,
            'gudang_id' => $this->gudang_id,
            'customer_id' => $this->customer_id,
            'tanggal' => $this->tanggal,
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'delivered_by' => $this->delivered_by,
            'delivered_at' => $this->delivered_at,
            'dokumen' => $this->dokumen,
            'gudang' => new GudangResource($this->whenLoaded('gudang')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'createdBy' => new UserResource($this->whenLoaded('createdBy')),
            'approvedBy' => new UserResource($this->whenLoaded('approvedBy')),
            'deliveredBy' => new UserResource($this->whenLoaded('deliveredBy')),
            'details' => BarangKeluarDetailResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}