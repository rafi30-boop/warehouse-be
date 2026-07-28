<?php

namespace App\Services;

class StokService extends BaseService
{
    public function hitungSaldoStok(int $barangId, int $gudangId): float
    {
        $masuk = \App\Models\BarangMasukDetail::where('barang_id', $barangId)
            ->whereHas('barangMasuk', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId)->where('status', 'approved');
            })
            ->sum('qty');

        $keluar = \App\Models\BarangKeluarDetail::where('barang_id', $barangId)
            ->whereHas('barangKeluar', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId)->where('status', 'delivered');
            })
            ->sum('qty');

        $masukMutasi = \App\Models\MutasiStok::where('barang_id', $barangId)
            ->where('gudang_tujuan_id', $gudangId)
            ->where('status', 'completed')
            ->sum('qty');

        $keluarMutasi = \App\Models\MutasiStok::where('barang_id', $barangId)
            ->where('gudang_asal_id', $gudangId)
            ->where('status', 'completed')
            ->sum('qty');

        return ($masuk + $masukMutasi) - ($keluar + $keluarMutasi);
    }
}