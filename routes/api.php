<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AktivitasLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\BarangKeluarController;
use App\Http\Controllers\Api\BarangMasukController;
use App\Http\Controllers\Api\BatchBarangController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\HistoryHargaController;
use App\Http\Controllers\Api\IzinRequestController;
use App\Http\Controllers\Api\JadwalPetugasController;
use App\Http\Controllers\Api\KartuStokController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\LokasiRakController;
use App\Http\Controllers\Api\MutasiStokController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PetugasController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SatuanController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StokOpnameController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');

// Portal izin (public, QR-based auth per request)
Route::post('portal/auth', [PortalController::class, 'auth'])->middleware('throttle:api');
Route::post('portal/izin/riwayat', [PortalController::class, 'riwayat'])->middleware('throttle:api');
Route::post('portal/izin', [PortalController::class, 'create'])->middleware('throttle:api');
Route::post('portal/izin/{izin_request}/cancel', [PortalController::class, 'cancel'])->middleware('throttle:api');

Route::middleware(['auth:api', 'throttle:api'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::post('upload', [FileController::class, 'store']);

    Route::post('qr/issue', [QrController::class, 'issue']);
    Route::post('qr/{user}/regenerate', [QrController::class, 'regenerate']);
    Route::post('qr/{user}/revoke', [QrController::class, 'revoke']);

    Route::apiResource('gudang', GudangController::class);
    Route::get('barang/export/excel', [BarangController::class, 'exportExcel']);
    Route::apiResource('barang', BarangController::class);
    Route::apiResource('kategori', KategoriController::class);
    Route::apiResource('supplier', SupplierController::class);
    Route::apiResource('customer', CustomerController::class);
    Route::get('barang-masuk/{barang_masuk}/print-surat-jalan', [BarangMasukController::class, 'printSuratJalan']);
    Route::get('barang-masuk/export/excel', [BarangMasukController::class, 'exportExcel']);
    Route::post('barang-masuk/{barang_masuk}/approve', [BarangMasukController::class, 'approve']);
    Route::post('barang-masuk/{barang_masuk}/reject', [BarangMasukController::class, 'reject']);
    Route::apiResource('barang-masuk', BarangMasukController::class);
    Route::get('barang-keluar/{barang_keluar}/print-surat-jalan', [BarangKeluarController::class, 'printSuratJalan']);
    Route::get('barang-keluar/export/excel', [BarangKeluarController::class, 'exportExcel']);
    Route::post('barang-keluar/{barang_keluar}/approve', [BarangKeluarController::class, 'approve']);
    Route::post('barang-keluar/{barang_keluar}/reject', [BarangKeluarController::class, 'reject']);
    Route::post('barang-keluar/{barang_keluar}/deliver', [BarangKeluarController::class, 'deliver']);
    Route::post('barang-keluar/{barang_keluar}/partial', [BarangKeluarController::class, 'partial']);
    Route::apiResource('barang-keluar', BarangKeluarController::class);
    Route::post('mutasi-stok/{mutasi_stok}/approve', [MutasiStokController::class, 'approve']);
    Route::post('mutasi-stok/{mutasi_stok}/reject', [MutasiStokController::class, 'reject']);
    Route::post('mutasi-stok/{mutasi_stok}/complete', [MutasiStokController::class, 'complete']);
    Route::apiResource('mutasi-stok', MutasiStokController::class);
    Route::post('stok-opname/{stok_opname}/start', [StokOpnameController::class, 'start']);
    Route::post('stok-opname/{stok_opname}/complete', [StokOpnameController::class, 'complete']);
    Route::post('stok-opname/{stok_opname}/cancel', [StokOpnameController::class, 'cancel']);
    Route::apiResource('stok-opname', StokOpnameController::class);
    Route::post('absensi/scan', [AbsensiController::class, 'scan']);
    Route::post('absensi/scan/sync', [AbsensiController::class, 'scanSync']);
    Route::apiResource('absensi', AbsensiController::class);
    Route::post('petugas/{petugas}/qr/issue', [PetugasController::class, 'issueQr']);
    Route::post('petugas/{petugas}/qr/regenerate', [PetugasController::class, 'regenerateQr']);
    Route::post('petugas/{petugas}/qr/revoke', [PetugasController::class, 'revokeQr']);
    Route::apiResource('petugas', PetugasController::class)->parameters(['petugas' => 'petugas']);
    Route::post('izin/{izin_request}/approve', [IzinRequestController::class, 'approve']);
    Route::post('izin/{izin_request}/reject', [IzinRequestController::class, 'reject']);
    Route::post('izin/{izin_request}/cancel', [IzinRequestController::class, 'cancel']);
    Route::apiResource('izin', IzinRequestController::class)->parameters(['izin' => 'izin_request']);
    Route::apiResource('shift', ShiftController::class);
    Route::apiResource('user', UserController::class);
    Route::apiResource('role', RoleController::class);

    Route::apiResource('satuan', SatuanController::class);
    Route::apiResource('lokasi-rak', LokasiRakController::class);
    Route::get('kartu-stok/riwayat', [KartuStokController::class, 'riwayat']);
    Route::get('kartu-stok/{kartu_stok}', [KartuStokController::class, 'show']);
    Route::get('kartu-stok', [KartuStokController::class, 'index']);
    Route::post('notifikasi/read-all', [NotifikasiController::class, 'markAllRead']);
    Route::post('notifikasi/{notifikasi}/read', [NotifikasiController::class, 'markAsRead']);
    Route::apiResource('notifikasi', NotifikasiController::class)->except(['store', 'update']);
    Route::apiResource('aktivitas-log', AktivitasLogController::class)->only(['index', 'show', 'destroy']);
    Route::apiResource('batch-barang', BatchBarangController::class);
    Route::apiResource('history-harga', HistoryHargaController::class)->except(['update']);
    Route::apiResource('jadwal-petugas', JadwalPetugasController::class)->parameters(['jadwal-petugas' => 'jadwal_petugas']);

    Route::prefix('laporan')->group(function () {
        Route::get('stok', [LaporanController::class, 'stok']);
        Route::get('barang-masuk', [LaporanController::class, 'barangMasuk']);
        Route::get('barang-keluar', [LaporanController::class, 'barangKeluar']);
        Route::get('mutasi-stok', [LaporanController::class, 'mutasiStok']);
        Route::get('stok-opname', [LaporanController::class, 'stokOpname']);
        Route::get('absensi', [LaporanController::class, 'absensi']);
    });
});
