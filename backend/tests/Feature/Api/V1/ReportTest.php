<?php

use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    // Ensure roles exist for tests
    $farmerRole = Role::firstOrCreate(['name' => 'farmer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'inspector', 'guard_name' => 'web']);

    // Ensure permissions exist and are assigned
    $createPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'Create:Report', 'guard_name' => 'web']);
    $farmerRole->givePermissionTo($createPermission);

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
});

it('prevents unauthenticated users from creating reports', function () {
    postJson('/api/v1/reports', [])->assertStatus(401);
});

it('prevents non-farmers from creating reports', function () {
    $inspector = User::factory()->create();
    $inspector->assignRole('inspector');

    actingAs($inspector, 'sanctum')
        ->postJson('/api/v1/reports', [])
        ->assertStatus(403);
});

it('allows farmers to create reports with valid data', function () {
    Storage::fake('public');

    $farmer = User::factory()->create();
    $farmer->assignRole('farmer');
    $reportType = ReportType::factory()->create();

    $file = UploadedFile::fake()->image('pest.jpg');

    $response = actingAs($farmer, 'sanctum')
        ->postJson('/api/v1/reports', [
            'report_type_id' => $reportType->id,
            'description' => 'A terrible pest in my farm',
            'latitude' => 36.7525,
            'longitude' => 3.04197,
            'is_synchronized' => true,
            'media_attachments' => [$file],
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'report' => [
                'id',
                'report_type' => [
                    'id',
                    'name',
                ],
                'description',
                'status',
                'media_attachments',
            ],
        ])
        ->assertJsonFragment([
            'status' => 'new',
            'latitude' => '36.75250000',
            'longitude' => '3.04197000',
            'is_synchronized' => true,
        ]);

    $report = Report::first();
    expect($report->media_attachments)->toBeArray();
    expect(count($report->media_attachments))->toBe(1);
    expect($response->json('report.description.en'))->toBe('A terrible pest in my farm');
    expect($response->json('report.media_attachments.0'))->toContain(Storage::disk('public')->url($report->media_attachments[0]));

    Storage::disk('public')->assertExists($report->media_attachments[0]);
});

it('requires essential fields to create a report', function () {
    $farmer = User::factory()->create();
    $farmer->assignRole('farmer');

    actingAs($farmer, 'sanctum')
        ->postJson('/api/v1/reports', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'report_type_id',
            'latitude',
            'longitude',
        ]);
});

it('ignores any status provided by the user', function () {
    $farmer = User::factory()->create();
    $farmer->assignRole('farmer');
    $reportType = ReportType::factory()->create();

    $response = actingAs($farmer, 'sanctum')
        ->postJson('/api/v1/reports', [
            'report_type_id' => $reportType->id,
            'latitude' => 36.7525,
            'longitude' => 3.04197,
            'status' => 'resolved', // Should be ignored
        ]);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'status' => 'new',
        ]);

    $report = Report::first();
    expect($report->status)->toBe('new');
});

it('allows farmers to fetch their own report history', function () {
    $farmer = User::factory()->create();
    $farmer->assignRole('farmer');
    $reportType = ReportType::factory()->create();

    Report::factory()->count(3)->create([
        'user_id' => $farmer->id,
        'report_type_id' => $reportType->id,
        'description' => ['en' => 'Test description'],
    ]);

    // Another user's report that shouldn't be visible
    Report::factory()->create([
        'user_id' => User::factory()->create()->id,
        'report_type_id' => $reportType->id,
        'description' => ['en' => 'Other description'],
    ]);

    $response = actingAs($farmer, 'sanctum')->getJson('/api/v1/reports');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'report_type' => ['id', 'name'],
                    'description',
                    'latitude',
                    'longitude',
                    'status',
                ],
            ],
            'meta',
            'links',
        ]);

    expect($response->json('data.0.description.en'))->toBe('Test description');
});

it('allows farmers to sync multiple reports at once', function () {
    $farmer = User::factory()->create();
    $farmer->assignRole('farmer');
    $reportType = ReportType::factory()->create();

    $payload = [
        'reports' => [
            [
                'report_type_id' => $reportType->id,
                'description' => 'First offline report',
                'latitude' => 36.1,
                'longitude' => 3.1,
                'created_at' => now()->subDay()->toDateTimeString(),
            ],
            [
                'report_type_id' => $reportType->id,
                'description' => 'Second offline report',
                'latitude' => 36.2,
                'longitude' => 3.2,
            ],
        ],
    ];

    $response = actingAs($farmer, 'sanctum')->postJson('/api/v1/reports/bulk', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Reports synchronized successfully',
            'count' => 2,
        ]);

    expect(Report::where('user_id', $farmer->id)->count())->toBe(2);
});

it('prevents duplicate reports using client_uuid (idempotency)', function () {
    $farmer = User::factory()->create();
    $farmer->assignRole('farmer');
    $reportType = ReportType::factory()->create();
    $uuid = (string) str()->uuid();

    $payload = [
        'client_uuid' => $uuid,
        'report_type_id' => $reportType->id,
        'description' => 'First attempt',
        'latitude' => 36.1,
        'longitude' => 3.1,
    ];

    // First submission
    actingAs($farmer, 'sanctum')->postJson('/api/v1/reports', $payload)->assertStatus(201);
    expect(Report::count())->toBe(1);

    // Second submission with same UUID
    $payload['description'] = 'Second attempt (retry)';
    actingAs($farmer, 'sanctum')->postJson('/api/v1/reports', $payload)->assertStatus(201);

    // Count should still be 1
    expect(Report::count())->toBe(1);
    expect(Report::first()->getTranslation('description', 'en'))->toBe('Second attempt (retry)');
});

it('validates bulk report requests', function () {
    $farmer = User::factory()->create();
    $farmer->assignRole('farmer');

    actingAs($farmer, 'sanctum')
        ->postJson('/api/v1/reports/bulk', ['reports' => []])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reports']);

    actingAs($farmer, 'sanctum')
        ->postJson('/api/v1/reports/bulk', [
            'reports' => [
                ['description' => 'Missing fields'],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'reports.0.report_type_id',
            'reports.0.latitude',
            'reports.0.longitude',
        ]);
});
