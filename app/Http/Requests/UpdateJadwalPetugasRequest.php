<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJadwalPetugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jadwal = $this->route('jadwal_petugas');
        $id = $jadwal instanceof Model ? $jadwal->getKey() : $jadwal;
        $userId = $this->input('user_id') ?? ($jadwal instanceof Model ? $jadwal->user_id : null);

        return [
            'user_id' => 'exists:users,id',
            'shift_id' => 'exists:shift,id',
            'tanggal' => 'date|unique:jadwal_petugas,tanggal,'.$id.',id,user_id,'.$userId,
        ];
    }
}
