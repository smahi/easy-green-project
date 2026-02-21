<?php

use App\Filament\Resources\ReportTypes\ReportTypeResource;
use App\Models\ReportType;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    
    $this->role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
    
    $permissions = [
        'ViewAny:ReportType',
        'Create:ReportType',
        'Update:ReportType',
        'Delete:ReportType',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->role->givePermissionTo($permissions);

    $this->user = User::factory()->create();
    $this->user->assignRole($this->role);
});

it('can render report type resource list page', function () {
    $this->actingAs($this->user)
        ->get(ReportTypeResource::getUrl('index'))
        ->assertSuccessful();
});

it('can render report type resource create page', function () {
    $this->actingAs($this->user)
        ->get(ReportTypeResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create a report type', function () {
    $this->actingAs($this->user);
    
    ReportType::create([
        'name' => ['en' => 'Locust', 'ar' => 'جراد', 'fr' => 'Criquet'],
        'severity_level' => 5,
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('report_types', [
        'severity_level' => 5,
    ]);
});
