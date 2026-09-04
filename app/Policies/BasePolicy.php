<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

abstract class BasePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Separation of Duties: creator cannot approve their own document.
     * Call from controller approve methods:
     *   BasePolicy::denyIfSelfApprove($user, $model)
     */
    public static function denyIfSelfApprove(User $user, Model $model): void
    {
        if (isset($model->created_by) && $model->created_by === $user->id) {
            abort(403, 'Anda tidak dapat menyetujui dokumen yang Anda buat sendiri (Separation of Duties).');
        }
    }

    /**
     * Anti-eskalasi: aktor hanya boleh memberikan role yang permission-nya
     * merupakan subset dari permission yang ia miliki sendiri.
     * super-admin bebas. Dipakai saat assign/sync role ke user.
     */
    public static function denyUngrantedRoleAssignment(User $actor, array $roleNames): void
    {
        if ($actor->hasRole('super-admin')) {
            return;
        }

        $actorPerms = $actor->getAllPermissions()->pluck('name')->all();

        foreach ($roleNames as $roleName) {
            $role = Role::findByName($roleName, 'api');

            foreach ($role->permissions->pluck('name') as $perm) {
                if (! in_array($perm, $actorPerms, true)) {
                    abort(403, "Anda tidak dapat memberikan role '{$roleName}' karena mengandung permission '{$perm}' yang Anda tidak miliki.");
                }
            }
        }
    }

    /**
     * Anti-eskalasi: aktor hanya boleh memberikan permission yang ia miliki sendiri.
     * super-admin bebas. Dipakai saat sync permission ke role.
     */
    public static function denyUngrantedPermissionGrant(User $actor, array $permissionNames): void
    {
        if ($actor->hasRole('super-admin')) {
            return;
        }

        $actorPerms = $actor->getAllPermissions()->pluck('name')->all();

        foreach ($permissionNames as $perm) {
            if (! in_array($perm, $actorPerms, true)) {
                abort(403, "Anda tidak dapat memberikan permission '{$perm}' karena Anda tidak memilikinya.");
            }
        }
    }

    /**
     * Role sistem tidak boleh di-rename/di-hapus agar hardcode FE,
     * seeder, dan alur auth tidak rusak.
     */
    public const SYSTEM_ROLES = ['super-admin', 'admin', 'operator'];

    public static function denySystemRoleRename(Role $role, ?string $newName): void
    {
        if ($newName !== null && $newName !== $role->name && in_array($role->name, self::SYSTEM_ROLES, true)) {
            abort(403, "Role sistem '{$role->name}' tidak dapat diubah namanya.");
        }
    }

    public static function denySystemRoleDelete(Role $role): void
    {
        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            abort(403, "Role sistem '{$role->name}' tidak dapat dihapus.");
        }
    }

    /**
     * Jangan sampai super-admin terakhir kehilangan akses
     * (via hapus role, nonaktif, atau hapus akun).
     */
    public static function denyLastSuperAdminLoss(User $target): void
    {
        if (! $target->hasRole('super-admin')) {
            return;
        }

        if (User::role('super-admin')->count() <= 1) {
            abort(403, 'Tidak dapat mencabut akses super-admin terakhir. Buat super-admin lain terlebih dahulu.');
        }
    }

    /**
     * Gudang scoping saat write: user yang ter-assign ke satu gudang
     * hanya boleh mencatat transaksi untuk gudangnya sendiri.
     * super-admin/admin bebas. User tanpa gudang (null) tidak dikunci.
     */
    public static function denyCrossGudangWrite(User $actor, ?int $gudangId): void
    {
        if ($actor->hasRole('super-admin') || $actor->hasRole('admin')) {
            return;
        }

        if ($actor->gudang_id === null || $gudangId === null) {
            return;
        }

        if ((int) $gudangId !== (int) $actor->gudang_id) {
            abort(403, 'Anda hanya dapat mencatat transaksi untuk gudang Anda sendiri.');
        }
    }
}
