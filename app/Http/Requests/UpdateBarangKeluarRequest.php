<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBarangKeluarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $barangKeluar = $this->route('barang_keluar');
        $id = $barangKeluar instanceof Model ? $barangKeluar->getKey() : $barangKeluar;

        return [
            'no_referensi' => 'string|unique:barang_keluar,no_referensi,'.$id,
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'exists:gudang,id',
            'customer_id' => 'exists:customer,id',
            'tanggal' => 'date',
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
