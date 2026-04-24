<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Baladya;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BaladyaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Baladya');
    }

    public function view(AuthUser $authUser, Baladya $baladya): bool
    {
        return $authUser->can('View:Baladya');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Baladya');
    }

    public function update(AuthUser $authUser, Baladya $baladya): bool
    {
        return $authUser->can('Update:Baladya');
    }

    public function delete(AuthUser $authUser, Baladya $baladya): bool
    {
        return $authUser->can('Delete:Baladya');
    }

    public function restore(AuthUser $authUser, Baladya $baladya): bool
    {
        return $authUser->can('Restore:Baladya');
    }

    public function forceDelete(AuthUser $authUser, Baladya $baladya): bool
    {
        return $authUser->can('ForceDelete:Baladya');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Baladya');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Baladya');
    }

    public function replicate(AuthUser $authUser, Baladya $baladya): bool
    {
        return $authUser->can('Replicate:Baladya');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Baladya');
    }
}
