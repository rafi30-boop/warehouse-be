<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => 'required|string|unique:barang,sku',
            'barcode' => 'nullable|string|unique:barang,barcode',
            'nama' => 'required|string',
            'kategori_id' => 'required|exists:kategori_barang,id',
            'satuan_id' => 'required|exists:satuan,id',
            'min_stok' => 'numeric|min:0',
            'max_stok' => 'numeric|min:0',
            'berat' => 'nullable|numeric',
            'foto' => 'nullable|string',
            'harga_beli' => 'numeric|min:0',
            'harga_jual' => 'numeric|min:0',
            'deskripsi' => 'nullable|string',
            'status' => 'in:aktif,nonaktif',
        ];
    }
}