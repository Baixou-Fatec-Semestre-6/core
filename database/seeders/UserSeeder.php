<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'root@localhost'],
            [
                'name' => 'root',
                'password' => 'root',
                'email_verified_at' => now(),
                'status' => UserStatus::ACTIVE,
                'role' => UserRole::SUPER_ADMIN,
            ]
        );

    }
}
