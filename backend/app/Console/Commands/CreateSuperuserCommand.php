<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperuserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-superuser {--name=} {--email=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a superuser account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?? text(
            label: 'What is the superuser name?',
            placeholder: 'John Doe',
            required: true,
        );

        $email = $this->option('email') ?? text(
            label: 'What is the superuser email address?',
            placeholder: 'admin@example.com',
            required: true,
            validate: fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'The email address must be valid.',
                User::where('email', $value)->exists() => 'The email address has already been taken.',
                default => null
            }
        );

        $passwordInput = $this->option('password') ?? password(
            label: 'What is the superuser password?',
            placeholder: 'minimum 8 characters',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) < 8 => 'The password must be at least 8 characters.',
                default => null
            }
        );

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($passwordInput),
        ]);

        $roleName = config('filament-shield.super_admin.name', 'superuser');

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->components->info('Superuser created successfully.');
        
        return self::SUCCESS;
    }
}
