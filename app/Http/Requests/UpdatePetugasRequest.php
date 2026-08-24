<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePetugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $petugasId = $this->route('petugas')?->id ?? $this->route('petugas');

        return [
            'nama' => 'sometimes|required|string|max:150',
            'user_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('petugas', 'user_id')
                    ->whereNull('deleted_at')
                    ->ignore($petugasId),
            ],
            'kode' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('petugas', 'kode')
                    ->whereNull('deleted_at')
                    ->ignore($petugasId),
            ],
            'telepon' => 'nullable|string|max:30',
            'jabatan' => 'nullable|string|max:100',
            'area_kerja' => 'nullable|string|max:100',
            'tanggal_bergabung' => 'nullable|date',
            'status_operasional' => 'nullable|in:Aktif,Cuti,Non-Aktif',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'User ini sudah memiliki profil petugas.',
            'kode.unique' => 'Kode petugas sudah dipakai.',
        ];
    }
}
