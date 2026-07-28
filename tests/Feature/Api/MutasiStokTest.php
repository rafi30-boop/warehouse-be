<?php

namespace Tests\Feature\Api;

class MutasiStokTest extends ApiTestCase
{
    public function test_index_mutasi_stok()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/mutasi-stok');
        $response->assertOk();
    }

    public function test_store_mutasi_stok()
    {
        $this->actingAsAdmin();
        $barang = \App\Models\Barang::factory()->create();
        $gudang1 = \App\Models\Gudang::factory()->create();
        $gudang2 = \App\Models\Gudang::factory()->create();

        $response = $this->postJson('/api/mutasi-stok', [
            'no_referensi' => 'MS001',
            'barang_id' => $barang->id,
            'gudang_asal_id' => $gudang1->id,
            'gudang_tujuan_id' => $gudang2->id,
            'qty' => 10,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'pending',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['no_referensi' => 'MS001']);
    }

    public function test_show_mutasi_stok()
    {
        $this->actingAsAdmin();
        $mutasiStok = \App\Models\MutasiStok::factory()->create();
        $response = $this->getJson("/api/mutasi-stok/{$mutasiStok->id}");
        $response->assertOk();
    }

    public function test_update_mutasi_stok()
    {
        $this->actingAsAdmin();
        $mutasiStok = \App\Models\MutasiStok::factory()->create();
        $response = $this->putJson("/api/mutasi-stok/{$mutasiStok->id}", [
            'keterangan' => 'Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['keterangan' => 'Updated']);
    }

    public function test_destroy_mutasi_stok()
    {
        $this->actingAsAdmin();
        $mutasiStok = \App\Models\MutasiStok::factory()->create();
        $response = $this->deleteJson("/api/mutasi-stok/{$mutasiStok->id}");
        $response->assertNoContent();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/mutasi-stok', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/mutasi-stok');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/mutasi-stok/99999');
        $response->assertStatus(404);
    }
}