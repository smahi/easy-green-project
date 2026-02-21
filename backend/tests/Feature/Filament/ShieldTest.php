<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;

beforeEach(function () {
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    
    $this->role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
    
    $permissions = [
        'ViewAny:Role',
        'Create:Role',
        'Update:Role',
        'Delete:Role',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->role->givePermissionTo($permissions);

    $this->user = User::factory()->create();
    $this->user->assignRole($this->role);
});

it('can access shield role resource', function () {
    $this->actingAs($this->user)
        ->get(RoleResource::getUrl('index'))
        ->assertSuccessful();
});

it('can create a role via shield', function () {
    $this->actingAs($this->user)
        ->get(RoleResource::getUrl('create'))
        ->assertSuccessful();
});
