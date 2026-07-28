<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'exists:users,id',
            'gudang_id' => 'exists:gudang,id',
            'shift_id' => 'exists:shift,id',
            'tanggal' => 'date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'status' => 'in:hadir,izin,sakit,alpha,cuti,terlambat',
            'lokasi_checkin' => 'nullable|string',
            'lokasi_checkout' => 'nullable|string',
            'radius_validasi' => 'nullable|integer',
            'foto_masuk' => 'nullable|string',
            'foto_pulang' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ];
    }
}