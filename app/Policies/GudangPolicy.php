<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Gudang;

class GudangPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gudang-list');
    }

    public function view(User $user, Gudang $gudang): bool
    {
        return $user->can('gudang-list');
    }

    public function create(User $user): bool
    {
        return $user->can('gudang-create');
    }

    public function update(User $user, Gudang $gudang): bool
    {
        return $user->can('gudang-edit');
    }

    public function delete(User $user, Gudang $gudang): bool
    {
        return $user->can('gudang-delete');
    }
}
