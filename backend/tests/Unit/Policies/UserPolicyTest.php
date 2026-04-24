<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->user->assignRole($this->role);

    Permission::firstOrCreate(['name' => 'Update:User', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'Delete:User', 'guard_name' => 'web']);

    $this->role->givePermissionTo('Update:User');
    $this->role->givePermissionTo('Delete:User');
});

test('superuser cannot update another superuser', function () {
    $policy = new UserPolicy;
    $otherSuperuser = User::factory()->create();
    $otherSuperuser->assignRole($this->role);

    expect($policy->update($this->user, $otherSuperuser))->toBeFalse();
});

test('superuser cannot delete another superuser', function () {
    $policy = new UserPolicy;
    $otherSuperuser = User::factory()->create();
    $otherSuperuser->assignRole($this->role);

    expect($policy->delete($this->user, $otherSuperuser))->toBeFalse();
});

test('superuser can update regular user', function () {
    $policy = new UserPolicy;
    $regularUser = User::factory()->create();

    expect($policy->update($this->user, $regularUser))->toBeTrue();
});

test('superuser can delete regular user', function () {
    $policy = new UserPolicy;
    $regularUser = User::factory()->create();

    expect($policy->delete($this->user, $regularUser))->toBeTrue();
});
