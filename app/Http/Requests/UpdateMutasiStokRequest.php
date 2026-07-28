<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMutasiStokRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mutasiStok = $this->route('mutasi_stok');
        $id = $mutasiStok instanceof \Illuminate\Database\Eloquent\Model ? $mutasiStok->getKey() : $mutasiStok;
        return [
            'no_referensi' => 'string|unique:mutasi_stok,no_referensi,' . $id,
            'barang_id' => 'exists:barang,id',
            'gudang_asal_id' => 'exists:gudang,id|different:gudang_tujuan_id',
            'gudang_tujuan_id' => 'exists:gudang,id|different:gudang_asal_id',
            'lokasi_rak_asal_id' => 'nullable|exists:lokasi_rak,id',
            'lokasi_rak_tujuan_id' => 'nullable|exists:lokasi_rak,id',
            'qty' => 'numeric|min:0.01',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,completed',
        ];
    }
}