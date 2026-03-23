<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        Branch::create([
            'name'      => 'Main Warehouse',
            'address'   => 'Main Office',
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'Admin',
            'email'     => 'admin@snackerp.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'branch_id' => null,
        ]);
    }
}