<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;

it('can create a superuser interactively', function () {
    artisan('user:create-superuser')
        ->expectsQuestion('What is the superuser name?', 'Super Admin')
        ->expectsQuestion('What is the superuser email address?', 'admin@example.com')
        ->expectsQuestion('What is the superuser password?', 'password123')
        ->expectsOutputToContain('Superuser created successfully.')
        ->assertSuccessful();

    assertDatabaseHas('users', [
        'name' => 'Super Admin',
        'email' => 'admin@example.com',
    ]);

    $user = User::where('email', 'admin@example.com')->first();
    expect($user->hasRole('superuser'))->toBeTrue();
});

it('can create a superuser using options', function () {
    artisan('user:create-superuser', [
        '--name' => 'Option Admin',
        '--email' => 'options@example.com',
        '--password' => 'password123',
    ])
    ->expectsOutputToContain('Superuser created successfully.')
    ->assertSuccessful();

    assertDatabaseHas('users', [
        'name' => 'Option Admin',
        'email' => 'options@example.com',
    ]);

    $user = User::where('email', 'options@example.com')->first();
    expect($user->hasRole('superuser'))->toBeTrue();
});