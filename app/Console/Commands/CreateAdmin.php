<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {password? : Omit to be prompted securely}';

    protected $description = 'Create a new admin account';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        if ($password === null) {
            $password = $this->secret('Password (min 12 characters, input hidden)');
            $confirmation = $this->secret('Confirm password');

            if ($password !== $confirmation) {
                $this->error('Passwords do not match.');
                return 1;
            }
        } else {
            $this->warn('Passing the password as an argument leaves it in your shell history. Omit it to be prompted instead.');
        }

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ], [
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:12',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  ' . $error);
            }
            return 1;
        }

        $admin = Admin::create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Admin created successfully:");
        $this->info("  Email: {$admin->email}");
        $this->info("  ID: {$admin->id}");

        return 0;
    }
}
