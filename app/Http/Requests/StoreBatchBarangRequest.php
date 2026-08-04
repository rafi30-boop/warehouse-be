<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barang_id' => 'required|exists:barang,id',
            'batch_number' => 'required|string|max:50|unique:batch_barang,batch_number,NULL,id,barang_id,'.$this->input('barang_id'),
            'expired_date' => 'nullable|date',
            'qty' => 'nullable|numeric|min:0',
        ];
    }
}
