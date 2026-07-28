<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'gudang_id' => $this->gudang_id,
            'no_pegawai' => $this->no_pegawai,
            'telepon' => $this->telepon,
            'foto' => $this->foto,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'gudang' => new GudangResource($this->whenLoaded('gudang')),
            'roles' => $this->whenLoaded('roles'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}