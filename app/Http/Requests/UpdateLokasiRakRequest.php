<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLokasiRakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lokasiRak = $this->route('lokasi_rak');
        $id = $lokasiRak instanceof Model ? $lokasiRak->getKey() : $lokasiRak;
        $gudangId = $this->input('gudang_id') ?? ($lokasiRak instanceof Model ? $lokasiRak->gudang_id : null);

        return [
            'gudang_id' => 'exists:gudang,id',
            'kode_rak' => 'string|max:50|unique:lokasi_rak,kode_rak,'.$id.',id,gudang_id,'.$gudangId,
            'zona' => 'nullable|string|max:50',
            'kapasitas' => 'nullable|integer|min:0',
            'deskripsi' => 'nullable|string',
            'status' => 'in:aktif,nonaktif,penuh',
        ];
    }
}
