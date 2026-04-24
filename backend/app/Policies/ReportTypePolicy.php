<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReportType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReportTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReportType');
    }

    public function view(AuthUser $authUser, ReportType $reportType): bool
    {
        return $authUser->can('View:ReportType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReportType');
    }

    public function update(AuthUser $authUser, ReportType $reportType): bool
    {
        return $authUser->can('Update:ReportType');
    }

    public function delete(AuthUser $authUser, ReportType $reportType): bool
    {
        return $authUser->can('Delete:ReportType');
    }

    public function restore(AuthUser $authUser, ReportType $reportType): bool
    {
        return $authUser->can('Restore:ReportType');
    }

    public function forceDelete(AuthUser $authUser, ReportType $reportType): bool
    {
        return $authUser->can('ForceDelete:ReportType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReportType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReportType');
    }

    public function replicate(AuthUser $authUser, ReportType $reportType): bool
    {
        return $authUser->can('Replicate:ReportType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReportType');
    }
}
