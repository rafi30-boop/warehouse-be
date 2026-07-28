<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'tipe' => $this->tipe,
            'nama' => $this->nama,
            'kontak' => $this->kontak,
            'telepon' => $this->telepon,
            'email' => $this->email,
            'alamat' => $this->alamat,
            'npwp' => $this->npwp,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}