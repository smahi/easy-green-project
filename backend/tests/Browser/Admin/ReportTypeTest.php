<?php

use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

test('superuser can create a report type', function () {
    $this->browse(function (Browser $browser) {
        $superuser = User::firstOrCreate(
            ['email' => 'superuser_dusk@example.com'],
            ['password' => bcrypt('password'), 'name' => 'Super User']
        );
        
        $role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
        
        // Ensure permissions exist
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'ViewAny:ReportType',
            'Create:ReportType',
            'Update:ReportType',
            'Delete:ReportType',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $role->givePermissionTo($permissions);
        
        $superuser->assignRole($role);

        $browser->loginAs($superuser)
            ->visit('/admin/report-types/create')
            ->assertSee('Create report type')
            ->type('data[name][en]', 'Locust')
            ->type('data[name][ar]', 'جراد')
            ->type('data[name][fr]', 'Criquet')
            ->type('data[color]', '#ff0000')
            ->select('data[severity_level]', '5')
            ->press('Create')
            ->waitForText('created') // Wait for notification
            ->assertPathIs('/admin/report-types');
    });
});
