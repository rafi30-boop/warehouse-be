<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

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
}
