<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\Gudang;
use App\Models\MutasiStok;
use App\Models\User;
use Tests\Feature\Api\ApiTestCase;

class BackfillKartuStokTest extends ApiTestCase
{
    public function test_backfill_reconstructs_kartu_stok()
    {
        $user = User::factory()->create();
        $barang = Barang::factory()->create();
        $gudang = Gudang::factory()->create();

        $barangMasuk = BarangMasuk::factory()->create([
            'gudang_id' => $gudang->id,
            'status' => 'approved',
            'tanggal' => '2026-07-01',
            'created_by' => $user->id,
        ]);
        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => 10,
            'harga_satuan' => 10000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 100000,
        ]);

        $barangKeluar = BarangKeluar::factory()->create([
            'gudang_id' => $gudang->id,
            'status' => 'delivered',
            'tanggal' => '2026-07-02',
            'created_by' => $user->id,
        ]);
        $barangKeluar->details()->create([
            'barang_id' => $barang->id,
            'qty' => 4,
            'harga_satuan' => 15000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 60000,
        ]);

        $this->artisan('stok:kartu-backfill')
            ->expectsOutputToContain('Berhasil mengisi 2 baris kartu stok.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('kartu_stok', [
            'barang_id' => $barang->id,
            'gudang_id' => $gudang->id,
            'tipe' => 'in',
            'qty' => 10,
            'saldo_sebelum' => 0,
            'saldo_sesudah' => 10,
            'referensi_type' => BarangMasuk::class,
            'referensi_id' => $barangMasuk->id,
        ]);

        $this->assertDatabaseHas('kartu_stok', [
            'barang_id' => $barang->id,
            'gudang_id' => $gudang->id,
            'tipe' => 'out',
            'qty' => -4,
            'saldo_sebelum' => 10,
            'saldo_sesudah' => 6,
            'referensi_type' => BarangKeluar::class,
            'referensi_id' => $barangKeluar->id,
        ]);

        $this->assertDatabaseHas('aktivitas_log', [
            'action' => 'backfill',
            'model' => 'KartuStok',
        ]);
    }

    public function test_backfill_refuses_when_data_exists()
    {
        $barang = Barang::factory()->create();
        $gudang = Gudang::factory()->create();
        $user = User::factory()->create();

        $barangMasuk = BarangMasuk::factory()->create([
            'gudang_id' => $gudang->id,
            'status' => 'approved',
            'created_by' => $user->id,
        ]);
        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => 5,
            'harga_satuan' => 10000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 50000,
        ]);

        $this->artisan('stok:kartu-backfill')->assertExitCode(0);
        $this->assertDatabaseCount('kartu_stok', 1);

        $this->artisan('stok:kartu-backfill')->assertExitCode(1);
        $this->assertDatabaseCount('kartu_stok', 1);
    }

    public function test_backfill_force_rebuilds()
    {
        $barang = Barang::factory()->create();
        $gudang1 = Gudang::factory()->create();
        $gudang2 = Gudang::factory()->create();
        $user = User::factory()->create();

        $barangMasuk = BarangMasuk::factory()->create([
            'gudang_id' => $gudang1->id,
            'status' => 'approved',
            'tanggal' => '2026-07-01',
            'created_by' => $user->id,
        ]);
        $barangMasuk->details()->create([
            'barang_id' => $barang->id,
            'qty' => 8,
            'harga_satuan' => 10000,
            'diskon' => 0,
            'pajak' => 0,
            'subtotal' => 80000,
        ]);

        MutasiStok::factory()->create([
            'barang_id' => $barang->id,
            'gudang_asal_id' => $gudang1->id,
            'gudang_tujuan_id' => $gudang2->id,
            'qty' => 3,
            'status' => 'completed',
            'tanggal' => '2026-07-03',
            'created_by' => $user->id,
        ]);

        $this->artisan('stok:kartu-backfill')->assertExitCode(0);
        $this->assertDatabaseCount('kartu_stok', 3);

        $this->artisan('stok:kartu-backfill', ['--force' => true])->assertExitCode(0);
        $this->assertDatabaseCount('kartu_stok', 3);

        $this->assertDatabaseHas('kartu_stok', [
            'barang_id' => $barang->id,
            'gudang_id' => $gudang2->id,
            'tipe' => 'mutasi_in',
            'qty' => 3,
            'saldo_sebelum' => 0,
            'saldo_sesudah' => 3,
        ]);
    }
}
