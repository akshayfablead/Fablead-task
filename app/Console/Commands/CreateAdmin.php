<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create an Admin account';

    public function handle(): int
    {
        $roleExists = Role::where('name', 'Admin')
            ->where('guard_name', 'web')
            ->exists();

        if (! $roleExists) {
            $this->error('Run php artisan db:seed first.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->ask('Admin name'),
            'email' => $this->ask('Admin email'),
            'password' => $this->secret('Password (at least 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255','unique:users,email'],
            'password' => ['required', 'confirmed',
                Password::min(12),
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->assignRole('Admin');
        });

        $this->info('Admin account created successfully.');

        return self::SUCCESS;
    }
}
