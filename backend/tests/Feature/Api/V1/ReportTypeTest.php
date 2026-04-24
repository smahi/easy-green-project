<?php

use App\Models\ReportType;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'farmer', 'guard_name' => 'web']);
});

it('prevents unauthenticated users from fetching report types', function () {
    getJson('/api/v1/report-types')->assertStatus(401);
});

it('allows authenticated users to fetch report types', function () {
    $user = User::factory()->create();
    $user->assignRole('farmer');

    ReportType::factory()->count(3)->create(['is_active' => true]);
    ReportType::factory()->create(['is_active' => false]);

    $response = actingAs($user, 'sanctum')->getJson('/api/v1/report-types');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'severity_level',
                    'icon',
                    'color',
                ],
            ],
            'meta' => ['version'],
        ]);
});
