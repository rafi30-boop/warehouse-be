<?php

namespace App\Services;

class StokService extends BaseService
{
    public function hitungSaldoStok(int $barangId, ?int $gudangId = null): float
    {
        $masuk = \App\Models\BarangMasukDetail::where('barang_id', $barangId)
            ->whereHas('barangMasuk', function ($q) use ($gudangId) {
                $q->when($gudangId, fn($q) => $q->where('gudang_id', $gudangId))
                  ->where('status', 'approved');
            })
            ->sum('qty');

        $keluar = \App\Models\BarangKeluarDetail::where('barang_id', $barangId)
            ->whereHas('barangKeluar', function ($q) use ($gudangId) {
                $q->when($gudangId, fn($q) => $q->where('gudang_id', $gudangId))
                  ->where('status', 'delivered');
            })
            ->sum('qty');

        $masukMutasi = \App\Models\MutasiStok::where('barang_id', $barangId)
            ->when($gudangId, fn($q) => $q->where('gudang_tujuan_id', $gudangId))
            ->where('status', 'completed')
            ->sum('qty');

        $keluarMutasi = \App\Models\MutasiStok::where('barang_id', $barangId)
            ->when($gudangId, fn($q) => $q->where('gudang_asal_id', $gudangId))
            ->where('status', 'completed')
            ->sum('qty');

        return ($masuk + $masukMutasi) - ($keluar + $keluarMutasi);
    }

    public function hitungSaldoStokBatch(array $barangIds, ?int $gudangId = null): array
    {
        $results = [];

        $masukData = \App\Models\BarangMasukDetail::whereIn('barang_id', $barangIds)
            ->whereHas('barangMasuk', function ($q) use ($gudangId) {
                $q->when($gudangId, fn($q) => $q->where('gudang_id', $gudangId))
                  ->where('status', 'approved');
            })
            ->selectRaw('barang_id, SUM(qty) as total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        $keluarData = \App\Models\BarangKeluarDetail::whereIn('barang_id', $barangIds)
            ->whereHas('barangKeluar', function ($q) use ($gudangId) {
                $q->when($gudangId, fn($q) => $q->where('gudang_id', $gudangId))
                  ->where('status', 'delivered');
            })
            ->selectRaw('barang_id, SUM(qty) as total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        $masukMutasi = \App\Models\MutasiStok::whereIn('barang_id', $barangIds)
            ->when($gudangId, fn($q) => $q->where('gudang_tujuan_id', $gudangId))
            ->where('status', 'completed')
            ->selectRaw('barang_id, SUM(qty) as total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        $keluarMutasi = \App\Models\MutasiStok::whereIn('barang_id', $barangIds)
            ->when($gudangId, fn($q) => $q->where('gudang_asal_id', $gudangId))
            ->where('status', 'completed')
            ->selectRaw('barang_id, SUM(qty) as total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        foreach ($barangIds as $id) {
            $masuk = (float) ($masukData[$id] ?? 0);
            $keluar = (float) ($keluarData[$id] ?? 0);
            $masukM = (float) ($masukMutasi[$id] ?? 0);
            $keluarM = (float) ($keluarMutasi[$id] ?? 0);
            $results[$id] = ($masuk + $masukM) - ($keluar + $keluarM);
        }

        return $results;
    }
}