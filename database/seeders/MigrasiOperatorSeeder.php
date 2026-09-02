<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Migrasi role operator → role baru sesuai jabatan di GudangParfumSeeder.
 *
 * Pemetaan:
 *  - budi, rudi, agus (Kepala Gudang) → kepala-gudang
 *  - siti, intan (Admin Stok)          → admin-gudang
 *  - dewi (Checker)                     → checker
 *
 * Role 'operator' TIDAK dihapus (FK aman), hanya sync kosong di RolePermissionSeeder.
 */
class MigrasiOperatorSeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            'budi.santoso@gudangparfum.id' => 'kepala-gudang',
            'agus.wijaya@gudangparfum.id'  => 'kepala-gudang',
            'rudi.hartono@gudangparfum.id'  => 'kepala-gudang',
            'siti.rahayu@gudangparfum.id'   => 'admin-gudang',
            'intan.permata@gudangparfum.id' => 'admin-gudang',
            'dewi.lestari@gudangparfum.id'  => 'checker',
        ];

        $migrated = 0;
        $skipped = 0;

        foreach ($mapping as $email => $roleName) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->command?->warn("SKIP: User {$email} tidak ditemukan.");
                $skipped++;
                continue;
            }

            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                $this->command?->warn("SKIP: Role '{$roleName}' belum dibuat. Jalankan RolePermissionSeeder terlebih dahulu.");
                $skipped++;
                continue;
            }

            // Hapus role 'operator' lama, lalu assign role baru
            $user->removeRole('operator');
            $user->assignRole($roleName);

            $this->command?->info("OK: {$email} → {$roleName}");
            $migrated++;
        }

        $this->command?->info("Migrasi selesai: {$migrated} berhasil, {$skipped} dilewati.");
    }
}
