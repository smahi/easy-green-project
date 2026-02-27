<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Report;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Report');
    }

    public function view(AuthUser $authUser, Report $report): bool
    {
        return $authUser->can('View:Report');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, Report $report): bool
    {
        return $authUser->can('Update:Report');
    }

    public function delete(AuthUser $authUser, Report $report): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, Report $report): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Report $report): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, Report $report): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}
