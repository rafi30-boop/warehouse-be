<?php

namespace Tests\Feature\Api;

use App\Models\Shift;

class ShiftTest extends ApiTestCase
{
    public function test_index_shift()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/shift');
        $response->assertOk();
    }

    public function test_store_shift()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/shift', [
            'nama' => 'Shift Pagi',
            'jam_masuk' => '07:00',
            'jam_pulang' => '15:00',
            'status' => 'aktif',
        ]);
        $response->assertCreated()
            ->assertJsonFragment(['nama' => 'Shift Pagi']);
    }

    public function test_show_shift()
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();
        $response = $this->getJson("/api/shift/{$shift->id}");
        $response->assertOk();
    }

    public function test_update_shift()
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();
        $response = $this->putJson("/api/shift/{$shift->id}", [
            'nama' => 'Shift Siang',
        ]);
        $response->assertOk()
            ->assertJsonFragment(['nama' => 'Shift Siang']);
    }

    public function test_destroy_shift()
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();
        $response = $this->deleteJson("/api/shift/{$shift->id}");
        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_store_validation_error()
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/shift', []);
        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access()
    {
        $response = $this->getJson('/api/shift');
        $response->assertStatus(401);
    }

    public function test_show_not_found()
    {
        $this->actingAsAdmin();
        $response = $this->getJson('/api/shift/99999');
        $response->assertStatus(404);
    }
}
