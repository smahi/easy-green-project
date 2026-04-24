<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Wilaya;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class WilayaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Wilaya');
    }

    public function view(AuthUser $authUser, Wilaya $wilaya): bool
    {
        return $authUser->can('View:Wilaya');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Wilaya');
    }

    public function update(AuthUser $authUser, Wilaya $wilaya): bool
    {
        return $authUser->can('Update:Wilaya');
    }

    public function delete(AuthUser $authUser, Wilaya $wilaya): bool
    {
        return $authUser->can('Delete:Wilaya');
    }

    public function restore(AuthUser $authUser, Wilaya $wilaya): bool
    {
        return $authUser->can('Restore:Wilaya');
    }

    public function forceDelete(AuthUser $authUser, Wilaya $wilaya): bool
    {
        return $authUser->can('ForceDelete:Wilaya');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Wilaya');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Wilaya');
    }

    public function replicate(AuthUser $authUser, Wilaya $wilaya): bool
    {
        return $authUser->can('Replicate:Wilaya');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Wilaya');
    }
}
