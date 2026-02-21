<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            WilayasSeeder::class,
        ]);

        // Ensure a superuser exists (if not created by command)
        $superUserRole = config('filament-shield.super_admin.name', 'superuser');
        $role = Role::firstOrCreate(['name' => $superUserRole, 'guard_name' => 'web']);

        if (! User::where('email', 'admin@easygreen.test')->exists()) {
            $user = User::factory()->create([
                'name' => 'System Owner',
                'email' => 'admin@easygreen.test',
                'password' => bcrypt('password'),
            ]);
            $user->assignRole($role);
        }
    }
}
