<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\AktivitasLog;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangKeluarDetail;
use App\Models\BarangMasuk;
use App\Models\BarangMasukDetail;
use App\Models\Gudang;
use App\Models\IzinRequest;
use App\Models\KartuStok;
use App\Models\LokasiRak;
use App\Models\MutasiStok;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DashboardService extends BaseService
{
    public function __construct(private StokService $stokService)
    {
    }

    /**
     * $user dipakai untuk strip field sensitif berbasis permission.
     * null = tanpa strip (pemakaian console/internal).
     */
    public function getDashboardData(?int $gudangId = null, string $chartRange = '24h', ?User $user = null): array
    {
        return [
            'metrics' => $this->getMetrics($gudangId, $user),
            'chart' => $this->getChartData($gudangId, $chartRange),
            'recent_activity' => $this->getRecentActivity($gudangId, $user),
            'alerts' => $this->getAlerts($gudangId, $user),
            'warehouse_capacity' => $this->getWarehouseCapacity($gudangId),
            'attendance_today' => $this->getAttendanceToday($gudangId, $user),
        ];
    }

    private function can(?User $user, string ...$permissions): bool
    {
        if ($user === null) {
            return true;
        }
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }
        return false;
    }

    private function getMetrics(?int $gudangId, ?User $user = null): array
    {
        $totalGudang = $gudangId ? 1 : Gudang::count();

        $allBarangIds = Barang::where('status', 'aktif')->pluck('id')->toArray();
        $saldoMap = $this->stokService->hitungSaldoStokBatch($allBarangIds, $gudangId);

        $totalBarang = 0;
        $totalNilaiStok = 0;
        $barangMap = Barang::whereIn('id', $allBarangIds)->get()->keyBy('id');
        foreach ($saldoMap as $barangId => $stok) {
            if ($stok > 0) {
                $totalBarang++;
                $barang = $barangMap[$barangId] ?? null;
                if ($barang) {
                    $totalNilaiStok += $stok * ($barang->harga_beli ?? 0);
                }
            }
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $masukQuery = BarangMasuk::query();
        if ($gudangId) {
            $masukQuery->where('gudang_id', $gudangId);
        }
        $masukQuery->where('status', 'approved')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth]);

        $masukIds = $masukQuery->pluck('id');
        $masukCount = $masukIds->count();
        $masukQty = BarangMasukDetail::whereIn('barang_masuk_id', $masukIds)->sum('qty');

        $keluarQuery = BarangKeluar::query();
        if ($gudangId) {
            $keluarQuery->where('gudang_id', $gudangId);
        }
        $keluarQuery->where('status', 'delivered')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth]);

        $keluarIds = $keluarQuery->pluck('id');
        $keluarCount = $keluarIds->count();
        $keluarQty = BarangKeluarDetail::whereIn('barang_keluar_id', $keluarIds)->sum('qty');

        $pendingMasuk = BarangMasuk::query();
        if ($gudangId) {
            $pendingMasuk->where('gudang_id', $gudangId);
        }
        $pendingMasukCount = $pendingMasuk->where('status', 'pending')->count();

        $pendingKeluar = BarangKeluar::query();
        if ($gudangId) {
            $pendingKeluar->where('gudang_id', $gudangId);
        }
        $pendingKeluarCount = $pendingKeluar->where('status', 'pending')->count();

        $pendingMutasi = MutasiStok::query();
        if ($gudangId) {
            $pendingMutasi->where(function ($q) use ($gudangId) {
                $q->where('gudang_asal_id', $gudangId)
                    ->orWhere('gudang_tujuan_id', $gudangId);
            });
        }
        $pendingMutasiCount = $pendingMutasi->where('status', 'pending')->count();

        $pendingApprovals = $pendingMasukCount + $pendingKeluarCount + $pendingMutasiCount;

        $totalStok = array_sum($saldoMap);

        return [
            'total_barang' => $totalBarang,
            'total_stok' => $totalStok,
            // Nilai Rp hanya untuk yang boleh melihat laporan stok.
            'total_nilai_stok' => $this->can($user, 'laporan-stok') ? $totalNilaiStok : null,
            'total_gudang' => $totalGudang,
            'barang_masuk_bulan_ini' => [
                'qty' => $masukQty,
                'count' => $masukCount,
            ],
            'barang_keluar_bulan_ini' => [
                'qty' => $keluarQty,
                'count' => $keluarCount,
            ],
            // Pending approvals hanya relevan bagi yang bisa menyetujui.
            'pending_approvals' => $this->can($user, 'barang-masuk-approve', 'barang-keluar-approve', 'mutasi-stok-approve', 'izin-approve') ? $pendingApprovals : null,
        ];
    }

    private function getChartData(?int $gudangId, string $range): array
    {
        $now = Carbon::now();
        $labels = [];
        $masukData = [];
        $keluarData = [];

        if ($range === '24h') {
            for ($h = 0; $h < 24; $h++) {
                $hour = str_pad((string) $h, 2, '0', STR_PAD_LEFT);
                $labels[] = "{$hour}:00";

                $start = $now->copy()->startOfDay()->addHours($h);
                $end = $start->copy()->addHour();

                $masukCount = BarangMasukDetail::whereHas('barangMasuk', function ($q) use ($gudangId, $start, $end) {
                    $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                        ->where('status', 'approved')
                        ->where('tanggal', '>=', $start)
                        ->where('tanggal', '<', $end);
                })->sum('qty');

                $keluarCount = BarangKeluarDetail::whereHas('barangKeluar', function ($q) use ($gudangId, $start, $end) {
                    $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                        ->where('status', 'delivered')
                        ->where('tanggal', '>=', $start)
                        ->where('tanggal', '<', $end);
                })->sum('qty');

                $masukData[] = (float) $masukCount;
                $keluarData[] = (float) $keluarCount;
            }
        } elseif ($range === '7d') {
            for ($d = 6; $d >= 0; $d--) {
                $date = $now->copy()->subDays($d);
                $labels[] = $date->format('d M');

                $start = $date->copy()->startOfDay();
                $end = $date->copy()->endOfDay();

                $masukCount = BarangMasukDetail::whereHas('barangMasuk', function ($q) use ($gudangId, $start, $end) {
                    $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                        ->where('status', 'approved')
                        ->where('tanggal', '>=', $start)
                        ->where('tanggal', '<=', $end);
                })->sum('qty');

                $keluarCount = BarangKeluarDetail::whereHas('barangKeluar', function ($q) use ($gudangId, $start, $end) {
                    $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                        ->where('status', 'delivered')
                        ->where('tanggal', '>=', $start)
                        ->where('tanggal', '<=', $end);
                })->sum('qty');

                $masukData[] = (float) $masukCount;
                $keluarData[] = (float) $keluarCount;
            }
        } else {
            for ($d = 29; $d >= 0; $d--) {
                $date = $now->copy()->subDays($d);
                $labels[] = $date->format('d M');

                $start = $date->copy()->startOfDay();
                $end = $date->copy()->endOfDay();

                $masukCount = BarangMasukDetail::whereHas('barangMasuk', function ($q) use ($gudangId, $start, $end) {
                    $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                        ->where('status', 'approved')
                        ->where('tanggal', '>=', $start)
                        ->where('tanggal', '<=', $end);
                })->sum('qty');

                $keluarCount = BarangKeluarDetail::whereHas('barangKeluar', function ($q) use ($gudangId, $start, $end) {
                    $q->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                        ->where('status', 'delivered')
                        ->where('tanggal', '>=', $start)
                        ->where('tanggal', '<=', $end);
                })->sum('qty');

                $masukData[] = (float) $masukCount;
                $keluarData[] = (float) $keluarCount;
            }
        }

        return [
            'range' => $range,
            'labels' => $labels,
            'masuk' => $masukData,
            'keluar' => $keluarData,
        ];
    }

    private function getRecentActivity(?int $gudangId, ?User $user = null): array
    {
        // Aktivitas staf lain hanya untuk yang boleh melihat activity log.
        if (! $this->can($user, 'aktivitas-log-list')) {
            return [];
        }

        $query = AktivitasLog::with(['user.roles'])
            ->whereIn('action', ['store', 'approve', 'reject', 'deliver', 'complete', 'scan', 'login', 'start', 'cancel']);

        if ($gudangId) {
            $query->where(function ($q) use ($gudangId) {
                $q->where(function ($q2) use ($gudangId) {
                    $q2->where('model', 'BarangMasuk')
                        ->whereIn('model_id', BarangMasuk::where('gudang_id', $gudangId)->pluck('id'));
                })->orWhere(function ($q2) use ($gudangId) {
                    $q2->where('model', 'BarangKeluar')
                        ->whereIn('model_id', BarangKeluar::where('gudang_id', $gudangId)->pluck('id'));
                })->orWhere(function ($q2) use ($gudangId) {
                    $q2->where('model', 'MutasiStok')
                        ->whereIn('model_id', MutasiStok::where('gudang_asal_id', $gudangId)->orWhere('gudang_tujuan_id', $gudangId)->pluck('id'));
                })->orWhere(function ($q2) use ($gudangId) {
                    $q2->where('model', 'StokOpname')
                        ->whereIn('model_id', StokOpname::where('gudang_id', $gudangId)->pluck('id'));
                })->orWhereIn('model', ['Auth', 'Absensi']);
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                $model = $log->model ?? '';
                $kategori = match (true) {
                    str_contains($model, 'BarangMasuk') => 'Masuk',
                    str_contains($model, 'BarangKeluar') => 'Keluar',
                    str_contains($model, 'MutasiStok') => 'Mutasi',
                    str_contains($model, 'StokOpname') => 'Opname',
                    str_contains($model, 'Absensi') => 'Absensi',
                    default => ucfirst($log->action ?? 'Lainnya'),
                };

                $action = $log->action ?? '-';
                $modelId = $log->model_id ?? '';
                $dataNew = is_array($log->data_new) ? $log->data_new : [];
                $noRef = $dataNew['no_referensi'] ?? $dataNew['kode'] ?? ($modelId ? "#{$modelId}" : '');

                $detail = $log->data_new['catatan'] ?? $action;
                if ($noRef) {
                    $detail .= " [{$noRef}]";
                }
                if (isset($dataNew['total_nilai'])) {
                    $detail .= " Rp " . number_format((float) $dataNew['total_nilai'], 0, ',', '.');
                }

                $status = 'selesai';
                if (isset($dataNew['status'])) {
                    $statusMap = [
                        'pending' => 'pending',
                        'draft' => 'pending',
                        'rejected' => 'revisi',
                        'cancelled' => 'revisi',
                    ];
                    $status = $statusMap[$dataNew['status']] ?? 'selesai';
                }

                return [
                    'id' => $log->id,
                    'waktu' => $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('H:i:s') : '-',
                    'kategori' => $kategori,
                    'petugas' => $log->user?->name ?? '-',
                    'role' => $log->user?->roles?->first()?->name ?? '-',
                    'gudang' => '-',
                    'detail' => $detail,
                    'referensi' => $noRef,
                    'status' => $status,
                ];
            })
            ->toArray();

        return $logs;
    }

    private function getAlerts(?int $gudangId, ?User $user = null): array
    {
        $stokKritis = Barang::where('status', 'aktif')
            ->get()
            ->filter(function ($barang) use ($gudangId) {
                $stok = $this->stokService->hitungSaldoStok($barang->id, $gudangId);
                return $stok < ($barang->min_stok ?? 0);
            })
            ->map(function ($barang) use ($gudangId) {
                $stok = $this->stokService->hitungSaldoStok($barang->id, $gudangId);
                return [
                    'id' => $barang->id,
                    'sku' => $barang->sku,
                    'nama' => $barang->nama,
                    'stok_saat_ini' => $stok,
                    'min_stok' => $barang->min_stok ?? 0,
                ];
            })
            ->values()
            ->toArray();

        $pendingMasuk = BarangMasuk::with(['gudang', 'createdBy'])
            ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
            ->where('status', 'pending')
            ->orderBy('tanggal', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($bm) {
                $qtyTotal = $bm->details->sum('qty');
                return [
                    'id' => $bm->id,
                    'no_referensi' => $bm->no_referensi,
                    'tanggal' => $bm->tanggal,
                    'gudang' => $bm->gudang->nama ?? '-',
                    'petugas' => $bm->createdBy->name ?? '-',
                    'total_qty' => $qtyTotal,
                ];
            })
            ->toArray();

        $pendingOpname = StokOpname::with(['gudang', 'createdBy'])
            ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
            ->whereIn('status', ['draft', 'in_progress'])
            ->orderBy('tanggal', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($so) {
                $hasSelisih = $so->details->contains(function ($detail) {
                    return abs((float) $detail->selisih) > 0.005;
                });
                return [
                    'id' => $so->id,
                    'no_referensi' => $so->no_referensi,
                    'tanggal' => $so->tanggal,
                    'gudang' => $so->gudang->nama ?? '-',
                    'petugas' => $so->createdBy->name ?? '-',
                    'status' => $so->status,
                    'ada_selisih' => $hasSelisih,
                ];
            })
            ->toArray();

        return [
            'stok_kritis' => $stokKritis,
            'pending_masuk' => $this->can($user, 'barang-masuk-list') ? $pendingMasuk : [],
            'pending_opname' => $this->can($user, 'stok-opname-list') ? $pendingOpname : [],
        ];
    }

    private function getWarehouseCapacity(?int $gudangId): array
    {
        $query = Gudang::withCount('lokasiRak');
        if ($gudangId) {
            $query->where('id', $gudangId);
        }

        $gudangs = $query->get();

        $allBarangIds = Barang::where('status', 'aktif')->pluck('id')->toArray();

        return $gudangs->map(function ($gudang) use ($allBarangIds) {
            $totalKapasitas = $gudang->lokasi_rak_count ?? 0;

            $saldoMap = $this->stokService->hitungSaldoStokBatch($allBarangIds, $gudang->id);
            $totalTerisi = (int) array_sum($saldoMap);

            $rakKapasitas = LokasiRak::where('gudang_id', $gudang->id)
                ->sum('kapasitas');
            $kapasitas = max((int) $rakKapasitas, 1);

            $persen = min(100, round(($totalTerisi / $kapasitas) * 100));

            return [
                'id' => $gudang->id,
                'nama' => $gudang->nama,
                'terisi' => $totalTerisi,
                'kapasitas' => $kapasitas,
                'persen' => $persen,
            ];
        })->toArray();
    }

    private function getAttendanceToday(?int $gudangId, ?User $user = null): array
    {
        if (! $this->can($user, 'absensi-list')) {
            return [];
        }

        $today = Carbon::now()->toDateString();

        $shifts = DB::table('shift')->where('status', 'aktif')->get();

        $results = [];
        foreach ($shifts as $shift) {
            $query = Absensi::where('shift_id', $shift->id)
                ->where('tanggal', $today);

            if ($gudangId) {
                $query->where('gudang_id', $gudangId);
            }

            $total = $query->count();
            $hadir = (clone $query)->whereIn('status', ['hadir', 'terlambat'])->count();

            $results[] = [
                'nama' => $shift->nama,
                'jam_masuk' => $shift->jam_masuk,
                'jam_pulang' => $shift->jam_pulang,
                'hadir' => $hadir,
                'total' => $total,
            ];
        }

        return $results;
    }
}
