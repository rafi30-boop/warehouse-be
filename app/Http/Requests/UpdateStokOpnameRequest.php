<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStokOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stokOpname = $this->route('stok_opname');
        $id = $stokOpname instanceof \Illuminate\Database\Eloquent\Model ? $stokOpname->getKey() : $stokOpname;
        return [
            'no_referensi' => 'string|unique:stok_opname,no_referensi,' . $id,
            'gudang_id' => 'exists:gudang,id',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:draft,in_progress,completed,cancelled',
        ];
    }
}