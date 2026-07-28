<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Barang;
use App\Models\Shift;
use App\Models\LokasiRak;
use App\Models\BarangMasuk;
use App\Models\BarangMasukDetail;
use App\Models\BarangKeluar;
use App\Models\BarangKeluarDetail;
use App\Models\MutasiStok;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Models\KartuStok;
use App\Models\JadwalPetugas;
use App\Models\Absensi;
use App\Models\Notifikasi;
use App\Models\HistoryHarga;
use App\Models\BatchBarang;
use App\Models\AktivitasLog;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('super-admin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        $operator = User::firstOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => 'Operator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $operator->assignRole('operator');

        Gudang::firstOrCreate(['kode' => 'GDG-001'], [
            'nama' => 'Gudang Utama',
            'alamat' => 'Jl. Contoh No. 1',
            'status' => 'aktif',
        ]);

        Gudang::firstOrCreate(['kode' => 'GDG-002'], [
            'nama' => 'Gudang Cabang',
            'alamat' => 'Jl. Contoh No. 2',
            'status' => 'aktif',
        ]);

        Kategori::firstOrCreate(['nama' => 'Elektronik'], ['deskripsi' => 'Barang elektronik']);
        Kategori::firstOrCreate(['nama' => 'Furniture'], ['deskripsi' => 'Perabotan']);
        Kategori::firstOrCreate(['nama' => 'ATK'], ['deskripsi' => 'Alat Tulis Kantor']);

        Satuan::firstOrCreate(['nama' => 'Unit'], ['singkatan' => 'unit']);
        Satuan::firstOrCreate(['nama' => 'Kilogram'], ['singkatan' => 'kg']);
        Satuan::firstOrCreate(['nama' => 'Lembar'], ['singkatan' => 'lbr']);

        Shift::firstOrCreate(['nama' => 'Pagi'], [
            'jam_masuk' => '07:00', 'jam_pulang' => '15:00',
            'toleransi_masuk' => 15, 'toleransi_pulang' => 0,
            'status' => 'aktif',
        ]);
        Shift::firstOrCreate(['nama' => 'Siang'], [
            'jam_masuk' => '15:00', 'jam_pulang' => '23:00',
            'toleransi_masuk' => 15, 'toleransi_pulang' => 0,
            'status' => 'aktif',
        ]);

        Supplier::firstOrCreate(['kode' => 'SUP-001'], [
            'tipe' => 'perusahaan', 'nama' => 'PT Supplier Utama',
            'kontak' => 'Budi', 'telepon' => '021123456',
        ]);

        Customer::firstOrCreate(['kode' => 'CUS-001'], [
            'tipe' => 'perusahaan', 'nama' => 'PT Customer Utama',
            'kontak' => 'Ani', 'telepon' => '021654321',
        ]);
    }
}