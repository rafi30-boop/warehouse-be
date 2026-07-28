<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMutasiStokRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_referensi' => 'required|string|unique:mutasi_stok,no_referensi',
            'barang_id' => 'required|exists:barang,id',
            'gudang_asal_id' => 'required|exists:gudang,id|different:gudang_tujuan_id',
            'gudang_tujuan_id' => 'required|exists:gudang,id|different:gudang_asal_id',
            'lokasi_rak_asal_id' => 'nullable|exists:lokasi_rak,id',
            'lokasi_rak_tujuan_id' => 'nullable|exists:lokasi_rak,id',
            'qty' => 'required|numeric|min:0.01',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,completed',
        ];
    }
}