<?php

namespace Tests\Feature\Api;

class GudangTest extends ApiTestCase
{
    public function test_index_gudang()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/gudang');
        $response->assertOk();
    }

    public function test_store_gudang()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/gudang', [
            'kode' => 'GDG001',
            'nama' => 'Gudang Utama',
            'alamat' => 'Jl. Contoh No.1',
            'status' => 'aktif',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['kode' => 'GDG001']);
    }

    public function test_show_gudang()
    {
        $this->actingAsAdmin();
        $gudang = \App\Models\Gudang::factory()->create();
        $response = $this->getJson("/api/gudang/{$gudang->id}");
        $response->assertOk();
    }

    public function test_update_gudang()
    {
        $this->actingAsAdmin();
        $gudang = \App\Models\Gudang::factory()->create();
        $response = $this->putJson("/api/gudang/{$gudang->id}", [
            'nama' => 'Gudang Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['nama' => 'Gudang Updated']);
    }

    public function test_destroy_gudang()
    {
        $this->actingAsAdmin();
        $gudang = \App\Models\Gudang::factory()->create();
        $response = $this->deleteJson("/api/gudang/{$gudang->id}");
        $response->assertNoContent();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/gudang', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/gudang');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/gudang/99999');
        $response->assertStatus(404);
    }
}