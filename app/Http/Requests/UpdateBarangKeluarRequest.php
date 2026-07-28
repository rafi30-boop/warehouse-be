<?php

namespace App\Http\Requests;

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
        $id = $barangKeluar instanceof \Illuminate\Database\Eloquent\Model ? $barangKeluar->getKey() : $barangKeluar;
        return [
            'no_referensi' => 'string|unique:barang_keluar,no_referensi,' . $id,
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'exists:gudang,id',
            'customer_id' => 'exists:customer,id',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,delivered,partial',
            'dokumen' => 'nullable|string',
        ];
    }
}