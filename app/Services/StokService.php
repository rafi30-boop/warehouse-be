<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangKeluarDetail;
use App\Models\BarangMasuk;
use App\Models\BarangMasukDetail;
use App\Models\KartuStok;
use App\Models\MutasiStok;
use App\Models\StokOpname;

class StokService extends BaseService
{
    public function hitungSaldoStok(int $barangId, ?int $gudangId = null, ?int $kecualiMutasiId = null): float
    {
        $masuk = BarangMasukDetail::where('barang_id', $barangId)
            ->whereHas('barangMasuk', function ($q) use ($gudangId) {
                $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                    ->where('status', 'approved');
            })
            ->sum('qty');

        $keluar = BarangKeluarDetail::where('barang_id', $barangId)
            ->whereHas('barangKeluar', function ($q) use ($gudangId) {
                $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                    ->where('status', 'delivered');
            })
            ->sum('qty');

        $masukMutasi = MutasiStok::where('barang_id', $barangId)
            ->when($gudangId, fn ($q) => $q->where('gudang_tujuan_id', $gudangId))
            ->where('status', 'completed')
            ->when($kecualiMutasiId, fn ($q) => $q->where('id', '!=', $kecualiMutasiId))
            ->sum('qty');

        $keluarMutasi = MutasiStok::where('barang_id', $barangId)
            ->when($gudangId, fn ($q) => $q->where('gudang_asal_id', $gudangId))
            ->where('status', 'completed')
            ->when($kecualiMutasiId, fn ($q) => $q->where('id', '!=', $kecualiMutasiId))
            ->sum('qty');

        return ($masuk + $masukMutasi) - ($keluar + $keluarMutasi);
    }

    public function hitungSaldoStokBatch(array $barangIds, ?int $gudangId = null): array
    {
        $results = [];

        $masukData = BarangMasukDetail::whereIn('barang_id', $barangIds)
            ->whereHas('barangMasuk', function ($q) use ($gudangId) {
                $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                    ->where('status', 'approved');
            })
            ->selectRaw('barang_id, SUM(qty) as total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        $keluarData = BarangKeluarDetail::whereIn('barang_id', $barangIds)
            ->whereHas('barangKeluar', function ($q) use ($gudangId) {
                $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                    ->where('status', 'delivered');
            })
            ->selectRaw('barang_id, SUM(qty) as total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        $masukMutasi = MutasiStok::whereIn('barang_id', $barangIds)
            ->when($gudangId, fn ($q) => $q->where('gudang_tujuan_id', $gudangId))
            ->where('status', 'completed')
            ->selectRaw('barang_id, SUM(qty) as total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        $keluarMutasi = MutasiStok::whereIn('barang_id', $barangIds)
            ->when($gudangId, fn ($q) => $q->where('gudang_asal_id', $gudangId))
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

    public function stokTersedia(int $barangId, ?int $gudangId = null): float
    {
        return max(0, $this->hitungSaldoStok($barangId, $gudangId));
    }

    public function cekStokCukup(int $barangId, float $qty, ?int $gudangId = null): bool
    {
        return $this->stokTersedia($barangId, $gudangId) >= $qty;
    }

    public function validasiStokDetail(array $details, ?int $gudangId = null): array
    {
        $errors = [];

        foreach ($details as $index => $detail) {
            $barangId = (int) ($detail['barang_id'] ?? 0);
            $qty = (float) ($detail['qty'] ?? 0);

            if ($barangId && ! $this->cekStokCukup($barangId, $qty, $gudangId)) {
                $barang = Barang::find($barangId);
                $nama = $barang->nama ?? "ID {$barangId}";
                $errors["details.{$index}.qty"] = [
                    "Stok {$nama} tidak mencukupi (tersedia: {$this->stokTersedia($barangId, $gudangId)})",
                ];
            }
        }

        return $errors;
    }

    public function riwayatKartuStok(int $barangId, ?int $gudangId = null, ?string $from = null, ?string $to = null): array
    {
        $rows = [];

        BarangMasukDetail::with('barangMasuk')
            ->where('barang_id', $barangId)
            ->whereHas('barangMasuk', function ($q) use ($gudangId, $from, $to) {
                $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                    ->where('status', 'approved');
                if ($from) {
                    $q->whereDate('tanggal', '>=', $from);
                }
                if ($to) {
                    $q->whereDate('tanggal', '<=', $to);
                }
            })
            ->orderByDesc('id')
            ->get()
            ->each(function ($detail) use (&$rows) {
                $rows[] = [
                    'tanggal' => $detail->barangMasuk->tanggal,
                    'tipe' => 'in',
                    'referensi' => $detail->barangMasuk->no_referensi,
                    'gudang_id' => $detail->barangMasuk->gudang_id,
                    'lokasi_rak_id' => $detail->lokasi_rak_id,
                    'qty' => (float) $detail->qty,
                    'keterangan' => 'Barang Masuk',
                ];
            });

        BarangKeluarDetail::with('barangKeluar')
            ->where('barang_id', $barangId)
            ->whereHas('barangKeluar', function ($q) use ($gudangId, $from, $to) {
                $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                    ->where('status', 'delivered');
                if ($from) {
                    $q->whereDate('tanggal', '>=', $from);
                }
                if ($to) {
                    $q->whereDate('tanggal', '<=', $to);
                }
            })
            ->orderByDesc('id')
            ->get()
            ->each(function ($detail) use (&$rows) {
                $rows[] = [
                    'tanggal' => $detail->barangKeluar->tanggal,
                    'tipe' => 'out',
                    'referensi' => $detail->barangKeluar->no_referensi,
                    'gudang_id' => $detail->barangKeluar->gudang_id,
                    'lokasi_rak_id' => $detail->lokasi_rak_id,
                    'qty' => (float) $detail->qty,
                    'keterangan' => 'Barang Keluar',
                ];
            });

        MutasiStok::where('barang_id', $barangId)
            ->when($gudangId, function ($q) use ($gudangId) {
                $q->where('gudang_asal_id', $gudangId)->orWhere('gudang_tujuan_id', $gudangId);
            })
            ->where('status', 'completed')
            ->when($from, fn ($q) => $q->whereDate('tanggal', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('tanggal', '<=', $to))
            ->orderByDesc('id')
            ->get()
            ->each(function ($mutasi) use (&$rows) {
                $rows[] = [
                    'tanggal' => $mutasi->tanggal,
                    'tipe' => 'mutasi_out',
                    'referensi' => $mutasi->no_referensi,
                    'gudang_id' => $mutasi->gudang_asal_id,
                    'lokasi_rak_id' => $mutasi->lokasi_rak_asal_id,
                    'qty' => (float) $mutasi->qty,
                    'keterangan' => 'Mutasi keluar',
                ];
                $rows[] = [
                    'tanggal' => $mutasi->tanggal,
                    'tipe' => 'mutasi_in',
                    'referensi' => $mutasi->no_referensi,
                    'gudang_id' => $mutasi->gudang_tujuan_id,
                    'lokasi_rak_id' => $mutasi->lokasi_rak_tujuan_id,
                    'qty' => (float) $mutasi->qty,
                    'keterangan' => 'Mutasi masuk',
                ];
            });

        usort($rows, fn ($a, $b) => strcmp($b['tanggal'], $a['tanggal']));

        return $rows;
    }

    public function catatBarangMasuk(BarangMasuk $doc, array $saldoAwal, int $userId): void
    {
        $this->catatDetailRows($doc, 'in', 'Barang Masuk', $saldoAwal, $userId);
    }

    public function catatBarangKeluar(BarangKeluar $doc, array $saldoAwal, int $userId): void
    {
        $this->catatDetailRows($doc, 'out', 'Barang Keluar', $saldoAwal, $userId);
    }

    private function catatDetailRows(BarangMasuk|BarangKeluar $doc, string $tipe, string $keterangan, array $saldoAwal, int $userId): void
    {
        $saldo = $saldoAwal;

        foreach ($doc->details as $detail) {
            $barangId = (int) $detail->barang_id;
            $qty = (float) $detail->qty;
            $sebelum = $saldo[$barangId] ?? $this->hitungSaldoStok($barangId, $doc->gudang_id);
            $sesudah = $tipe === 'out' ? $sebelum - $qty : $sebelum + $qty;
            $saldo[$barangId] = $sesudah;

            KartuStok::create([
                'barang_id' => $barangId,
                'gudang_id' => $doc->gudang_id,
                'lokasi_rak_id' => $detail->lokasi_rak_id,
                'tipe' => $tipe,
                'qty' => $qty,
                'saldo_sebelum' => $sebelum,
                'saldo_sesudah' => $sesudah,
                'referensi_type' => $tipe === 'out' ? BarangKeluar::class : BarangMasuk::class,
                'referensi_id' => $doc->id,
                'keterangan' => $keterangan,
                'created_by' => $userId,
            ]);
        }
    }

    public function catatMutasiStok(MutasiStok $mutasi, int $userId): void
    {
        $qty = (float) $mutasi->qty;
        $saldoAsal = $this->hitungSaldoStok($mutasi->barang_id, $mutasi->gudang_asal_id, $mutasi->id);
        $saldoTujuan = $this->hitungSaldoStok($mutasi->barang_id, $mutasi->gudang_tujuan_id, $mutasi->id);

        KartuStok::create([
            'barang_id' => $mutasi->barang_id,
            'gudang_id' => $mutasi->gudang_asal_id,
            'lokasi_rak_id' => $mutasi->lokasi_rak_asal_id,
            'tipe' => 'mutasi_out',
            'qty' => $qty,
            'saldo_sebelum' => $saldoAsal,
            'saldo_sesudah' => $saldoAsal - $qty,
            'referensi_type' => MutasiStok::class,
            'referensi_id' => $mutasi->id,
            'keterangan' => 'Mutasi stok keluar',
            'created_by' => $userId,
        ]);

        KartuStok::create([
            'barang_id' => $mutasi->barang_id,
            'gudang_id' => $mutasi->gudang_tujuan_id,
            'lokasi_rak_id' => $mutasi->lokasi_rak_tujuan_id,
            'tipe' => 'mutasi_in',
            'qty' => $qty,
            'saldo_sebelum' => $saldoTujuan,
            'saldo_sesudah' => $saldoTujuan + $qty,
            'referensi_type' => MutasiStok::class,
            'referensi_id' => $mutasi->id,
            'keterangan' => 'Mutasi stok masuk',
            'created_by' => $userId,
        ]);
    }

    public function catatStokOpname(StokOpname $doc, int $userId): void
    {
        foreach ($doc->details as $detail) {
            $selisih = (float) $detail->selisih;

            if (abs($selisih) < 0.005) {
                continue;
            }

            KartuStok::create([
                'barang_id' => $detail->barang_id,
                'gudang_id' => $doc->gudang_id,
                'lokasi_rak_id' => $detail->lokasi_rak_id,
                'tipe' => 'opname',
                'qty' => $selisih,
                'saldo_sebelum' => (float) $detail->stok_sistem,
                'saldo_sesudah' => (float) $detail->stok_fisik,
                'referensi_type' => StokOpname::class,
                'referensi_id' => $doc->id,
                'keterangan' => 'Stok opname',
                'created_by' => $userId,
            ]);
        }
    }
}
