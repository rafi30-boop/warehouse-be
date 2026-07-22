<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Shift;
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
            ]
        );
        $superAdmin->assignRole('super-admin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        $operator = User::firstOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => 'Operator',
                'password' => Hash::make('password'),
            ]
        );
        $operator->assignRole('operator');

        Gudang::firstOrCreate(['kode' => 'GDG-001'], [
            'nama' => 'Gudang Utama',
            'alamat' => 'Jl. Contoh No. 1',
            'is_active' => true,
        ]);

        Gudang::firstOrCreate(['kode' => 'GDG-002'], [
            'nama' => 'Gudang Cabang',
            'alamat' => 'Jl. Contoh No. 2',
            'is_active' => true,
        ]);

        Kategori::firstOrCreate(['nama' => 'Elektronik'], ['deskripsi' => 'Barang elektronik']);
        Kategori::firstOrCreate(['nama' => 'Furniture'], ['deskripsi' => 'Perabotan']);
        Kategori::firstOrCreate(['nama' => 'ATK'], ['deskripsi' => 'Alat Tulis Kantor']);

        Shift::firstOrCreate(['nama' => 'Pagi'], ['jam_mulai' => '07:00', 'jam_selesai' => '15:00']);
        Shift::firstOrCreate(['nama' => 'Siang'], ['jam_mulai' => '15:00', 'jam_selesai' => '23:00']);
    }
}
