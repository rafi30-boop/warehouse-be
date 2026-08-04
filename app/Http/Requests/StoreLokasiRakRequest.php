<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLokasiRakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gudang_id' => 'required|exists:gudang,id',
            'kode_rak' => 'required|string|max:50|unique:lokasi_rak,kode_rak,NULL,id,gudang_id,'.$this->input('gudang_id'),
            'zona' => 'nullable|string|max:50',
            'kapasitas' => 'nullable|integer|min:0',
            'deskripsi' => 'nullable|string',
            'status' => 'in:aktif,nonaktif,penuh',
        ];
    }
}
