<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'gudang_id' => 'nullable|exists:gudang,id',
            'no_pegawai' => 'nullable|string|unique:users,no_pegawai',
            'telepon' => 'nullable|string',
            'foto' => 'nullable|string',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ];
    }
}