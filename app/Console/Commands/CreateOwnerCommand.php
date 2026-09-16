<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateOwnerCommand extends Command
{
    protected $signature = 'owner:create {name? : The owner\'s name} {email? : The owner\'s email} {password? : The owner\'s password}';

    protected $description = 'Create the single owner account for this PiShock instance';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->error('An owner account already exists. This app supports a single owner; log in and use the profile page to change your details instead.');

            return self::FAILURE;
        }

        $name = $this->argument('name') ?? $this->ask('Name');
        $email = $this->argument('email') ?? $this->ask('Email');
        $password = $this->argument('password') ?? $this->secret('Password');

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->info("Owner account created for {$email}.");

        return self::SUCCESS;
    }
}
