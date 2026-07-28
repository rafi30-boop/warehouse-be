<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\BarangMasukController;
use App\Http\Controllers\Api\BarangKeluarController;
use App\Http\Controllers\Api\MutasiStokController;
use App\Http\Controllers\Api\StokOpnameController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\LaporanController;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');

Route::middleware(['auth:api', 'throttle:api'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('gudang', GudangController::class);
    Route::get('barang/export/excel', [BarangController::class, 'exportExcel']);
    Route::apiResource('barang', BarangController::class);
    Route::apiResource('kategori', KategoriController::class);
    Route::apiResource('supplier', SupplierController::class);
    Route::apiResource('customer', CustomerController::class);
    Route::get('barang-masuk/{barang_masuk}/print-surat-jalan', [BarangMasukController::class, 'printSuratJalan']);
    Route::get('barang-masuk/export/excel', [BarangMasukController::class, 'exportExcel']);
    Route::apiResource('barang-masuk', BarangMasukController::class);
    Route::get('barang-keluar/{barang_keluar}/print-surat-jalan', [BarangKeluarController::class, 'printSuratJalan']);
    Route::get('barang-keluar/export/excel', [BarangKeluarController::class, 'exportExcel']);
    Route::apiResource('barang-keluar', BarangKeluarController::class);
    Route::apiResource('mutasi-stok', MutasiStokController::class);
    Route::apiResource('stok-opname', StokOpnameController::class);
    Route::apiResource('absensi', AbsensiController::class);
    Route::apiResource('shift', ShiftController::class);
    Route::apiResource('user', UserController::class);
    Route::apiResource('role', RoleController::class);

    Route::prefix('laporan')->group(function () {
        Route::get('stok', [LaporanController::class, 'stok']);
        Route::get('barang-masuk', [LaporanController::class, 'barangMasuk']);
        Route::get('barang-keluar', [LaporanController::class, 'barangKeluar']);
        Route::get('mutasi-stok', [LaporanController::class, 'mutasiStok']);
        Route::get('stok-opname', [LaporanController::class, 'stokOpname']);
        Route::get('absensi', [LaporanController::class, 'absensi']);
    });
});
