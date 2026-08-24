<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\QrService;

class QrTest extends ApiTestCase
{
    private QrService $qrService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrService = app(QrService::class);
    }

    public function test_issue_own_qr()
    {
        $this->actingAsOperator();

        $response = $this->postJson('/api/qr/issue');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $this->operatorUser->id);

        $payload = $response->json('data.payload');
        $this->assertStringStartsWith('WQR1.', $payload);
    }

    public function test_issue_for_other_requires_permission()
    {
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAsOperator();
        $this->postJson('/api/qr/issue', ['user_id' => $target->id])
            ->assertStatus(403);

        $this->actingAsAdmin();
        $this->postJson('/api/qr/issue', ['user_id' => $target->id])
            ->assertOk()
            ->assertJsonPath('data.user.id', $target->id);
    }

    public function test_verify_roundtrip()
    {
        $pegawai = User::factory()->create(['is_active' => true, 'no_pegawai' => 'NP-001']);
        $raw = $this->qrService->issue($pegawai);

        $result = $this->qrService->verify($raw);

        $this->assertTrue($result['ok']);
        $this->assertEquals($pegawai->id, $result['user']->id);
    }

    public function test_verify_rejects_tampered_payload()
    {
        $pegawai = User::factory()->create(['is_active' => true]);
        $raw = $this->qrService->issue($pegawai);

        $tampered = substr($raw, 0, -2).'xx';
        $result = $this->qrService->verify($tampered);

        $this->assertFalse($result['ok']);
        $this->assertSame(QrService::ERROR_SIGNATURE, $result['error']);

        $result = $this->qrService->verify('{"uid":1}');
        $this->assertFalse($result['ok']);
        $this->assertSame(QrService::ERROR_FORMAT, $result['error']);
    }

    public function test_regenerate_invalidates_old_card()
    {
        $this->actingAsOperator();
        $old = $this->postJson('/api/qr/issue')->json('data.payload');

        $response = $this->postJson("/api/qr/{$this->operatorUser->id}/regenerate");
        $response->assertOk()->assertJsonPath('success', true);
        $new = $response->json('data.payload');

        $oldResult = $this->qrService->verify($old);
        $this->assertFalse($oldResult['ok']);
        $this->assertSame(QrService::ERROR_STALE_VERSION, $oldResult['error']);

        $this->assertTrue($this->qrService->verify($new)['ok']);
    }

    public function test_regenerate_other_requires_permission()
    {
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAsOperator();
        $this->postJson("/api/qr/{$target->id}/regenerate")->assertStatus(403);

        $this->actingAsAdmin();
        $this->postJson("/api/qr/{$target->id}/regenerate")->assertOk();
    }

    public function test_revoke_blocks_verification_and_regenerate_restores()
    {
        $pegawai = User::factory()->create(['is_active' => true]);
        $raw = $this->qrService->issue($pegawai);

        $this->actingAsOperator();
        $this->postJson("/api/qr/{$pegawai->id}/revoke")->assertStatus(403);

        $this->actingAsAdmin();
        $this->postJson("/api/qr/{$pegawai->id}/revoke")
            ->assertOk()
            ->assertJsonPath('success', true);

        $revokedResult = $this->qrService->verify($raw);
        $this->assertFalse($revokedResult['ok']);
        $this->assertSame(QrService::ERROR_REVOKED, $revokedResult['error']);

        $this->postJson('/api/qr/issue', ['user_id' => $pegawai->id])->assertStatus(422);

        $this->postJson("/api/qr/{$pegawai->id}/regenerate")->assertOk();
        $fresh = $this->qrService->issue($pegawai->refresh());
        $this->assertTrue($this->qrService->verify($fresh)['ok']);
    }
}
