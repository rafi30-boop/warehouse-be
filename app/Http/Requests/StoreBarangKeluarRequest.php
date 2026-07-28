<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangKeluarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_referensi' => 'required|string|unique:barang_keluar,no_referensi',
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'required|exists:gudang,id',
            'customer_id' => 'required|exists:customer,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,delivered,partial',
            'dokumen' => 'nullable|string',
        ];
    }
}