<?php

namespace Tests\Feature\Api;

class RoleTest extends ApiTestCase
{
    public function test_index_role()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/role');
        $response->assertOk();
    }

    public function test_store_role()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/role', [
            'name' => 'manager',
            'permissions' => ['gudang-list', 'barang-list'],
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['name' => 'manager']);
    }

    public function test_show_role()
    {
        $this->actingAsAdmin();
        $role = \Spatie\Permission\Models\Role::create(['name' => 'test-role']);
        $response = $this->getJson("/api/role/{$role->id}");
        $response->assertOk();
    }

    public function test_update_role()
    {
        $this->actingAsAdmin();
        $role = \Spatie\Permission\Models\Role::create(['name' => 'test-role']);
        $response = $this->putJson("/api/role/{$role->id}", [
            'name' => 'manager-updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['name' => 'manager-updated']);
    }

    public function test_destroy_role()
    {
        $this->actingAsAdmin();
        $role = \Spatie\Permission\Models\Role::create(['name' => 'test-role']);
        $response = $this->deleteJson("/api/role/{$role->id}");
        $response->assertNoContent();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/role', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/role');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/role/99999');
        $response->assertStatus(404);
    }
}