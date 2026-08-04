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
            'details' => 'nullable|array|min:1',
            'details.*.barang_id' => 'required|exists:barang,id',
            'details.*.lokasi_rak_id' => 'nullable|exists:lokasi_rak,id',
            'details.*.qty' => 'required|numeric|min:0.01',
            'details.*.harga_satuan' => 'nullable|numeric|min:0',
            'details.*.diskon' => 'nullable|numeric|min:0',
            'details.*.pajak' => 'nullable|numeric|min:0',
        ];
    }
}
