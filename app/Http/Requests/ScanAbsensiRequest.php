<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_payload' => 'required|string',
            'gudang_id' => 'nullable|integer|exists:gudang,id',
        ];
    }
}
