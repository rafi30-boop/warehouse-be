<?php

namespace Tests\Feature\Api;

use App\Models\Absensi;
use App\Models\Gudang;
use App\Models\IzinRequest;
use App\Models\Shift;
use App\Models\User;
use Laravel\Passport\Passport;

class IzinRequestTest extends ApiTestCase
{
    private Shift $shift;

    private Gudang $gudang;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        // operatorUser berperan sebagai pemilik pengajuan biasa:
        // boleh CRUD milik sendiri, bukan approver, tanpa hak on-behalf.
        $this->operatorUser->givePermissionTo([
            'izin-list', 'izin-create', 'izin-delete',
        ]);

        $this->shift = Shift::factory()->create(['status' => 'aktif']);
        $this->gudang = Gudang::factory()->create();
        $this->pegawai = User::factory()->create([
            'is_active' => true,
            'gudang_id' => $this->gudang->id,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'jenis' => 'sakit',
            'tanggal_mulai' => '2026-08-25',
            'tanggal_selesai' => '2026-08-26',
            'alasan' => 'Demam, ada surat dokter',
            'bukti' => 'http://localhost:8000/storage/uploads/surat.pdf',
        ], $overrides);
    }

    public function test_store_forces_own_user_id()
    {
        $this->actingAsOperator();

        // Self-service tanpa izin-edit: tanggal harus ke depan + milik sendiri.
        $response = $this->postJson('/api/izin', $this->validPayload([
            'tanggal_mulai' => today()->addDay()->toDateString(),
            'tanggal_selesai' => today()->addDays(2)->toDateString(),
            'user_id' => $this->adminUser->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.user.id', $this->operatorUser->id)
            ->assertJsonPath('data.status', 'menunggu');

        $this->assertDatabaseHas('izin_requests', [
            'user_id' => $this->operatorUser->id,
            'status' => 'menunggu',
        ]);
    }

    public function test_store_validation_error()
    {
        $this->actingAsOperator();

        $response = $this->postJson('/api/izin', [
            'jenis' => 'libur',
            'tanggal_mulai' => '2026-08-26',
            'tanggal_selesai' => '2026-08-25',
        ]);

        $response->assertStatus(422);
    }

    public function test_index_scopes_to_owner_for_non_approver()
    {
        IzinRequest::create($this->validPayload() + ['user_id' => $this->operatorUser->id]);
        IzinRequest::create($this->validPayload() + ['user_id' => $this->pegawai->id]);

        $this->actingAsOperator();
        $response = $this->getJson('/api/izin');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($this->operatorUser->id, $response->json('data.0.user_id'));

        $this->actingAsAdmin();
        $response = $this->getJson('/api/izin');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_show_other_forbidden_for_non_approver()
    {
        $izin = IzinRequest::create($this->validPayload() + ['user_id' => $this->pegawai->id]);

        $this->actingAsOperator();
        $this->getJson("/api/izin/{$izin->id}")->assertStatus(403);

        $this->actingAsAdmin();
        $this->getJson("/api/izin/{$izin->id}")->assertOk();
    }

    public function test_update_only_pending_and_own()
    {
        $izin = IzinRequest::create($this->validPayload() + ['user_id' => $this->operatorUser->id]);

        // Pemegang izin-edit (admin) boleh mengubah milik orang lain yang pending.
        $this->actingAsAdmin();
        $this->putJson("/api/izin/{$izin->id}", ['alasan' => 'diubah admin'])
            ->assertOk()
            ->assertJsonPath('data.alasan', 'diubah admin');

        // Tanpa izin-edit, milik orang lain tetap 403.
        Passport::actingAs(User::find($this->pegawai->id), ['*']);
        $this->putJson("/api/izin/{$izin->id}", ['alasan' => 'diubah orang lain'])->assertStatus(403);

        $this->actingAsOperator();
        $this->putJson("/api/izin/{$izin->id}", ['alasan' => 'alasan baru'])
            ->assertOk()
            ->assertJsonPath('data.alasan', 'alasan baru');

        $izin->update(['status' => 'disetujui']);
        // Non-pending tidak bisa diubah (422), bukan 403.
        $this->putJson("/api/izin/{$izin->id}", ['alasan' => 'x'])->assertStatus(422);
    }

    public function test_destroy_only_pending()
    {
        $izin = IzinRequest::create($this->validPayload() + ['user_id' => $this->operatorUser->id]);

        $this->actingAsOperator();
        $this->deleteJson("/api/izin/{$izin->id}")->assertOk();

        $izin = IzinRequest::create($this->validPayload() + [
            'user_id' => $this->pegawai->id,
            'status' => 'disetujui',
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        $this->deleteJson("/api/izin/{$izin->id}")->assertStatus(422);
    }

    public function test_approve_auto_creates_absensi_for_range()
    {
        $izin = IzinRequest::create($this->validPayload() + ['user_id' => $this->pegawai->id]);

        $this->actingAsAdmin();
        $response = $this->postJson("/api/izin/{$izin->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.izin_request.status', 'disetujui')
            ->assertJsonPath('data.absensi_created_ids.0', fn ($id) => $id > 0);

        $this->assertDatabaseHas('izin_requests', [
            'id' => $izin->id,
            'status' => 'disetujui',
            'approved_by' => $this->adminUser->id,
        ]);

        foreach (['2026-08-25', '2026-08-26'] as $tanggal) {
            $this->assertDatabaseHas('absensi', [
                'user_id' => $this->pegawai->id,
                'tanggal' => $tanggal,
                'status' => 'sakit',
                'gudang_id' => $this->gudang->id,
                'shift_id' => $this->shift->id,
            ]);
        }
    }

    public function test_approve_skips_dates_that_already_have_absensi()
    {
        Absensi::create([
            'user_id' => $this->pegawai->id,
            'gudang_id' => $this->gudang->id,
            'shift_id' => $this->shift->id,
            'tanggal' => '2026-08-25',
            'jam_masuk' => '08:00',
            'status' => 'hadir',
        ]);

        $izin = IzinRequest::create($this->validPayload() + ['user_id' => $this->pegawai->id]);

        $this->actingAsAdmin();
        $response = $this->postJson("/api/izin/{$izin->id}/approve");

        $response->assertOk();
        $this->assertCount(1, $response->json('data.absensi_created_ids'));
        $this->assertDatabaseMissing('absensi', [
            'user_id' => $this->pegawai->id,
            'tanggal' => '2026-08-25',
            'status' => 'sakit',
        ]);
    }

    public function test_double_approve_rejected()
    {
        $izin = IzinRequest::create($this->validPayload() + ['user_id' => $this->pegawai->id]);

        $this->actingAsAdmin();
        $this->postJson("/api/izin/{$izin->id}/approve")->assertOk();
        $this->postJson("/api/izin/{$izin->id}/approve")->assertStatus(422);
    }

    public function test_reject()
    {
        $izin = IzinRequest::create($this->validPayload() + ['user_id' => $this->pegawai->id]);

        $this->actingAsAdmin();
        $response = $this->postJson("/api/izin/{$izin->id}/reject", [
            'catatan_penolakan' => 'Tidak memenuhi syarat',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'ditolak');
        $this->assertDatabaseMissing('absensi', ['user_id' => $this->pegawai->id]);
    }

    public function test_non_approver_cannot_approve_or_reject()
    {
        $izin = IzinRequest::create($this->validPayload() + ['user_id' => $this->pegawai->id]);

        $this->actingAsOperator();
        $this->postJson("/api/izin/{$izin->id}/approve")->assertStatus(403);
        $this->postJson("/api/izin/{$izin->id}/reject")->assertStatus(403);
    }
}
