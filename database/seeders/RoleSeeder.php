<?php

namespace Database\Seeders;

use App\Constants\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all roles in a single, scalable list
        $roles = [
            Roles::ROLE_ADMIN,
            Roles::ROLE_STAFF,
        ];

        // Loop and insert safely to prevent duplicate entry exceptions
        foreach ($roles as $role) {
            Role::firstOrCreate([
                'public_id' => Str::uuid7(),
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
