<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'petugas_id' => $this->petugas_id,
            'nama' => $this->petugas?->nama ?? $this->user?->name,
            'gudang_id' => $this->gudang_id,
            'shift_id' => $this->shift_id,
            'tanggal' => $this->tanggal,
            'jam_masuk' => $this->jam_masuk,
            'jam_pulang' => $this->jam_pulang,
            'status' => $this->status,
            'lokasi_checkin' => $this->lokasi_checkin,
            'lokasi_checkout' => $this->lokasi_checkout,
            'radius_validasi' => $this->radius_validasi,
            'foto_masuk' => $this->foto_masuk,
            'foto_pulang' => $this->foto_pulang,
            'keterangan' => $this->keterangan,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'sumber' => $this->sumber,
            'di_luar_jadwal' => $this->di_luar_jadwal,
            'user' => new UserResource($this->whenLoaded('user')),
            'petugas' => $this->whenLoaded('petugas', fn () => $this->petugas ? [
                'id' => $this->petugas->id,
                'nama' => $this->petugas->nama,
                'kode' => $this->petugas->kode,
                'jabatan' => $this->petugas->jabatan,
            ] : null),
            'gudang' => new GudangResource($this->whenLoaded('gudang')),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'approvedBy' => new UserResource($this->whenLoaded('approvedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
