<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

#[OA\Tag(name: 'Auth')]
class AuthController extends Controller
{
    use ApiResponse;

    #[OA\Post(
        path: '/api/login',
        summary: 'Login user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login success', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Login berhasil'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'token', type: 'string', description: 'Bearer access token'),
                    new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Email atau password salah', 401);
        }

        if (! $user->is_active) {
            return $this->error('Akun Anda telah dinonaktifkan', 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('api-token')->accessToken;

        return $this->success([
            'user' => $user->load(['roles', 'gudang']),
            'token' => $token,
        ], 'Login berhasil');
    }

    #[OA\Post(
        path: '/api/register',
        summary: 'Register new user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Registration success', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Registrasi berhasil'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'token', type: 'string', description: 'Bearer access token'),
                    new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole(Role::where('name', 'operator')->first());

        $token = $user->createToken('api-token')->accessToken;

        return $this->success([
            'user' => $user->load(['roles', 'gudang']),
            'token' => $token,
        ], 'Registrasi berhasil', 201);
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Get authenticated user',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Current user data', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Data user berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/User'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function me(Request $request)
    {
        return $this->success($request->user()->load(['roles.permissions', 'gudang']), 'Data user berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout user',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout success', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->success(null, 'Logout berhasil');
    }

    #[OA\Post(
        path: '/api/refresh',
        summary: 'Refresh access token (issue new token, revoke current)',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'New token issued', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Token berhasil diperbarui'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'token', type: 'string', description: 'New bearer access token'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function refresh(Request $request)
    {
        $request->user()->token()->revoke();

        $token = $request->user()->createToken('api-token')->accessToken;

        return $this->success(['token' => $token], 'Token berhasil diperbarui');
    }
}
