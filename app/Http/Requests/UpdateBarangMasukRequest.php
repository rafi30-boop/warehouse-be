<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBarangMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $barangMasuk = $this->route('barang_masuk');
        $id = $barangMasuk instanceof Model ? $barangMasuk->getKey() : $barangMasuk;

        return [
            'no_referensi' => 'string|unique:barang_masuk,no_referensi,'.$id,
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'exists:gudang,id',
            'supplier_id' => 'exists:supplier,id',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected',
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
