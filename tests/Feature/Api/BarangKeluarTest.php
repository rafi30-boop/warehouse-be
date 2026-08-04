<?php

namespace Tests\Feature\Api;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\Customer;
use App\Models\Gudang;

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
        $gudang = Gudang::factory()->create();
        $customer = Customer::factory()->create();

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
        $barangKeluar = BarangKeluar::factory()->create();
        $response = $this->getJson("/api/barang-keluar/{$barangKeluar->id}");
        $response->assertOk();
    }

    public function test_update_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = BarangKeluar::factory()->create();
        $response = $this->putJson("/api/barang-keluar/{$barangKeluar->id}", [
            'keterangan' => 'Updated keterangan',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['keterangan' => 'Updated keterangan']);
    }

    public function test_destroy_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = BarangKeluar::factory()->create();
        $response = $this->deleteJson("/api/barang-keluar/{$barangKeluar->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_export_excel_barang_keluar()
    {
        $this->actingAsAdmin();
        BarangKeluar::factory()->count(3)->create();
        $response = $this->getJson('/api/barang-keluar/export/excel');
        $response->assertOk();
    }

    public function test_print_surat_jalan_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = BarangKeluar::factory()->create();
        $response = $this->getJson("/api/barang-keluar/{$barangKeluar->id}/print-surat-jalan");
        $response->assertOk();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/barang-keluar', []);
        $response->assertStatus(422);
    }

    public function test_store_with_details_persists_details()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $customer = Customer::factory()->create();
        $barang = Barang::factory()->create();

        $this->makeApprovedStock($gudang, $barang, 10);

        $response = $this->postJson('/api/barang-keluar', [
            'no_referensi' => 'BK002',
            'gudang_id' => $gudang->id,
            'customer_id' => $customer->id,
            'tanggal' => now()->format('Y-m-d'),
            'details' => [
                ['barang_id' => $barang->id, 'qty' => 5, 'harga_satuan' => 15000],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.details.0.qty', '5.00');

        $this->assertDatabaseHas('barang_keluar_detail', [
            'barang_keluar_id' => $response->json('data.id'),
            'barang_id' => $barang->id,
            'qty' => 5,
        ]);
    }

    public function test_store_insufficient_stock_rejected()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $customer = Customer::factory()->create();
        $barang = Barang::factory()->create();

        $this->makeApprovedStock($gudang, $barang, 3);

        $response = $this->postJson('/api/barang-keluar', [
            'no_referensi' => 'BK003',
            'gudang_id' => $gudang->id,
            'customer_id' => $customer->id,
            'tanggal' => now()->format('Y-m-d'),
            'details' => [
                ['barang_id' => $barang->id, 'qty' => 99],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('details.0.qty');

        $this->assertDatabaseCount('barang_keluar', 0);
    }

    public function test_approve_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = BarangKeluar::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/barang-keluar/{$barangKeluar->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by.id', $this->adminUser->id);
    }

    public function test_reject_barang_keluar()
    {
        $this->actingAsAdmin();
        $barangKeluar = BarangKeluar::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/barang-keluar/{$barangKeluar->id}/reject");

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_deliver_barang_keluar()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $barang = Barang::factory()->create();

        $this->makeApprovedStock($gudang, $barang, 10);

        $barangKeluar = BarangKeluar::factory()->create(['status' => 'approved', 'gudang_id' => $gudang->id]);
        $barangKeluar->details()->create([
            'barang_id' => $barang->id,
            'qty' => 4,
            'harga_satuan' => 1000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 4000,
        ]);

        $response = $this->postJson("/api/barang-keluar/{$barangKeluar->id}/deliver");

        $response->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.delivered_by.id', $this->adminUser->id);

        $this->assertDatabaseHas('kartu_stok', [
            'barang_id' => $barang->id,
            'gudang_id' => $gudang->id,
            'tipe' => 'out',
            'qty' => 4,
            'saldo_sebelum' => 10,
            'saldo_sesudah' => 6,
            'referensi_id' => $barangKeluar->id,
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $barangKeluar->created_by,
            'judul' => 'Barang keluar dikirim',
        ]);
    }

    public function test_deliver_insufficient_stock_rejected()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $barang = Barang::factory()->create();

        $barangKeluar = BarangKeluar::factory()->create(['status' => 'approved', 'gudang_id' => $gudang->id]);
        $barangKeluar->details()->create([
            'barang_id' => $barang->id,
            'qty' => 100,
            'harga_satuan' => 1000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 100000,
        ]);

        $response = $this->postJson("/api/barang-keluar/{$barangKeluar->id}/deliver");

        $response->assertStatus(422);

        $this->assertDatabaseHas('barang_keluar', ['id' => $barangKeluar->id, 'status' => 'approved']);
    }

    public function test_partial_delivery()
    {
        $this->actingAsAdmin();
        $barangKeluar = BarangKeluar::factory()->create(['status' => 'approved']);

        $response = $this->postJson("/api/barang-keluar/{$barangKeluar->id}/partial");

        $response->assertOk()
            ->assertJsonPath('data.status', 'partial');
    }

    public function test_destroy_processed_blocked()
    {
        $this->actingAsAdmin();
        $barangKeluar = BarangKeluar::factory()->create(['status' => 'approved']);

        $response = $this->deleteJson("/api/barang-keluar/{$barangKeluar->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('barang_keluar', ['id' => $barangKeluar->id]);
    }

    private function makeApprovedStock(Gudang $gudang, Barang $barang, float $qty): void
    {
        $barangMasuk = BarangMasuk::factory()->create([
            'gudang_id' => $gudang->id,
            'status' => 'approved',
        ]);
        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => $qty,
            'harga_satuan' => 10000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => $qty * 10000,
        ]);
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
