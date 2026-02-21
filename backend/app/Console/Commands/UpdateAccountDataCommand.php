<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class UpdateAccountDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update {email? : The email of the user to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a user account data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? text(
            label: 'Which user email do you want to update?',
            required: true,
        );

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->components->error("User with email [{$email}] not found.");
            return self::FAILURE;
        }

        $this->components->info("Updating user: {$user->name} ({$user->email})");

        $name = text(
            label: 'New name',
            default: $user->name,
            required: true,
        );

        $newEmail = text(
            label: 'New email address',
            default: $user->email,
            required: true,
            validate: fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'The email address must be valid.',
                $value !== $user->email && User::where('email', $value)->exists() => 'The email address has already been taken.',
                default => null
            }
        );

        $passwordInput = password(
            label: 'New password (leave blank to keep current)',
            validate: fn (string $value) => match (true) {
                $value !== '' && strlen($value) < 8 => 'The password must be at least 8 characters.',
                default => null
            }
        );

        $user->name = $name;
        $user->email = $newEmail;
        
        if ($passwordInput !== '') {
            $user->password = Hash::make($passwordInput);
        }

        $user->save();

        $this->components->info('User account data updated successfully.');

        return self::SUCCESS;
    }
}
