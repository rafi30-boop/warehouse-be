<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            GudangParfumSeeder::class,
            PetugasBackfillSeeder::class,
            MigrasiOperatorSeeder::class,
        ]);
    }
}
