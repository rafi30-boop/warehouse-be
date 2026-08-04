<?php

namespace App\Console\Commands;

use App\Models\AktivitasLog;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\KartuStok;
use App\Models\MutasiStok;
use App\Models\StokOpname;
use Illuminate\Console\Command;

class BackfillKartuStok extends Command
{
    protected $signature = 'stok:kartu-backfill {--force : Hitung ulang dan hapus data kartu_stok yang sudah ada}';

    protected $description = 'Isi ulang kartu_stok dari dokumen transaksi yang sudah approved/delivered/completed';

    public function handle(): int
    {
        if (KartuStok::exists() && ! $this->option('force')) {
            $this->error('kartu_stok sudah memiliki data. Gunakan --force untuk menghitung ulang (data lama akan dihapus).');

            return self::FAILURE;
        }

        if ($this->option('force')) {
            KartuStok::query()->delete();
        }

        $events = $this->collectEvents();

        usort($events, fn ($a, $b) => strcmp($a['sort'], $b['sort']));

        $saldo = [];
        $inserted = 0;

        foreach ($events as $event) {
            $key = $event['barang_id'].':'.($event['gudang_id'] ?? 0);
            $sebelum = $saldo[$key] ?? 0;
            $sesudah = $event['tipe'] === 'opname' ? $event['saldo_sesudah'] : $sebelum + $event['qty'];

            KartuStok::create([
                'barang_id' => $event['barang_id'],
                'gudang_id' => $event['gudang_id'],
                'lokasi_rak_id' => $event['lokasi_rak_id'],
                'tipe' => $event['tipe'],
                'qty' => $event['qty'],
                'saldo_sebelum' => $sebelum,
                'saldo_sesudah' => $sesudah,
                'referensi_type' => $event['referensi_type'],
                'referensi_id' => $event['referensi_id'],
                'keterangan' => $event['keterangan'],
                'created_by' => $event['created_by'],
            ]);

            if ($event['tipe'] !== 'opname') {
                $saldo[$key] = $sesudah;
            }

            $inserted++;
        }

        AktivitasLog::create([
            'user_id' => null,
            'action' => 'backfill',
            'model' => 'KartuStok',
            'data_new' => ['inserted' => $inserted],
        ]);

        $this->info("Berhasil mengisi {$inserted} baris kartu stok.");

        return self::SUCCESS;
    }

    private function collectEvents(): array
    {
        $events = [];

        BarangMasuk::with('details')->where('status', 'approved')->get()->each(function (BarangMasuk $bm) use (&$events) {
            foreach ($bm->details as $detail) {
                $events[] = [
                    'sort' => $bm->tanggal.'|'.$bm->id,
                    'barang_id' => $detail->barang_id,
                    'gudang_id' => $bm->gudang_id,
                    'lokasi_rak_id' => $detail->lokasi_rak_id,
                    'qty' => (float) $detail->qty,
                    'tipe' => 'in',
                    'saldo_sesudah' => 0,
                    'referensi_type' => BarangMasuk::class,
                    'referensi_id' => $bm->id,
                    'keterangan' => 'Barang Masuk',
                    'created_by' => $bm->created_by,
                ];
            }
        });

        BarangKeluar::with('details')->where('status', 'delivered')->get()->each(function (BarangKeluar $bk) use (&$events) {
            foreach ($bk->details as $detail) {
                $events[] = [
                    'sort' => $bk->tanggal.'|'.$bk->id,
                    'barang_id' => $detail->barang_id,
                    'gudang_id' => $bk->gudang_id,
                    'lokasi_rak_id' => $detail->lokasi_rak_id,
                    'qty' => -1 * (float) $detail->qty,
                    'tipe' => 'out',
                    'saldo_sesudah' => 0,
                    'referensi_type' => BarangKeluar::class,
                    'referensi_id' => $bk->id,
                    'keterangan' => 'Barang Keluar',
                    'created_by' => $bk->created_by,
                ];
            }
        });

        MutasiStok::where('status', 'completed')->get()->each(function (MutasiStok $mutasi) use (&$events) {
            $qty = (float) $mutasi->qty;

            $events[] = [
                'sort' => $mutasi->tanggal.'|'.$mutasi->id,
                'barang_id' => $mutasi->barang_id,
                'gudang_id' => $mutasi->gudang_asal_id,
                'lokasi_rak_id' => $mutasi->lokasi_rak_asal_id,
                'qty' => -1 * $qty,
                'tipe' => 'mutasi_out',
                'saldo_sesudah' => 0,
                'referensi_type' => MutasiStok::class,
                'referensi_id' => $mutasi->id,
                'keterangan' => 'Mutasi stok keluar',
                'created_by' => $mutasi->created_by,
            ];

            $events[] = [
                'sort' => $mutasi->tanggal.'|'.$mutasi->id,
                'barang_id' => $mutasi->barang_id,
                'gudang_id' => $mutasi->gudang_tujuan_id,
                'lokasi_rak_id' => $mutasi->lokasi_rak_tujuan_id,
                'qty' => $qty,
                'tipe' => 'mutasi_in',
                'saldo_sesudah' => 0,
                'referensi_type' => MutasiStok::class,
                'referensi_id' => $mutasi->id,
                'keterangan' => 'Mutasi stok masuk',
                'created_by' => $mutasi->created_by,
            ];
        });

        StokOpname::with('details')->where('status', 'completed')->get()->each(function (StokOpname $opname) use (&$events) {
            foreach ($opname->details as $detail) {
                $selisih = (float) $detail->selisih;

                if (abs($selisih) < 0.005) {
                    continue;
                }

                $events[] = [
                    'sort' => $opname->tanggal.'|'.$opname->id,
                    'barang_id' => $detail->barang_id,
                    'gudang_id' => $opname->gudang_id,
                    'lokasi_rak_id' => $detail->lokasi_rak_id,
                    'qty' => $selisih,
                    'tipe' => 'opname',
                    'saldo_sesudah' => (float) $detail->stok_fisik,
                    'referensi_type' => StokOpname::class,
                    'referensi_id' => $opname->id,
                    'keterangan' => 'Stok opname',
                    'created_by' => $opname->created_by,
                ];
            }
        });

        return $events;
    }
}
