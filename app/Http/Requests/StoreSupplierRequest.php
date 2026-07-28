<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => 'required|string|unique:supplier,kode',
            'tipe' => 'required|in:perusahaan,pribadi',
            'nama' => 'required|string',
            'kontak' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
            'alamat' => 'nullable|string',
            'npwp' => 'nullable|string',
        ];
    }
}