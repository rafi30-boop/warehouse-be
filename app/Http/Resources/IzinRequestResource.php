<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IzinRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'petugas_id' => $this->petugas_id,
            'nama' => $this->petugas?->nama ?? $this->user?->name,
            'jenis' => $this->jenis,
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'alasan' => $this->alasan,
            'bukti' => $this->bukti,
            'status' => $this->status,
            'catatan_penolakan' => $this->catatan_penolakan,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'no_pegawai' => $this->user->no_pegawai,
            ]),
            'petugas' => $this->whenLoaded('petugas', fn() => [
                'id' => $this->petugas->id,
                'nama' => $this->petugas->nama,
                'kode' => $this->petugas->kode,
                'jabatan' => $this->petugas->jabatan,
            ]),
            'approvedBy' => $this->whenLoaded('approvedBy', fn() => [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ]),
        ];
    }
}
