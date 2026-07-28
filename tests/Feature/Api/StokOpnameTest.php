<?php

namespace Tests\Feature\Api;

class StokOpnameTest extends ApiTestCase
{
    public function test_index_stok_opname()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/stok-opname');
        $response->assertOk();
    }

    public function test_store_stok_opname()
    {
        $this->actingAsAdmin();
        $gudang = \App\Models\Gudang::factory()->create();

        $response = $this->postJson('/api/stok-opname', [
            'no_referensi' => 'SO001',
            'gudang_id' => $gudang->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'draft',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['no_referensi' => 'SO001']);
    }

    public function test_show_stok_opname()
    {
        $this->actingAsAdmin();
        $stokOpname = \App\Models\StokOpname::factory()->create();
        $response = $this->getJson("/api/stok-opname/{$stokOpname->id}");
        $response->assertOk();
    }

    public function test_update_stok_opname()
    {
        $this->actingAsAdmin();
        $stokOpname = \App\Models\StokOpname::factory()->create();
        $response = $this->putJson("/api/stok-opname/{$stokOpname->id}", [
            'keterangan' => 'Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['keterangan' => 'Updated']);
    }

    public function test_destroy_stok_opname()
    {
        $this->actingAsAdmin();
        $stokOpname = \App\Models\StokOpname::factory()->create();
        $response = $this->deleteJson("/api/stok-opname/{$stokOpname->id}");
        $response->assertNoContent();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/stok-opname', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/stok-opname');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/stok-opname/99999');
        $response->assertStatus(404);
    }
}