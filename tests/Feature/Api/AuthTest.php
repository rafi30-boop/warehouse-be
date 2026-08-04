<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Hash;

class AuthTest extends ApiTestCase
{
    public function test_login_success()
    {
        $password = 'password123';
        $this->adminUser->update(['password' => Hash::make($password)]);

        $response = $this->postJson('/api/login', [
            'email' => $this->adminUser->email,
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']])
            ->assertJson(['success' => true, 'message' => 'Login berhasil']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@email.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Email atau password salah']);
    }

    public function test_register_success()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']])
            ->assertJson(['success' => true, 'message' => 'Registrasi berhasil']);
    }

    public function test_me()
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['id', 'name', 'email', 'roles']]);
    }

    public function test_logout()
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/logout');

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => 'Logout berhasil']);
    }

    public function test_refresh_token()
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/refresh');

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['token']])
            ->assertJson(['success' => true]);
    }

    public function test_login_inactive_account_rejected()
    {
        $password = 'password123';
        $this->adminUser->update(['password' => Hash::make($password), 'is_active' => false]);

        $response = $this->postJson('/api/login', [
            'email' => $this->adminUser->email,
            'password' => $password,
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_login_validation_error()
    {
        $response = $this->postJson('/api/login', []);
        $response->assertStatus(422);
    }
}
