<?php

namespace Tests\Feature\Api;

class AbsensiTest extends ApiTestCase
{
    public function test_index_absensi()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/absensi');
        $response->assertOk();
    }

    public function test_store_absensi()
    {
        $this->actingAsAdmin();
        $user = \App\Models\User::factory()->create();
        $gudang = \App\Models\Gudang::factory()->create();
        $shift = \App\Models\Shift::factory()->create();

        $response = $this->postJson('/api/absensi', [
            'user_id' => $user->id,
            'gudang_id' => $gudang->id,
            'shift_id' => $shift->id,
            'tanggal' => now()->format('Y-m-d'),
            'jam_masuk' => '07:00',
            'status' => 'hadir',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['status' => 'hadir']);
    }

    public function test_show_absensi()
    {
        $this->actingAsAdmin();
        $absensi = \App\Models\Absensi::factory()->create();
        $response = $this->getJson("/api/absensi/{$absensi->id}");
        $response->assertOk();
    }

    public function test_update_absensi()
    {
        $this->actingAsAdmin();
        $absensi = \App\Models\Absensi::factory()->create();
        $response = $this->putJson("/api/absensi/{$absensi->id}", [
            'jam_pulang' => '15:00',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['jam_pulang' => '15:00']);
    }

    public function test_destroy_absensi()
    {
        $this->actingAsAdmin();
        $absensi = \App\Models\Absensi::factory()->create();
        $response = $this->deleteJson("/api/absensi/{$absensi->id}");
        $response->assertNoContent();
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/absensi', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/absensi');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/absensi/99999');
        $response->assertStatus(404);
    }
}