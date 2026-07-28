<?php

namespace App\Http\Requests;

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
        $id = $barangMasuk instanceof \Illuminate\Database\Eloquent\Model ? $barangMasuk->getKey() : $barangMasuk;
        return [
            'no_referensi' => 'string|unique:barang_masuk,no_referensi,' . $id,
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'exists:gudang,id',
            'supplier_id' => 'exists:supplier,id',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,partial',
            'dokumen' => 'nullable|string',
        ];
    }
}