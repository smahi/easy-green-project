<?php

use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);

    $permissions = [
        'ViewAny:Report',
        'Create:Report',
        'Update:Report',
        'Delete:Report',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->role->givePermissionTo($permissions);

    $this->user = User::factory()->create();
    $this->user->assignRole($this->role);
});

it('can render report resource list page', function () {
    $this->actingAs($this->user)
        ->get(ReportResource::getUrl('index'))
        ->assertSuccessful();
});

it('can create a report', function () {
    $reportType = ReportType::create([
        'name' => ['en' => 'Fire'],
        'severity_level' => 5,
        'is_active' => true,
        'color' => '#ff0000',
    ]);

    $this->actingAs($this->user);

    $report = Report::create([
        'user_id' => $this->user->id,
        'report_type_id' => $reportType->id,
        'description' => 'Fire in the north field',
        'latitude' => 36.7525,
        'longitude' => 3.0420,
        'status' => 'new',
    ]);

    $this->assertDatabaseHas('reports', [
        'description' => 'Fire in the north field',
        'status' => 'new',
    ]);
});
