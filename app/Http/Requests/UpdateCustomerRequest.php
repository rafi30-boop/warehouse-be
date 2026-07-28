<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $id = $customer instanceof \Illuminate\Database\Eloquent\Model ? $customer->getKey() : $customer;
        return [
            'kode' => 'string|unique:customer,kode,' . $id,
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