<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'farmer' => 'Responsible for reporting problems via mobile app.',
            'inspector' => 'Field agents who verify and update reports.',
            'admin' => 'Regional Agricultural Administration managers.',
            'ministry' => 'Higher Authority / Analytics viewer.',
        ];

        foreach ($roles as $role => $description) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
