<?php

namespace Tests\Feature\Api;

use App\Models\User;

class UserTest extends ApiTestCase
{
    public function test_index_user()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/user');
        $response->assertOk();
    }

    public function test_store_user()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/user', [
            'name' => 'Staff Baru',
            'email' => 'staff@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['email' => 'staff@test.com']);
    }

    public function test_show_user()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        $response = $this->getJson("/api/user/{$user->id}");
        $response->assertOk();
    }

    public function test_update_user()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        $response = $this->putJson("/api/user/{$user->id}", [
            'name' => 'User Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['name' => 'User Updated']);
    }

    public function test_destroy_user()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        $response = $this->deleteJson("/api/user/{$user->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/user', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/user/99999');
        $response->assertStatus(404);
    }
}
