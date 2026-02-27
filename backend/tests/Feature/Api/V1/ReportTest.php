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
    Role::firstOrCreate(['name' => 'farmer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'inspector', 'guard_name' => 'web']);
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
                'user_id',
                'report_type_id',
                'status',
                'media_attachments',
            ],
        ])
        ->assertJsonFragment([
            'status' => 'new',
            'user_id' => $farmer->id,
            'report_type_id' => $reportType->id,
            'latitude' => '36.75250000',
            'longitude' => '3.04197000',
            'is_synchronized' => true,
        ]);

    $report = Report::first();
    expect($report->media_attachments)->toBeArray();
    expect(count($report->media_attachments))->toBe(1);

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
