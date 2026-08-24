<?php

namespace Database\Seeders;

use App\Models\Petugas;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PetugasBackfillSeeder extends Seeder
{
    private int $nextNumber = 0;

    public function run(): void
    {
        $operatorIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'operator')
            ->pluck('model_id');

        $users = User::whereIn('id', $operatorIds)->get();

        $nextNumber = (int) (Petugas::withTrashed()
            ->whereRaw("kode regexp '^PG-[0-9]+$'")
            ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(kode, 4) AS UNSIGNED)), 0) as max_num')
            ->value('max_num'));

        foreach ($users as $user) {
            Petugas::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nama' => $user->name,
                    'kode' => $this->resolveKode($user),
                    'status_operasional' => $user->is_active ? 'Aktif' : 'Non-Aktif',
                ]
            );
        }
    }

    private function resolveKode(User $user): string
    {
        if ($user->no_pegawai && ! Petugas::withTrashed()->where('kode', $user->no_pegawai)->exists()) {
            return $user->no_pegawai;
        }

        do {
            $kode = 'PG-'.str_pad((string) ++$this->nextNumber, 3, '0', STR_PAD_LEFT);
        } while (Petugas::withTrashed()->where('kode', $kode)->exists());

        return $kode;
    }
}
