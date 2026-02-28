<?php

use App\Models\ReportType;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('superuser can create a report', function () {
    $this->browse(function (Browser $browser) {
        $superuser = User::firstOrCreate(
            ['email' => 'superuser_dusk@example.com'],
            ['password' => bcrypt('password'), 'name' => 'Super User']
        );
        
        $role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
        
        // Ensure permissions exist
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'ViewAny:Report',
            'Create:Report',
            'Update:Report',
            'Delete:Report',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $role->givePermissionTo($permissions);
        
        $superuser->assignRole($role);

        // Create a ReportType to select
        $reportType = ReportType::create([
            'name' => ['en' => 'Dusk Test Type'],
            'severity_level' => 1,
            'is_active' => true,
            'color' => '#00ff00',
        ]);

        $browser->loginAs($superuser)
            ->visit('/admin/reports/create')
            ->assertSee('Create report')
            ->select('data[user_id]', $superuser->id)
            ->select('data[report_type_id]', $reportType->id)
            ->type('data[description]', 'This is a dusk test report')
            ->type('data[latitude]', '36.12345678')
            ->type('data[longitude]', '3.12345678')
            ->select('data[status]', 'new')
            ->press('Create')
            ->waitForText('created')
            ->assertPathIs('/admin/reports');
    });
});
