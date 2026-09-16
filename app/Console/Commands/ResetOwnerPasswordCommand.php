<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetOwnerPasswordCommand extends Command
{
    protected $signature = 'owner:reset-password {password? : The new password}';

    protected $description = "Reset the owner's password";

    public function handle(): int
    {
        $user = User::query()->first();

        if (! $user) {
            $this->error('No owner account exists yet. Run `php artisan owner:create` first.');

            return self::FAILURE;
        }

        $password = $this->argument('password') ?? $this->secret('New password');

        $validator = Validator::make(['password' => $password], [
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user->forceFill(['password' => Hash::make($password)])->save();

        $this->info("Password updated for {$user->email}.");

        return self::SUCCESS;
    }
}
