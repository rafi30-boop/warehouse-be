<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
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
        $id = $stokOpname instanceof Model ? $stokOpname->getKey() : $stokOpname;

        return [
            'no_referensi' => 'string|unique:stok_opname,no_referensi,'.$id,
            'gudang_id' => 'exists:gudang,id',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:draft,in_progress,completed,cancelled',
            'details' => 'nullable|array|min:1',
            'details.*.barang_id' => 'required|exists:barang,id',
            'details.*.lokasi_rak_id' => 'nullable|exists:lokasi_rak,id',
            'details.*.stok_fisik' => 'required|numeric|min:0',
            'details.*.keterangan' => 'nullable|string',
        ];
    }
}
