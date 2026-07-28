<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gudang = $this->route('gudang');
        $id = $gudang instanceof \Illuminate\Database\Eloquent\Model ? $gudang->getKey() : $gudang;
        return [
            'kode' => 'string|unique:gudang,kode,' . $id,
            'nama' => 'string',
            'alamat' => 'nullable|string',
            'pic' => 'nullable|string',
            'telepon' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'in:aktif,nonaktif',
        ];
    }
}