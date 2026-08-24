<?php

namespace App\Services;

use App\Models\Petugas;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QrService extends BaseService
{
    public const PREFIX = 'WQR1';

    public const ERROR_FORMAT = 'invalid_format';

    public const ERROR_SIGNATURE = 'invalid_signature';

    public const ERROR_EXPIRED = 'expired';

    public const ERROR_REVOKED = 'revoked';

    public const ERROR_STALE_VERSION = 'stale_version';

    public const ERROR_INACTIVE = 'user_inactive';

    public const ERROR_NOT_FOUND = 'user_not_found';

    public const ERROR_PETUGAS_NOT_FOUND = 'petugas_not_found';

    public function issue(User $user): string
    {
        return $this->encode('uid', $user->id, (int) ($user->qr_version ?: 1));
    }

    public function issueForPetugas(Petugas $petugas): string
    {
        return $this->encode('pid', $petugas->id, (int) ($petugas->qr_version ?: 1));
    }

    public function verify(string $raw): array
    {
        $parts = explode('.', trim($raw));

        if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
            return ['ok' => false, 'error' => self::ERROR_FORMAT];
        }

        [, $body, $signature] = $parts;

        if (! hash_equals($this->sign($body), $signature)) {
            return ['ok' => false, 'error' => self::ERROR_SIGNATURE];
        }

        $payload = json_decode($this->base64UrlDecode($body), true);

        if (! is_array($payload) || ! isset($payload['v'])) {
            return ['ok' => false, 'error' => self::ERROR_FORMAT];
        }

        if (isset($payload['exp']) && now()->getTimestamp() > (int) $payload['exp']) {
            return ['ok' => false, 'error' => self::ERROR_EXPIRED];
        }

        if (isset($payload['pid'])) {
            return $this->resolvePetugas($payload);
        }

        if (! isset($payload['uid'])) {
            return ['ok' => false, 'error' => self::ERROR_FORMAT];
        }

        return $this->resolveUser($payload);
    }

    private function resolveUser(array $payload): array
    {
        $user = User::find((int) $payload['uid']);

        if (! $user) {
            return ['ok' => false, 'error' => self::ERROR_NOT_FOUND];
        }

        if (! $user->is_active) {
            return ['ok' => false, 'error' => self::ERROR_INACTIVE];
        }

        if ($user->qr_revoked_at !== null) {
            return ['ok' => false, 'error' => self::ERROR_REVOKED, 'user_id' => $user->id];
        }

        if ((int) $payload['v'] !== (int) $user->qr_version) {
            return ['ok' => false, 'error' => self::ERROR_STALE_VERSION, 'user_id' => $user->id];
        }

        return [
            'ok' => true,
            'subject' => ['jenis' => 'user', 'id' => $user->id],
            'user' => $user,
            'payload' => $payload,
        ];
    }

    private function resolvePetugas(array $payload): array
    {
        $petugas = Petugas::find((int) $payload['pid']);

        if (! $petugas) {
            return ['ok' => false, 'error' => self::ERROR_PETUGAS_NOT_FOUND];
        }

        if ($petugas->qr_revoked_at !== null) {
            return ['ok' => false, 'error' => self::ERROR_REVOKED, 'petugas_id' => $petugas->id];
        }

        if ((int) $payload['v'] !== (int) $petugas->qr_version) {
            return ['ok' => false, 'error' => self::ERROR_STALE_VERSION, 'petugas_id' => $petugas->id];
        }

        return [
            'ok' => true,
            'subject' => ['jenis' => 'petugas', 'id' => $petugas->id],
            'petugas' => $petugas,
            'payload' => $payload,
        ];
    }

    public function regenerate(User $user): User
    {
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'qr_version' => DB::raw('qr_version + 1'),
                'qr_revoked_at' => null,
                'updated_at' => now(),
            ]);

        return $user->refresh();
    }

    public function revoke(User $user): User
    {
        $user->forceFill(['qr_revoked_at' => now()])->save();

        return $user->refresh();
    }

    public function regeneratePetugas(Petugas $petugas): Petugas
    {
        DB::table('petugas')
            ->where('id', $petugas->id)
            ->update([
                'qr_version' => DB::raw('qr_version + 1'),
                'qr_revoked_at' => null,
                'updated_at' => now(),
            ]);

        return $petugas->refresh();
    }

    public function revokePetugas(Petugas $petugas): Petugas
    {
        $petugas->forceFill(['qr_revoked_at' => now()])->save();

        return $petugas->refresh();
    }

    private function encode(string $idKey, int $id, int $version): string
    {
        $payload = [$idKey => $id, 'v' => $version, 'iat' => now()->getTimestamp()];

        $body = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->sign($body);

        return self::PREFIX.'.'.$body.'.'.$signature;
    }

    private function sign(string $body): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $body, $this->secret(), true));
    }

    private function secret(): string
    {
        return hash('sha256', config('app.key'), true);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
