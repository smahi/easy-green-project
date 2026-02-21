<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\RolePolicy;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->superuserRoleName = config('filament-shield.super_admin.name', 'superuser');
    $this->superuserRole = Role::firstOrCreate(['name' => $this->superuserRoleName, 'guard_name' => 'web']);
    
    $this->user = User::factory()->create();
    $this->user->assignRole($this->superuserRole);

    Permission::firstOrCreate(['name' => 'Update:Role', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'Delete:Role', 'guard_name' => 'web']);
    
    $this->superuserRole->givePermissionTo('Update:Role');
    $this->superuserRole->givePermissionTo('Delete:Role');
});

test('superuser cannot update the superuser role', function () {
    $policy = new RolePolicy();
    expect($policy->update($this->user, $this->superuserRole))->toBeFalse();
});

test('superuser cannot delete the superuser role', function () {
    $policy = new RolePolicy();
    expect($policy->delete($this->user, $this->superuserRole))->toBeFalse();
});

test('superuser can update other roles', function () {
    $policy = new RolePolicy();
    $otherRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    expect($policy->update($this->user, $otherRole))->toBeTrue();
});

test('superuser can delete other roles', function () {
    $policy = new RolePolicy();
    $otherRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    expect($policy->delete($this->user, $otherRole))->toBeTrue();
});
