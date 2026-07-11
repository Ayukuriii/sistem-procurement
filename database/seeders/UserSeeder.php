<?php

namespace Database\Seeders;

use App\Constants\Roles;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => Roles::ROLE_ADMIN]);
        $staffRole = Role::firstOrCreate(['name' => Roles::ROLE_STAFF]);
        $passwordHash = Hash::make('password123');

        $admins = [
            [
                'public_id' => Str::uuid7(),
                'name' => 'Ekadian haris',
                'email' => 'ekadianharis@gmail.com',
                'password' => $passwordHash,
            ],
            [
                'public_id' => Str::uuid7(),
                'name' => 'Admin 1',
                'email' => 'haris@gmail.com',
                'password' => $passwordHash,
            ],
            [
                'public_id' => Str::uuid7(),
                'name' => 'Admin 2',
                'email' => 'admin2@gmail.com',
                'password' => $passwordHash,
            ],
        ];

        foreach ($admins as $admin) {
            $user = User::create($admin);
            info($user);
            $user->assignRole($adminRole);
        }

        $staffCount = 5;

        for ($i = 0; $i < $staffCount; $i++) {
            $user = User::create([
                'public_id' => Str::uuid7(),
                'name' => fake('id')->name(),
                'email' => fake('id')->safeEmail(),
                'password' => $passwordHash,
            ]);
            $user->assignRole($staffRole);
        }
    }
}
