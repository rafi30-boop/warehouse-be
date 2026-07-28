<?php

namespace Tests\Feature\Api;

class BarangKeluarTest extends ApiTestCase
{
    public function test_index_barang_keluar()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/barang-keluar');
        $response->assertOk();
    }

    public function test_store_barang_keluar()
    {
        $this->actingAsAdmin();
        $gudang = \App\Models\Gudang::factory()->create();
        $customer = \App\Models\Customer::factory()->create();

        $response = $this->postJson('/api/barang-keluar', [
            'no_referensi' => 'BK001',
            'gudang_id' => $gudang->id,
            'customer_id' => $customer->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'pending',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['no_referensi' => 'BK001']);
    }

    public function test_show_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = \App\Models\BarangKeluar::factory()->create();
        $response = $this->getJson("/api/barang-keluar/{$barangKeluar->id}");
        $response->assertOk();
    }

    public function test_update_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = \App\Models\BarangKeluar::factory()->create();
        $response = $this->putJson("/api/barang-keluar/{$barangKeluar->id}", [
            'keterangan' => 'Updated keterangan',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['keterangan' => 'Updated keterangan']);
    }

    public function test_destroy_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = \App\Models\BarangKeluar::factory()->create();
        $response = $this->deleteJson("/api/barang-keluar/{$barangKeluar->id}");
        $response->assertNoContent();
    }

    public function test_export_excel_barang_keluar()
    {
        $this->actingAsAdmin();
        \App\Models\BarangKeluar::factory()->count(3)->create();
        $response = $this->getJson('/api/barang-keluar/export/excel');
        $response->assertOk();
    }

    public function test_print_surat_jalan_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = \App\Models\BarangKeluar::factory()->create();
        $response = $this->getJson("/api/barang-keluar/{$barangKeluar->id}/print-surat-jalan");
        $response->assertOk();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/barang-keluar', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/barang-keluar');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/barang-keluar/99999');
        $response->assertStatus(404);
    }
}