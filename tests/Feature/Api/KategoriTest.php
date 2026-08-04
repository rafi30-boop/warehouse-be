<?php

namespace Tests\Feature\Api;

use App\Models\Kategori;

class KategoriTest extends ApiTestCase
{
    public function test_index_kategori()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/kategori');
        $response->assertOk();
    }

    public function test_store_kategori()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/kategori', [
            'nama' => 'Elektronik',
            'deskripsi' => 'Kategori elektronik',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['nama' => 'Elektronik']);
    }

    public function test_show_kategori()
    {
        $this->actingAsAdmin();
        $kategori = Kategori::factory()->create();
        $response = $this->getJson("/api/kategori/{$kategori->id}");
        $response->assertOk();
    }

    public function test_update_kategori()
    {
        $this->actingAsAdmin();
        $kategori = Kategori::factory()->create();
        $response = $this->putJson("/api/kategori/{$kategori->id}", [
            'nama' => 'Elektronik Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['nama' => 'Elektronik Updated']);
    }

    public function test_destroy_kategori()
    {
        $this->actingAsAdmin();
        $kategori = Kategori::factory()->create();
        $response = $this->deleteJson("/api/kategori/{$kategori->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/kategori', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/kategori');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/kategori/99999');
        $response->assertStatus(404);
    }
}
