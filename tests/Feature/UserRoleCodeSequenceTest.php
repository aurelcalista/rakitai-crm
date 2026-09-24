<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleCodeSequenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_code_sequence_is_independent_per_role()
    {
        $ymPrefix = date('ym');

        // Create Admin 1
        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'status' => 'Aktif',
        ]);
        $this->assertEquals("{$ymPrefix}A001", $admin1->kode);

        // Create Admin 2
        $admin2 = User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'status' => 'Aktif',
        ]);
        $this->assertEquals("{$ymPrefix}A002", $admin2->kode);

        // Create HM 1
        $hm1 = User::create([
            'name' => 'HM One',
            'email' => 'hm1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'HM',
            'status' => 'Aktif',
        ]);
        $this->assertEquals("{$ymPrefix}H001", $hm1->kode);

        // Create SPV 1
        $spv1 = User::create([
            'name' => 'SPV One',
            'email' => 'spv1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'SPV',
            'status' => 'Aktif',
        ]);
        $this->assertEquals("{$ymPrefix}V001", $spv1->kode);

        // Create Sales 1
        $sales1 = User::create([
            'name' => 'Sales One',
            'email' => 'sales1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'Aktif',
        ]);
        $this->assertEquals("{$ymPrefix}S001", $sales1->kode);

        // Create Sales 2
        $sales2 = User::create([
            'name' => 'Sales Two',
            'email' => 'sales2@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'Aktif',
        ]);
        $this->assertEquals("{$ymPrefix}S002", $sales2->kode);

        // Create CS 1
        $cs1 = User::create([
            'name' => 'CS One',
            'email' => 'cs1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'CS',
            'status' => 'Aktif',
        ]);
        $this->assertEquals("{$ymPrefix}C001", $cs1->kode);

        // Create EO 1
        $eo1 = User::create([
            'name' => 'EO One',
            'email' => 'eo1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'EO',
            'status' => 'Aktif',
        ]);
        $this->assertEquals("{$ymPrefix}E001", $eo1->kode);
    }
}
