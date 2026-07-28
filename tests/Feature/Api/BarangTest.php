<?php

namespace Tests\Feature\Api;

class BarangTest extends ApiTestCase
{
    public function test_index_barang()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/barang');
        $response->assertOk();
    }

    public function test_store_barang()
    {
        $this->actingAsAdmin();
        $kategori = \App\Models\Kategori::factory()->create();
        $satuan = \App\Models\Satuan::factory()->create();

        $response = $this->postJson('/api/barang', [
            'sku' => 'BRG001',
            'nama' => 'Barang Example',
            'kategori_id' => $kategori->id,
            'satuan_id' => $satuan->id,
            'harga_beli' => 10000,
            'harga_jual' => 15000,
            'status' => 'aktif',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['sku' => 'BRG001']);
    }

    public function test_show_barang()
    {
        $this->actingAsAdmin();
        $barang = \App\Models\Barang::factory()->create();
        $response = $this->getJson("/api/barang/{$barang->id}");
        $response->assertOk();
    }

    public function test_update_barang()
    {
        $this->actingAsAdmin();
        $barang = \App\Models\Barang::factory()->create();
        $response = $this->putJson("/api/barang/{$barang->id}", [
            'nama' => 'Barang Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['nama' => 'Barang Updated']);
    }

    public function test_destroy_barang()
    {
        $this->actingAsAdmin();
        $barang = \App\Models\Barang::factory()->create();
        $response = $this->deleteJson("/api/barang/{$barang->id}");
        $response->assertNoContent();
    }

    public function test_export_excel_barang()
    {
        $this->actingAsAdmin();
        \App\Models\Barang::factory()->count(3)->create();
        $response = $this->getJson('/api/barang/export/excel');
        $response->assertOk();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/barang', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/barang');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/barang/99999');
        $response->assertStatus(404);
    }
}