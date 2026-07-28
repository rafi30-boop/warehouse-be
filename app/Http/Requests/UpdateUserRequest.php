<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $id = $user instanceof \Illuminate\Database\Eloquent\Model ? $user->getKey() : $user;
        return [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $id,
            'password' => 'nullable|min:8',
            'gudang_id' => 'nullable|exists:gudang,id',
            'no_pegawai' => 'nullable|string|unique:users,no_pegawai,' . $id,
            'telepon' => 'nullable|string',
            'foto' => 'nullable|string',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ];
    }
}