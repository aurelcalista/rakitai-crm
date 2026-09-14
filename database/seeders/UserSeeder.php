<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin CIC',  'email' => 'admin@cic.ac.id', 'role' => 'Admin'],
            ['name' => 'HM CIC',     'email' => 'hm@cic.ac.id',    'role' => 'HM'],
            ['name' => 'SPV CIC',    'email' => 'spv@cic.ac.id',   'role' => 'SPV'],
            ['name' => 'Sales CIC',  'email' => 'sales@cic.ac.id', 'role' => 'Sales'],
            ['name' => 'CS CIC',     'email' => 'cs@cic.ac.id',    'role' => 'CS'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'              => $user['name'],
                    'role'              => $user['role'],
                    'password'          => Hash::make('password'),
                    'status'            => 'aktif',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}

