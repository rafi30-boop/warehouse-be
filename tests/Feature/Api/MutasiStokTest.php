<?php

namespace Tests\Feature\Api;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\Gudang;
use App\Models\MutasiStok;

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
        $barang = Barang::factory()->create();
        $gudang1 = Gudang::factory()->create();
        $gudang2 = Gudang::factory()->create();

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
        $mutasiStok = MutasiStok::factory()->create();
        $response = $this->getJson("/api/mutasi-stok/{$mutasiStok->id}");
        $response->assertOk();
    }

    public function test_update_mutasi_stok()
    {
        $this->actingAsAdmin();
        $mutasiStok = MutasiStok::factory()->create();
        $response = $this->putJson("/api/mutasi-stok/{$mutasiStok->id}", [
            'keterangan' => 'Updated',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['keterangan' => 'Updated']);
    }

    public function test_destroy_mutasi_stok()
    {
        $this->actingAsAdmin();
        $mutasiStok = MutasiStok::factory()->create();
        $response = $this->deleteJson("/api/mutasi-stok/{$mutasiStok->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
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

    public function test_approve_mutasi_stok()
    {
        $this->actingAsAdmin();
        $mutasiStok = MutasiStok::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/mutasi-stok/{$mutasiStok->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by.id', $this->adminUser->id);

        $this->assertDatabaseHas('mutasi_stok', [
            'id' => $mutasiStok->id,
            'status' => 'approved',
            'approved_by' => $this->adminUser->id,
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $mutasiStok->created_by,
            'judul' => 'Mutasi stok disetujui',
        ]);
    }

    public function test_reject_mutasi_stok()
    {
        $this->actingAsAdmin();
        $mutasiStok = MutasiStok::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/mutasi-stok/{$mutasiStok->id}/reject", [
            'keterangan' => 'Stok asal tidak cukup',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $mutasiStok->created_by,
            'judul' => 'Mutasi stok ditolak',
        ]);
    }

    public function test_complete_mutasi_stok_moves_stock()
    {
        $this->actingAsAdmin();
        $barang = Barang::factory()->create();
        $gudang1 = Gudang::factory()->create();
        $gudang2 = Gudang::factory()->create();

        $barangMasuk = BarangMasuk::factory()->create([
            'gudang_id' => $gudang1->id,
            'status' => 'approved',
        ]);
        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => 10,
            'harga_satuan' => 10000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 100000,
        ]);

        $mutasiStok = MutasiStok::factory()->create([
            'status' => 'approved',
            'barang_id' => $barang->id,
            'gudang_asal_id' => $gudang1->id,
            'gudang_tujuan_id' => $gudang2->id,
            'qty' => 4,
        ]);

        $response = $this->postJson("/api/mutasi-stok/{$mutasiStok->id}/complete");

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('kartu_stok', [
            'barang_id' => $barang->id,
            'gudang_id' => $gudang1->id,
            'tipe' => 'mutasi_out',
            'qty' => 4,
            'saldo_sebelum' => 10,
            'saldo_sesudah' => 6,
            'referensi_id' => $mutasiStok->id,
        ]);

        $this->assertDatabaseHas('kartu_stok', [
            'barang_id' => $barang->id,
            'gudang_id' => $gudang2->id,
            'tipe' => 'mutasi_in',
            'qty' => 4,
            'saldo_sebelum' => 0,
            'saldo_sesudah' => 4,
            'referensi_id' => $mutasiStok->id,
        ]);
    }

    public function test_complete_insufficient_stock_rejected()
    {
        $this->actingAsAdmin();
        $barang = Barang::factory()->create();
        $gudang1 = Gudang::factory()->create();
        $gudang2 = Gudang::factory()->create();

        $mutasiStok = MutasiStok::factory()->create([
            'status' => 'approved',
            'barang_id' => $barang->id,
            'gudang_asal_id' => $gudang1->id,
            'gudang_tujuan_id' => $gudang2->id,
            'qty' => 50,
        ]);

        $response = $this->postJson("/api/mutasi-stok/{$mutasiStok->id}/complete");

        $response->assertStatus(422);

        $this->assertDatabaseHas('mutasi_stok', ['id' => $mutasiStok->id, 'status' => 'approved']);
        $this->assertDatabaseCount('kartu_stok', 0);
    }

    public function test_workflow_invalid_transition()
    {
        $this->actingAsAdmin();
        $pending = MutasiStok::factory()->create(['status' => 'pending']);
        $approved = MutasiStok::factory()->create(['status' => 'approved']);

        $this->postJson("/api/mutasi-stok/{$pending->id}/complete")->assertStatus(422);
        $this->postJson("/api/mutasi-stok/{$approved->id}/reject")->assertStatus(422);
    }

    public function test_destroy_completed_blocked()
    {
        $this->actingAsAdmin();
        $mutasiStok = MutasiStok::factory()->create(['status' => 'completed']);

        $response = $this->deleteJson("/api/mutasi-stok/{$mutasiStok->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('mutasi_stok', ['id' => $mutasiStok->id]);
    }
}
