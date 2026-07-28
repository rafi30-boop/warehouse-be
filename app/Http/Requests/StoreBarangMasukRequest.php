<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_referensi' => 'required|string|unique:barang_masuk,no_referensi',
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'required|exists:gudang,id',
            'supplier_id' => 'required|exists:supplier,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,partial',
            'dokumen' => 'nullable|string',
        ];
    }
}