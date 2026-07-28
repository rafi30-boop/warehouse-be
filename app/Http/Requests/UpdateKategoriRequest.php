<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|exists:kategori_barang,id',
            'nama' => 'string',
            'deskripsi' => 'nullable|string',
        ];
    }
}