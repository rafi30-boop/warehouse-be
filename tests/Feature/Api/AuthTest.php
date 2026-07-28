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
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@email.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
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
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_me()
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJsonStructure(['id', 'name', 'email', 'roles']);
    }

    public function test_logout()
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_login_validation_error()
    {
        $response = $this->postJson('/api/login', []);
        $response->assertStatus(422);
    }
}