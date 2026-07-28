<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $barang = $this->route('barang');
        $id = $barang instanceof \Illuminate\Database\Eloquent\Model ? $barang->getKey() : $barang;
        return [
            'sku' => 'string|unique:barang,sku,' . $id,
            'barcode' => 'nullable|string|unique:barang,barcode,' . $id,
            'nama' => 'string',
            'kategori_id' => 'exists:kategori_barang,id',
            'satuan_id' => 'exists:satuan,id',
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