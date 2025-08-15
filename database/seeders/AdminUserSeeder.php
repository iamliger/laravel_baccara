<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $adminUser = User::create([
            'name' => 'admin',
            'email' => 'admin@domain.com',
            'password' => Hash::make('eoqkr007'),
        ]);

        $adminRole = Role::findByName('Admin');
        $adminUser->assignRole($adminRole);
    }
}
