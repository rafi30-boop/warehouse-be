<?php

namespace Tests\Feature\Api;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\Gudang;
use App\Models\StokOpname;

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
        $gudang = Gudang::factory()->create();

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
        $stokOpname = StokOpname::factory()->create();
        $response = $this->getJson("/api/stok-opname/{$stokOpname->id}");
        $response->assertOk();
    }

    public function test_update_stok_opname()
    {
        $this->actingAsAdmin();
        $stokOpname = StokOpname::factory()->create();
        $response = $this->putJson("/api/stok-opname/{$stokOpname->id}", [
            'keterangan' => 'Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['keterangan' => 'Updated']);
    }

    public function test_destroy_stok_opname()
    {
        $this->actingAsAdmin();
        $stokOpname = StokOpname::factory()->create();
        $response = $this->deleteJson("/api/stok-opname/{$stokOpname->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/stok-opname', []);
        $response->assertStatus(422);
    }

    public function test_store_with_details_computes_stok_sistem()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $barang = Barang::factory()->create();

        $barangMasuk = BarangMasuk::factory()->create(['gudang_id' => $gudang->id, 'status' => 'approved']);
        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => 10,
            'harga_satuan' => 1000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 10000,
        ]);

        $response = $this->postJson('/api/stok-opname', [
            'no_referensi' => 'SO002',
            'gudang_id' => $gudang->id,
            'tanggal' => now()->format('Y-m-d'),
            'details' => [
                ['barang_id' => $barang->id, 'stok_fisik' => 9],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.details.0.stok_sistem', '10.00')
            ->assertJsonPath('data.details.0.selisih', '-1.00');
    }

    public function test_start_stok_opname()
    {
        $this->actingAsAdmin();
        $stokOpname = StokOpname::factory()->create(['status' => 'draft']);

        $response = $this->postJson("/api/stok-opname/{$stokOpname->id}/start");

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_complete_stok_opname_recomputes_selisih()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $barang = Barang::factory()->create();

        $barangMasuk = BarangMasuk::factory()->create(['gudang_id' => $gudang->id, 'status' => 'approved']);
        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => 10,
            'harga_satuan' => 1000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 10000,
        ]);

        $stokOpname = StokOpname::factory()->create(['gudang_id' => $gudang->id, 'status' => 'in_progress']);
        $stokOpname->details()->create([
            'barang_id' => $barang->id,
            'stok_fisik' => 8,
            'stok_sistem' => 0,
            'selisih' => 8,
        ]);

        $response = $this->postJson("/api/stok-opname/{$stokOpname->id}/complete");

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.details.0.stok_sistem', '10.00')
            ->assertJsonPath('data.details.0.selisih', '-2.00')
            ->assertJsonPath('data.approved_by.id', $this->adminUser->id);
    }

    public function test_cancel_stok_opname()
    {
        $this->actingAsAdmin();
        $stokOpname = StokOpname::factory()->create(['status' => 'in_progress']);

        $response = $this->postJson("/api/stok-opname/{$stokOpname->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_destroy_completed_blocked()
    {
        $this->actingAsAdmin();
        $stokOpname = StokOpname::factory()->create(['status' => 'completed']);

        $response = $this->deleteJson("/api/stok-opname/{$stokOpname->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('stok_opname', ['id' => $stokOpname->id]);
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
