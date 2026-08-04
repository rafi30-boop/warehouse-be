<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJadwalPetugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'shift_id' => 'required|exists:shift,id',
            'tanggal' => 'required|date|unique:jadwal_petugas,tanggal,NULL,id,user_id,'.$this->input('user_id'),
        ];
    }
}
