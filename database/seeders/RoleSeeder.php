<?php

namespace Database\Seeders;

use App\Constants\Roles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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
            'name' => $role,
            'guard_name' => 'web',
        ]);
    }
    }
}
