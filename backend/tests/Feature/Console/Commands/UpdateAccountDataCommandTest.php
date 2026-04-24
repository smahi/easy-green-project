<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;

it('can update user account data', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'password' => Hash::make('oldpassword'),
    ]);

    artisan('user:update', ['email' => 'old@example.com'])
        ->expectsQuestion('New name', 'New Name')
        ->expectsQuestion('New email address', 'new@example.com')
        ->expectsQuestion('New password (leave blank to keep current)', 'newpassword123')
        ->expectsOutputToContain('User account data updated successfully.')
        ->assertSuccessful();

    assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

it('can update user keeping the old password', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'password' => Hash::make('oldpassword'),
    ]);

    artisan('user:update', ['email' => 'old@example.com'])
        ->expectsQuestion('New name', 'New Name')
        ->expectsQuestion('New email address', 'new@example.com')
        ->expectsQuestion('New password (leave blank to keep current)', '')
        ->expectsOutputToContain('User account data updated successfully.')
        ->assertSuccessful();

    $user->refresh();
    expect(Hash::check('oldpassword', $user->password))->toBeTrue();
});

it('can update a superuser via command', function () {
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
    $user = User::factory()->create([
        'name' => 'Super User',
        'email' => 'super@example.com',
    ]);
    $user->assignRole($role);

    artisan('user:update', ['email' => 'super@example.com'])
        ->expectsQuestion('New name', 'Updated Super User')
        ->expectsQuestion('New email address', 'super_updated@example.com')
        ->expectsQuestion('New password (leave blank to keep current)', '')
        ->expectsOutputToContain('User account data updated successfully.')
        ->assertSuccessful();

    $user->refresh();
    expect($user->name)->toBe('Updated Super User');
    expect($user->email)->toBe('super_updated@example.com');
});

it('fails if the user does not exist', function () {
    artisan('user:update', ['email' => 'nonexistent@example.com'])
        ->expectsOutputToContain('User with email [nonexistent@example.com] not found.')
        ->assertFailed();
});
