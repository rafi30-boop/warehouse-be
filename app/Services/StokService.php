<?php

namespace App\Services;

class StokService extends BaseService
{
    public function hitungSaldoStok(int $barangId, int $gudangId): int
    {
        $masuk = \App\Models\BarangMasuk::where('barang_id', $barangId)
            ->where('gudang_id', $gudangId)
            ->sum('jumlah');

        $keluar = \App\Models\BarangKeluar::where('barang_id', $barangId)
            ->where('gudang_id', $gudangId)
            ->sum('jumlah');

        $masukMutasi = \App\Models\MutasiStok::where('barang_id', $barangId)
            ->where('gudang_tujuan_id', $gudangId)
            ->sum('jumlah');

        $keluarMutasi = \App\Models\MutasiStok::where('barang_id', $barangId)
            ->where('gudang_asal_id', $gudangId)
            ->sum('jumlah');

        return ($masuk + $masukMutasi) - ($keluar + $keluarMutasi);
    }
}
