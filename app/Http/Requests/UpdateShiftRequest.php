<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => 'string',
            'jam_masuk' => 'date_format:H:i',
            'jam_pulang' => 'date_format:H:i',
            'toleransi_masuk' => 'integer|min:0',
            'toleransi_pulang' => 'integer|min:0',
            'status' => 'in:aktif,nonaktif',
        ];
    }
}