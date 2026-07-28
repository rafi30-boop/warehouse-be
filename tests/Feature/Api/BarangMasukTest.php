<?php

namespace Tests\Feature\Api;

class BarangMasukTest extends ApiTestCase
{
    public function test_index_barang_masuk()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/barang-masuk');
        $response->assertOk();
    }

    public function test_store_barang_masuk()
    {
        $this->actingAsAdmin();
        $gudang = \App\Models\Gudang::factory()->create();
        $supplier = \App\Models\Supplier::factory()->create();

        $response = $this->postJson('/api/barang-masuk', [
            'no_referensi' => 'BM001',
            'gudang_id' => $gudang->id,
            'supplier_id' => $supplier->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'pending',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['no_referensi' => 'BM001']);
    }

    public function test_show_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = \App\Models\BarangMasuk::factory()->create();
        $response = $this->getJson("/api/barang-masuk/{$barangMasuk->id}");
        $response->assertOk();
    }

    public function test_update_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = \App\Models\BarangMasuk::factory()->create();
        $response = $this->putJson("/api/barang-masuk/{$barangMasuk->id}", [
            'keterangan' => 'Updated keterangan',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['keterangan' => 'Updated keterangan']);
    }

    public function test_destroy_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = \App\Models\BarangMasuk::factory()->create();
        $response = $this->deleteJson("/api/barang-masuk/{$barangMasuk->id}");
        $response->assertNoContent();
    }

    public function test_export_excel_barang_masuk()
    {
        $this->actingAsAdmin();
        \App\Models\BarangMasuk::factory()->count(3)->create();
        $response = $this->getJson('/api/barang-masuk/export/excel');
        $response->assertOk();
    }

    public function test_print_surat_jalan_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = \App\Models\BarangMasuk::factory()->create();
        $response = $this->getJson("/api/barang-masuk/{$barangMasuk->id}/print-surat-jalan");
        $response->assertOk();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/barang-masuk', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/barang-masuk');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/barang-masuk/99999');
        $response->assertStatus(404);
    }
}