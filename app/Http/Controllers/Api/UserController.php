<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with('roles')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        if (isset($data['roles'])) {
            $user->assignRole($data['roles']);
        }

        return response()->json($user->load('roles'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('roles.permissions'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return response()->json($user->load('roles'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}
