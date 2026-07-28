<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|exists:kategori_barang,id',
            'nama' => 'required|string',
            'deskripsi' => 'nullable|string',
        ];
    }
}