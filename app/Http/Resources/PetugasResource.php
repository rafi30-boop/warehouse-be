<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetugasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'user_id' => $this->user_id,
            'kode' => $this->kode,
            'telepon' => $this->telepon,
            'jabatan' => $this->jabatan,
            'area_kerja' => $this->area_kerja,
            'tanggal_bergabung' => $this->tanggal_bergabung?->format('Y-m-d'),
            'status_operasional' => $this->status_operasional,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'no_pegawai' => $this->user->no_pegawai,
                'foto' => $this->user->foto,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
