<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Leadership & Management Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@cic.ac.id'],
            [
                'name'              => 'Admin CIC',
                'role'              => 'Admin',
                'password'          => Hash::make('password'),
                'status'            => 'aktif',
                'email_verified_at' => now(),
            ]
        );

        $hm = User::updateOrCreate(
            ['email' => 'hm@cic.ac.id'],
            [
                'name'              => 'HM CIC',
                'role'              => 'HM',
                'password'          => Hash::make('password'),
                'status'            => 'aktif',
                'email_verified_at' => now(),
            ]
        );

        $spv = User::updateOrCreate(
            ['email' => 'spv@cic.ac.id'],
            [
                'name'              => 'Hendra Setiawan, S.Kom',
                'role'              => 'SPV',
                'password'          => Hash::make('password'),
                'status'            => 'aktif',
                'email_verified_at' => now(),
            ]
        );

        $cs = User::updateOrCreate(
            ['email' => 'cs@cic.ac.id'],
            [
                'name'              => 'Dina Marlina',
                'role'              => 'CS',
                'password'          => Hash::make('password'),
                'status'            => 'aktif',
                'supervisor_id'     => $spv->id,
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Sales Team Members under SPV
        $salesMembers = [
            ['name' => 'Sales CIC',     'email' => 'sales@cic.ac.id'],
            ['name' => 'Aurel Calista', 'email' => 'aurel.calista@cic.ac.id'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky.pratama@cic.ac.id'],
            ['name' => 'Budi Santoso',  'email' => 'budi.santoso@cic.ac.id'],
        ];

        foreach ($salesMembers as $sales) {
            User::updateOrCreate(
                ['email' => $sales['email']],
                [
                    'name'              => $sales['name'],
                    'role'              => 'Sales',
                    'password'          => Hash::make('password'),
                    'status'            => 'aktif',
                    'supervisor_id'     => $spv->id,
                    'email_verified_at' => now(),
                ]
            );
        }

        // 3. Ensure any existing Sales without supervisor get attached to SPV
        User::where('role', 'Sales')->whereNull('supervisor_id')->update(['supervisor_id' => $spv->id]);
    }
}
