<?php

namespace Tests\Feature\Api;

use App\Models\Absensi;
use App\Models\Gudang;
use App\Models\Shift;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;

class AbsensiScanTest extends ApiTestCase
{
    private Shift $shift;

    private User $pegawai;

    private Gudang $gudang;

    protected function setUp(): void
    {
        parent::setUp();

        // Scanner device bertindak sebagai operator + permission scan.
        $this->operatorUser->givePermissionTo('absensi-scan');

        $this->gudang = Gudang::factory()->create();
        $this->shift = Shift::factory()->create([
            'nama' => 'Shift Pagi',
            'jam_masuk' => '08:00',
            'jam_pulang' => '17:00',
            'toleransi_masuk' => 30,
            'status' => 'aktif',
        ]);
        $this->pegawai = User::factory()->create([
            'is_active' => true,
            'no_pegawai' => 'NP-100',
            'gudang_id' => $this->gudang->id,
        ]);

        Carbon::setTestNow(Carbon::parse(today()->format('Y-m-d').' 07:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function scanPayload(): string
    {
        return app(QrService::class)->issue($this->pegawai);
    }

    public function test_scan_checkin_creates_hadir()
    {
        $this->actingAsOperator();

        $response = $this->postJson('/api/absensi/scan', [
            'qr_payload' => $this->scanPayload(),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tipe', 'masuk')
            ->assertJsonPath('data.duplicate', false)
            ->assertJsonPath('data.user.id', $this->pegawai->id)
            ->assertJsonPath('data.absensi.status', 'hadir')
            ->assertJsonPath('data.shift.id', $this->shift->id);

        $this->assertDatabaseHas('absensi', [
            'user_id' => $this->pegawai->id,
            'tanggal' => today()->toDateString(),
            'status' => 'hadir',
        ]);
    }

    public function test_scan_late_marks_terlambat()
    {
        Carbon::setTestNow(Carbon::parse(today()->format('Y-m-d').' 08:45:00'));

        $this->actingAsOperator();
        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        $response->assertOk()->assertJsonPath('data.absensi.status', 'terlambat');
    }

    public function test_scan_checkout_after_cooldown()
    {
        $this->actingAsOperator();

        $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        Carbon::setTestNow(Carbon::parse(today()->format('Y-m-d').' 17:10:00'));

        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        $response->assertOk()
            ->assertJsonPath('data.tipe', 'pulang')
            ->assertJsonPath('data.duplicate', false)
            ->assertJsonPath('data.absensi.status', 'hadir');

        $absensi = Absensi::where('user_id', $this->pegawai->id)->whereDate('tanggal', today())->first();
        $this->assertNotNull($absensi->jam_pulang);
    }

    public function test_duplicate_scan_within_cooldown_is_idempotent()
    {
        $this->actingAsOperator();

        $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);
        Carbon::setTestNow(Carbon::parse(today()->format('Y-m-d').' 07:01:00'));

        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        $response->assertOk()
            ->assertJsonPath('data.tipe', 'duplicate')
            ->assertJsonPath('data.duplicate', true);

        $count = Absensi::where('user_id', $this->pegawai->id)->count();
        $absensi = Absensi::first();

        $this->assertSame(1, $count);
        $this->assertNull($absensi->jam_pulang);
    }

    public function test_completed_day_returns_duplicate()
    {
        $this->actingAsAdmin();
        Absensi::create([
            'user_id' => $this->pegawai->id,
            'gudang_id' => $this->gudang->id,
            'shift_id' => $this->shift->id,
            'tanggal' => today(),
            'jam_masuk' => '08:00',
            'jam_pulang' => '17:00',
            'status' => 'hadir',
        ]);

        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        $response->assertOk()
            ->assertJsonPath('data.tipe', 'duplicate');
    }

    public function test_invalid_qr_rejected()
    {
        $this->actingAsOperator();

        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => '{"uid":1}']);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_inactive_user_rejected()
    {
        $this->pegawai->update(['is_active' => false]);

        $this->actingAsOperator();
        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        $response->assertStatus(422);
        $this->assertStringContainsStringIgnoringCase('tidak aktif', $response->json('message'));
    }

    public function test_missing_gudang_rejected()
    {
        $this->pegawai->update(['gudang_id' => null]);

        $this->actingAsOperator();
        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        $response->assertStatus(422);
        $this->assertStringContainsStringIgnoringCase('gudang', $response->json('message'));
    }

    public function test_scanner_gudang_used_as_fallback()
    {
        $this->pegawai->update(['gudang_id' => null]);
        $operatorGudang = Gudang::factory()->create();
        $this->operatorUser->update(['gudang_id' => $operatorGudang->id]);

        $this->actingAsOperator();
        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        $response->assertOk();
        $this->assertDatabaseHas('absensi', [
            'user_id' => $this->pegawai->id,
            'gudang_id' => $operatorGudang->id,
        ]);
    }

    public function test_requires_absensi_scan_permission()
    {
        $plainUser = User::factory()->create(['is_active' => true]);
        Passport::actingAs(User::find($plainUser->id), ['*']);

        $response = $this->postJson('/api/absensi/scan', ['qr_payload' => $this->scanPayload()]);

        $response->assertStatus(403);
    }
}
