<?php

namespace Tests\Feature\Api;

use App\Models\Supplier;

class SupplierTest extends ApiTestCase
{
    public function test_index_supplier()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/supplier');
        $response->assertOk();
    }

    public function test_store_supplier()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/supplier', [
            'kode' => 'SPL001',
            'tipe' => 'perusahaan',
            'nama' => 'PT Supplier Makmur',
            'email' => 'supplier@test.com',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['kode' => 'SPL001']);
    }

    public function test_show_supplier()
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();
        $response = $this->getJson("/api/supplier/{$supplier->id}");
        $response->assertOk();
    }

    public function test_update_supplier()
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();
        $response = $this->putJson("/api/supplier/{$supplier->id}", [
            'nama' => 'PT Supplier Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['nama' => 'PT Supplier Updated']);
    }

    public function test_destroy_supplier()
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();
        $response = $this->deleteJson("/api/supplier/{$supplier->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/supplier', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/supplier');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/supplier/99999');
        $response->assertStatus(404);
    }
}
