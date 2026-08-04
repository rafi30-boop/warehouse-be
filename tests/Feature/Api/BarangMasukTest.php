<?php

namespace Tests\Feature\Api;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\Gudang;
use App\Models\Supplier;

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
        $gudang = Gudang::factory()->create();
        $supplier = Supplier::factory()->create();

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
        $barangMasuk = BarangMasuk::factory()->create();
        $response = $this->getJson("/api/barang-masuk/{$barangMasuk->id}");
        $response->assertOk();
    }

    public function test_update_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = BarangMasuk::factory()->create();
        $response = $this->putJson("/api/barang-masuk/{$barangMasuk->id}", [
            'keterangan' => 'Updated keterangan',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['keterangan' => 'Updated keterangan']);
    }

    public function test_destroy_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = BarangMasuk::factory()->create();
        $response = $this->deleteJson("/api/barang-masuk/{$barangMasuk->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_export_excel_barang_masuk()
    {
        $this->actingAsAdmin();
        BarangMasuk::factory()->count(3)->create();
        $response = $this->getJson('/api/barang-masuk/export/excel');
        $response->assertOk();
    }

    public function test_print_surat_jalan_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = BarangMasuk::factory()->create();
        $response = $this->getJson("/api/barang-masuk/{$barangMasuk->id}/print-surat-jalan");
        $response->assertOk();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/barang-masuk', []);
        $response->assertStatus(422);
    }

    public function test_store_with_details_persists_details()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $response = $this->postJson('/api/barang-masuk', [
            'no_referensi' => 'BM002',
            'gudang_id' => $gudang->id,
            'supplier_id' => $supplier->id,
            'tanggal' => now()->format('Y-m-d'),
            'details' => [
                ['barang_id' => $barang->id, 'qty' => 10, 'harga_satuan' => 10000],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.details.0.barang_id', $barang->id)
            ->assertJsonPath('data.details.0.subtotal', '100000.00');

        $this->assertDatabaseHas('barang_masuk_detail', [
            'barang_masuk_id' => $response->json('data.id'),
            'barang_id' => $barang->id,
            'qty' => 10,
        ]);
    }

    public function test_store_details_validation_error()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->postJson('/api/barang-masuk', [
            'no_referensi' => 'BM003',
            'gudang_id' => $gudang->id,
            'supplier_id' => $supplier->id,
            'tanggal' => now()->format('Y-m-d'),
            'details' => [
                ['qty' => 5],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('details.0.barang_id');
    }

    public function test_approve_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = BarangMasuk::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/barang-masuk/{$barangMasuk->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by.id', $this->adminUser->id);

        $this->assertDatabaseHas('barang_masuk', [
            'id' => $barangMasuk->id,
            'status' => 'approved',
            'approved_by' => $this->adminUser->id,
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $barangMasuk->created_by,
            'judul' => 'Barang masuk disetujui',
        ]);

        $this->assertDatabaseHas('aktivitas_log', [
            'action' => 'update',
            'model' => 'BarangMasuk',
            'model_id' => $barangMasuk->id,
        ]);
    }

    public function test_approve_non_pending_rejected()
    {
        $this->actingAsAdmin();
        $barangMasuk = BarangMasuk::factory()->create(['status' => 'approved']);

        $response = $this->postJson("/api/barang-masuk/{$barangMasuk->id}/approve");

        $response->assertStatus(422);
    }

    public function test_reject_barang_masuk()
    {
        $this->actingAsAdmin();
        $barangMasuk = BarangMasuk::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/barang-masuk/{$barangMasuk->id}/reject", [
            'keterangan' => 'Data tidak valid',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_destroy_approved_blocked()
    {
        $this->actingAsAdmin();
        $barangMasuk = BarangMasuk::factory()->create(['status' => 'approved']);

        $response = $this->deleteJson("/api/barang-masuk/{$barangMasuk->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('barang_masuk', ['id' => $barangMasuk->id]);
    }

    public function test_update_with_details_replaces_details()
    {
        $this->actingAsAdmin();
        $barangMasuk = BarangMasuk::factory()->create(['status' => 'pending']);
        $barang = Barang::factory()->create();
        $barang2 = Barang::factory()->create();

        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => 3,
            'harga_satuan' => 1000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 3000,
        ]);

        $response = $this->putJson("/api/barang-masuk/{$barangMasuk->id}", [
            'details' => [
                ['barang_id' => $barang2->id, 'qty' => 7, 'harga_satuan' => 2000],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.details')
            ->assertJsonPath('data.details.0.barang_id', $barang2->id);

        $this->assertDatabaseMissing('barang_masuk_detail', [
            'barang_masuk_id' => $barangMasuk->id,
            'barang_id' => $barang->id,
        ]);
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
