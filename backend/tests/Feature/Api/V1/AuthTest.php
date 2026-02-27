<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('can login with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password123',
        'device_name' => 'test_device',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
            ],
        ]);
});

it('cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('requires email and password for login', function () {
    $response = postJson('/api/v1/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('can fetch the authenticated user', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = getJson('/api/v1/user');

    $response->assertStatus(200)
        ->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
});

it('can logout successfully', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Successfully logged out',
        ]);

    expect($user->tokens()->count())->toBe(0);
});

it('cannot logout if not authenticated', function () {
    $response = postJson('/api/v1/logout');

    $response->assertStatus(401);
});
