<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $id = $supplier instanceof \Illuminate\Database\Eloquent\Model ? $supplier->getKey() : $supplier;
        return [
            'kode' => 'string|unique:supplier,kode,' . $id,
            'tipe' => 'in:perusahaan,pribadi',
            'nama' => 'string',
            'kontak' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
            'alamat' => 'nullable|string',
            'npwp' => 'nullable|string',
        ];
    }
}