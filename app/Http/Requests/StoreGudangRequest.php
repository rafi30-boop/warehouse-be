<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => 'required|string|unique:gudang,kode',
            'nama' => 'required|string',
            'alamat' => 'nullable|string',
            'pic' => 'nullable|string',
            'telepon' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'in:aktif,nonaktif',
        ];
    }
}