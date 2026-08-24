<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'gudang_id' => 'required|exists:gudang,id',
            'shift_id' => 'required|exists:shift,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'status' => 'required|in:hadir,izin,sakit,alpha,cuti,terlambat',
            'lokasi_checkin' => 'nullable|string',
            'lokasi_checkout' => 'nullable|string',
            'radius_validasi' => 'nullable|integer',
            'foto_masuk' => 'nullable|string',
            'foto_pulang' => 'nullable|string',
            'keterangan' => 'required|string|min:5',
            'sumber' => 'nullable|in:qr,manual',
            'di_luar_jadwal' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'sumber' => 'manual',
        ]);
    }
}