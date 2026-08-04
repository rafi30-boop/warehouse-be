<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $batch = $this->route('batch_barang');
        $id = $batch instanceof Model ? $batch->getKey() : $batch;
        $barangId = $this->input('barang_id') ?? ($batch instanceof Model ? $batch->barang_id : null);

        return [
            'barang_id' => 'exists:barang,id',
            'batch_number' => 'string|max:50|unique:batch_barang,batch_number,'.$id.',id,barang_id,'.$barangId,
            'expired_date' => 'nullable|date',
            'qty' => 'nullable|numeric|min:0',
        ];
    }
}
