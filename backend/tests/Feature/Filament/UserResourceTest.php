<?php

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Livewire\Livewire;

beforeEach(function () {
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    
    $this->role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
    
    $permissions = [
        'ViewAny:User',
        'Create:User',
        'Update:User',
        'Delete:User',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->role->givePermissionTo($permissions);

    $this->user = User::factory()->create();
    $this->user->assignRole($this->role);
});

it('can render user resource list page', function () {
    $this->actingAs($this->user)
        ->get(UserResource::getUrl('index'))
        ->assertSuccessful();
});

it('can render user resource create page', function () {
    $this->actingAs($this->user)
        ->get(UserResource::getUrl('create'))
        ->assertSuccessful();
});

it('can render user resource edit page', function () {
    $userToEdit = User::factory()->create();
    
    $this->actingAs($this->user)
        ->get(UserResource::getUrl('edit', ['record' => $userToEdit]))
        ->assertSuccessful();
});

it('can list users', function () {
    $user = User::factory()->create();

    $this->actingAs($this->user);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$user]);
});
