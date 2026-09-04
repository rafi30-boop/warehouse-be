<?php

namespace Tests\Feature\Api;

use App\Models\Petugas;
use App\Models\User;
use Database\Seeders\PetugasBackfillSeeder;
use Illuminate\Support\Facades\DB;

class PetugasV2Test extends ApiTestCase
{
    public function test_store_auto_kode_and_full_crud_flow()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $res = $this->postJson('/api/petugas', [
            'nama' => 'Budi Santoso',
            'jabatan' => 'Operator',
            'user_id' => $user->id,
        ]);
        $res->assertCreated()
            ->assertJsonPath('data.nama', 'Budi Santoso')
            ->assertJsonPath('data.status_operasional', 'Aktif')
            ->assertJsonPath('data.user.id', $user->id);
        $this->assertMatchesRegularExpression('/^PG-\d{3}$/', $res->json('data.kode'));
        $id = $res->json('data.id');

        $this->getJson("/api/petugas/{$id}")->assertOk()->assertJsonPath('data.id', $id);

        $this->putJson("/api/petugas/{$id}", ['nama' => 'Budi S.', 'status_operasional' => 'Cuti', 'area_kerja' => 'Dock B'])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Budi S.')
            ->assertJsonPath('data.status_operasional', 'Cuti')
            ->assertJsonPath('data.area_kerja', 'Dock B')
            ->assertJsonPath('data.kode', $res->json('data.kode'));

        $unlink = $this->putJson("/api/petugas/{$id}", ['user_id' => null]);
        $unlink->assertOk()->assertJsonPath('data.user_id', null)->assertJsonPath('data.user', null);

        $this->deleteJson("/api/petugas/{$id}")->assertOk();
        $this->assertSoftDeleted('petugas', ['id' => $id]);
    }

    public function test_store_without_user_id_creates_standalone_karyawan()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/petugas', ['nama' => 'Karyawan Tanpa Akun']);
        $res->assertCreated()
            ->assertJsonPath('data.nama', 'Karyawan Tanpa Akun')
            ->assertJsonPath('data.user_id', null)
            ->assertJsonPath('data.user', null)
            ->assertJsonPath('data.status_operasional', 'Aktif');
        $this->assertMatchesRegularExpression('/^PG-\d{3}$/', $res->json('data.kode'));
    }

    public function test_store_requires_nama_and_rejects_duplicate_user_or_kode()
    {
        $this->actingAsAdmin();
        $u = User::factory()->create();
        Petugas::create(['nama' => 'Lama', 'user_id' => $u->id, 'kode' => 'X-1']);

        $this->postJson('/api/petugas', ['kode' => 'X-9'])->assertStatus(422)->assertJsonValidationErrors('nama');
        $this->postJson('/api/petugas', ['nama' => 'Dup User', 'user_id' => $u->id])
            ->assertStatus(422)->assertJsonValidationErrors('user_id');
        $this->postJson('/api/petugas', ['nama' => 'Dup Kode', 'kode' => 'X-1'])
            ->assertStatus(422)->assertJsonValidationErrors('kode');
        $this->postJson('/api/petugas', ['nama' => 'Bad Enum', 'status_operasional' => 'libur'])
            ->assertStatus(422)->assertJsonValidationErrors('status_operasional');
    }

    public function test_index_filters_search_status_and_operator_cannot_create()
    {
        $a = User::factory()->create(['name' => 'Budi']);
        Petugas::create(['nama' => 'Budi Karyawan', 'user_id' => $a->id, 'kode' => 'K-A', 'status_operasional' => 'Aktif']);
        Petugas::create(['nama' => 'Sari Karyawan', 'kode' => 'K-B', 'status_operasional' => 'Non-Aktif']);

        // Operator boleh melihat daftar, tapi tidak boleh membuat.
        $this->operatorUser->givePermissionTo('petugas-list');

        $this->actingAsOperator();
        $this->getJson('/api/petugas?status=Non-Aktif')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nama', 'Sari Karyawan');
        $this->getJson('/api/petugas?search=Budi')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.kode', 'K-A');
        $this->postJson('/api/petugas', ['nama' => 'X'])->assertStatus(403);
    }

    public function test_destroy_keeps_linked_user_account_alive()
    {
        $this->actingAsAdmin();
        $u = User::factory()->create();
        $p = Petugas::create(['nama' => 'Hapus Aku', 'user_id' => $u->id, 'kode' => 'K-Z']);

        $this->deleteJson("/api/petugas/{$p->id}")->assertOk();
        $this->assertSoftDeleted('petugas', ['id' => $p->id]);
        $this->assertDatabaseHas('users', ['id' => $u->id]);
    }

    public function test_backfill_seeder_fills_nama_from_users()
    {
        $roleId = DB::table('roles')->where('name', 'operator')->value('id');
        DB::table('model_has_roles')->where('role_id', $roleId)->delete();
        Petugas::query()->delete();

        $op = User::factory()->create(['name' => 'Slamet Operator', 'no_pegawai' => 'EMP-777', 'is_active' => true]);
        $op->assignRole('operator');

        $this->seed(PetugasBackfillSeeder::class);

        $this->assertDatabaseHas('petugas', [
            'user_id' => $op->id,
            'nama' => 'Slamet Operator',
            'kode' => 'EMP-777',
            'status_operasional' => 'Aktif',
        ]);
    }
}
