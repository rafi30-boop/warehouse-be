<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;

class NotifikasiService
{
    private const TIPE_VALID = ['info', 'warning', 'error', 'success'];

    private const PRIORITY_VALID = ['low', 'medium', 'high', 'critical'];

    public function send(int $userId, string $judul, string $pesan, string $tipe = 'info', string $priority = 'medium', ?string $link = null): Notifikasi
    {
        if (! in_array($tipe, self::TIPE_VALID)) {
            $tipe = 'info';
        }

        if (! in_array($priority, self::PRIORITY_VALID)) {
            $priority = 'medium';
        }

        return Notifikasi::create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'priority' => $priority,
            'link' => $link,
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function sendToApprovers(string $permission, string $judul, string $pesan, string $tipe = 'info', ?int $exceptUserId = null, ?string $link = null): int
    {
        $query = User::permission($permission);

        if ($exceptUserId) {
            $query->where('id', '!=', $exceptUserId);
        }

        $count = 0;

        foreach ($query->get() as $user) {
            $this->send($user->id, $judul, $pesan, $tipe, 'medium', $link);
            $count++;
        }

        return $count;
    }
}
