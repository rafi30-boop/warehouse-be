<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        config(['auth.defaults.guard' => 'api']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'gudang-list', 'gudang-create', 'gudang-edit', 'gudang-delete',
            'barang-list', 'barang-create', 'barang-edit', 'barang-delete',
            'kategori-list', 'kategori-create', 'kategori-edit', 'kategori-delete',
            'supplier-list', 'supplier-create', 'supplier-edit', 'supplier-delete',
            'customer-list', 'customer-create', 'customer-edit', 'customer-delete',
            'barang-masuk-list', 'barang-masuk-create', 'barang-masuk-edit', 'barang-masuk-delete',
            'barang-masuk-approve',
            'barang-keluar-list', 'barang-keluar-create', 'barang-keluar-edit', 'barang-keluar-delete',
            'barang-keluar-approve', 'barang-keluar-deliver',
            'mutasi-stok-list', 'mutasi-stok-create', 'mutasi-stok-edit', 'mutasi-stok-delete',
            'mutasi-stok-approve',
            'stok-opname-list', 'stok-opname-create', 'stok-opname-edit', 'stok-opname-delete',
            'stok-opname-start', 'stok-opname-complete', 'stok-opname-cancel',
            'absensi-list', 'absensi-create', 'absensi-edit', 'absensi-delete',
            'shift-list', 'shift-create', 'shift-edit', 'shift-delete',
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            'laporan-stok', 'laporan-barang-masuk', 'laporan-barang-keluar',
            'laporan-mutasi-stok', 'laporan-stok-opname', 'laporan-absensi',
            'barang-export',
            'barang-masuk-export', 'barang-masuk-print',
            'barang-keluar-export', 'barang-keluar-print',
            'satuan-list', 'satuan-create', 'satuan-edit', 'satuan-delete',
            'lokasi-rak-list', 'lokasi-rak-create', 'lokasi-rak-edit', 'lokasi-rak-delete',
            'kartu-stok-list',
            'notifikasi-list', 'notifikasi-edit', 'notifikasi-delete',
            'aktivitas-log-list', 'aktivitas-log-delete',
            'batch-barang-list', 'batch-barang-create', 'batch-barang-edit', 'batch-barang-delete',
            'history-harga-list', 'history-harga-create', 'history-harga-delete',
            'jadwal-petugas-list', 'jadwal-petugas-create', 'jadwal-petugas-edit', 'jadwal-petugas-delete',
            'upload',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions([
            'gudang-list',
            'barang-list', 'barang-create', 'barang-edit',
            'kategori-list',
            'supplier-list', 'supplier-create', 'supplier-edit',
            'customer-list', 'customer-create', 'customer-edit',
            'barang-masuk-list', 'barang-masuk-create',
            'barang-keluar-list', 'barang-keluar-create', 'barang-keluar-deliver',
            'mutasi-stok-list', 'mutasi-stok-create',
            'stok-opname-list', 'stok-opname-create',
            'absensi-list', 'absensi-create', 'absensi-edit',
            'shift-list',
            'satuan-list',
            'lokasi-rak-list',
            'kartu-stok-list',
            'notifikasi-list',
            'batch-barang-list',
            'jadwal-petugas-list',
            'upload',
        ]);
    }
}
