<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdmin extends Command
{
    protected $signature = 'platform:create-admin';

    protected $description =
        'Create an independent platform administrator';

    public function handle(): int
    {
        $name = $this->ask(
            'Administrator name',
            'Platform Administrator'
        );

        $email = $this->ask('Administrator email');

        $password = $this->secret('Administrator password');

        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:12|max:72|confirmed',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User();

        $user->forceFill([
            'name' => $name,
            'email' => strtolower(trim($email)),
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $user->save();

        $this->info(
            'Platform administrator created successfully.'
        );

        $this->info('User ID: ' . $user->id);
        $this->info('Role: ' . $user->role);

        return self::SUCCESS;
    }
}