<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => 'required|string|unique:customer,kode',
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