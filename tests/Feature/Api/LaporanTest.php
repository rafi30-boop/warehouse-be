<?php

namespace Tests\Feature\Api;

class LaporanTest extends ApiTestCase
{
    public function test_laporan_stok()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/laporan/stok');
        $response->assertOk();
    }

    public function test_laporan_barang_masuk()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/laporan/barang-masuk');
        $response->assertOk();
    }

    public function test_laporan_barang_keluar()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/laporan/barang-keluar');
        $response->assertOk();
    }

    public function test_laporan_mutasi_stok()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/laporan/mutasi-stok');
        $response->assertOk();
    }

    public function test_laporan_stok_opname()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/laporan/stok-opname');
        $response->assertOk();
    }

    public function test_laporan_absensi()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/laporan/absensi');
        $response->assertOk();
    }

    public function test_unauthenticated_cannot_access_laporan()
    {
        $response = $this->getJson('/api/laporan/stok');
        $response->assertStatus(401);
    }
}