<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStokOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_referensi' => 'required|string|unique:stok_opname,no_referensi',
            'gudang_id' => 'required|exists:gudang,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'in:draft,in_progress,completed,cancelled',
        ];
    }
}