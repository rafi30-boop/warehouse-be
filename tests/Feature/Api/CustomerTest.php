<?php

namespace Tests\Feature\Api;

use App\Models\Customer;

class CustomerTest extends ApiTestCase
{
    public function test_index_customer()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/customer');
        $response->assertOk();
    }

    public function test_store_customer()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/customer', [
            'kode' => 'CST001',
            'tipe' => 'perusahaan',
            'nama' => 'PT Customer Sejahtera',
            'email' => 'customer@test.com',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['kode' => 'CST001']);
    }

    public function test_show_customer()
    {
        $this->actingAsAdmin();
        $customer = Customer::factory()->create();
        $response = $this->getJson("/api/customer/{$customer->id}");
        $response->assertOk();
    }

    public function test_update_customer()
    {
        $this->actingAsAdmin();
        $customer = Customer::factory()->create();
        $response = $this->putJson("/api/customer/{$customer->id}", [
            'nama' => 'PT Customer Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['nama' => 'PT Customer Updated']);
    }

    public function test_destroy_customer()
    {
        $this->actingAsAdmin();
        $customer = Customer::factory()->create();
        $response = $this->deleteJson("/api/customer/{$customer->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/customer', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/customer');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/customer/99999');
        $response->assertStatus(404);
    }
}
