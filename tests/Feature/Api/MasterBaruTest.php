<?php

namespace Tests\Feature\Api;

use App\Models\AktivitasLog;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\Gudang;
use App\Models\Notifikasi;
use App\Models\Satuan;
use App\Models\Shift;

class MasterBaruTest extends ApiTestCase
{
    public function test_satuan_crud()
    {
        $this->actingAsAdmin();

        $this->getJson('/api/satuan')->assertOk();

        $store = $this->postJson('/api/satuan', ['nama' => 'Kilogram', 'singkatan' => 'kg']);
        $store->assertCreated()->assertJsonPath('data.nama', 'Kilogram');
        $id = $store->json('data.id');

        $this->getJson("/api/satuan/{$id}")->assertOk();

        $this->putJson("/api/satuan/{$id}", ['nama' => 'Kilogram Baru'])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Kilogram Baru');

        $this->deleteJson("/api/satuan/{$id}")->assertOk();
        $this->assertDatabaseMissing('satuan', ['id' => $id]);
    }

    public function test_satuan_validation_error()
    {
        $this->actingAsAdmin();
        $this->postJson('/api/satuan', [])->assertStatus(422);
    }

    public function test_lokasi_rak_crud()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();

        $store = $this->postJson('/api/lokasi-rak', [
            'gudang_id' => $gudang->id,
            'kode_rak' => 'RAK-A1',
            'zona' => 'Zona A',
            'kapasitas' => 100,
            'status' => 'aktif',
        ]);
        $store->assertCreated()->assertJsonPath('data.kode_rak', 'RAK-A1');
        $id = $store->json('data.id');

        $this->getJson('/api/lokasi-rak?gudang_id='.$gudang->id)->assertOk();
        $this->getJson("/api/lokasi-rak/{$id}")->assertOk();

        $this->putJson("/api/lokasi-rak/{$id}", ['status' => 'penuh'])
            ->assertOk()
            ->assertJsonPath('data.status', 'penuh');

        $this->deleteJson("/api/lokasi-rak/{$id}")->assertOk();
    }

    public function test_lokasi_rak_kode_unique_per_gudang()
    {
        $this->actingAsAdmin();
        $gudang1 = Gudang::factory()->create();
        $gudang2 = Gudang::factory()->create();

        $this->postJson('/api/lokasi-rak', ['gudang_id' => $gudang1->id, 'kode_rak' => 'RAK-X'])->assertCreated();

        $this->postJson('/api/lokasi-rak', ['gudang_id' => $gudang1->id, 'kode_rak' => 'RAK-X'])
            ->assertStatus(422);

        $this->postJson('/api/lokasi-rak', ['gudang_id' => $gudang2->id, 'kode_rak' => 'RAK-X'])->assertCreated();
    }

    public function test_batch_barang_crud()
    {
        $this->actingAsAdmin();
        $barang = Barang::factory()->create();

        $store = $this->postJson('/api/batch-barang', [
            'barang_id' => $barang->id,
            'batch_number' => 'BATCH-001',
            'expired_date' => '2027-01-01',
            'qty' => 50,
        ]);
        $store->assertCreated()->assertJsonPath('data.batch_number', 'BATCH-001');
        $id = $store->json('data.id');

        $this->getJson('/api/batch-barang?barang_id='.$barang->id)->assertOk();

        $this->putJson("/api/batch-barang/{$id}", ['qty' => 30])
            ->assertOk()
            ->assertJsonPath('data.qty', 30);

        $this->deleteJson("/api/batch-barang/{$id}")->assertOk();
    }

    public function test_history_harga_crud()
    {
        $this->actingAsAdmin();
        $barang = Barang::factory()->create();

        $store = $this->postJson('/api/history-harga', [
            'barang_id' => $barang->id,
            'harga_beli' => 9000,
            'harga_jual' => 14000,
            'tanggal_efektif' => '2026-08-04',
        ]);
        $store->assertCreated()->assertJsonPath('data.harga_jual', 14000);
        $id = $store->json('data.id');

        $this->getJson('/api/history-harga?barang_id='.$barang->id)->assertOk();
        $this->getJson("/api/history-harga/{$id}")->assertOk();
        $this->deleteJson("/api/history-harga/{$id}")->assertOk();
    }

    public function test_jadwal_petugas_crud()
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $store = $this->postJson('/api/jadwal-petugas', [
            'user_id' => $this->operatorUser->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-08-05',
        ]);
        $store->assertCreated()->assertJsonPath('data.tanggal', '2026-08-05');
        $id = $store->json('data.id');

        $this->getJson('/api/jadwal-petugas?user_id='.$this->operatorUser->id)->assertOk();

        $this->putJson("/api/jadwal-petugas/{$id}", ['tanggal' => '2026-08-06'])
            ->assertOk()
            ->assertJsonPath('data.tanggal', '2026-08-06');
    }

    public function test_notifikasi_read_flow()
    {
        $this->actingAsAdmin();
        Notifikasi::factory()->count(2)->create(['user_id' => $this->adminUser->id, 'is_read' => false]);

        $this->getJson('/api/notifikasi?is_read=false')->assertOk();

        $notif = Notifikasi::where('user_id', $this->adminUser->id)->first();
        $this->postJson("/api/notifikasi/{$notif->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->postJson('/api/notifikasi/read-all')
            ->assertOk();

        $this->assertSame(0, Notifikasi::where('user_id', $this->adminUser->id)->where('is_read', false)->count());

        $this->deleteJson("/api/notifikasi/{$notif->id}")->assertOk();
    }

    public function test_aktivitas_log_index_show()
    {
        $this->actingAsAdmin();
        AktivitasLog::factory()->count(3)->create(['user_id' => $this->adminUser->id]);

        $this->getJson('/api/aktivitas-log')->assertOk();

        $log = AktivitasLog::first();
        $this->getJson("/api/aktivitas-log/{$log->id}")->assertOk();

        $this->deleteJson("/api/aktivitas-log/{$log->id}")->assertOk();
        $this->assertDatabaseMissing('aktivitas_log', ['id' => $log->id]);
    }

    public function test_kartu_stok_index_and_riwayat()
    {
        $this->actingAsAdmin();
        $gudang = Gudang::factory()->create();
        $barang = Barang::factory()->create();

        $barangMasuk = BarangMasuk::factory()->create(['gudang_id' => $gudang->id, 'status' => 'approved']);
        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => 7,
            'harga_satuan' => 1000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 7000,
        ]);

        $this->getJson('/api/kartu-stok')->assertOk();

        $this->getJson('/api/kartu-stok/riwayat?barang_id='.$barang->id.'&gudang_id='.$gudang->id)
            ->assertOk()
            ->assertJsonPath('data.0.tipe', 'in')
            ->assertJsonPath('data.0.qty', 7);
    }

    public function test_operator_cannot_delete_satuan()
    {
        $this->actingAsOperator();
        $satuan = Satuan::factory()->create();

        $response = $this->deleteJson("/api/satuan/{$satuan->id}");
        $response->assertStatus(403);
    }

    public function test_new_entities_unauthenticated()
    {
        $this->getJson('/api/satuan')->assertStatus(401);
        $this->getJson('/api/lokasi-rak')->assertStatus(401);
        $this->getJson('/api/kartu-stok')->assertStatus(401);
        $this->getJson('/api/notifikasi')->assertStatus(401);
        $this->getJson('/api/aktivitas-log')->assertStatus(401);
        $this->getJson('/api/batch-barang')->assertStatus(401);
        $this->getJson('/api/history-harga')->assertStatus(401);
        $this->getJson('/api/jadwal-petugas')->assertStatus(401);
    }
}
