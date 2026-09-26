<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $parentWilayahs = Wilayah::whereNull('parent_id')->get();
        $defaultWilayah = Wilayah::where('kode', 'W-CRB')->first() ?? $parentWilayahs->first();
        $wilayahId = $defaultWilayah?->id;
        $kecamatans = Wilayah::whereNotNull('parent_id')->get();

        // 1. Admin Users (2 users)
        $admins = [
            ['name' => 'Admin Utama CRM',       'email' => 'admin@cic.ac.id',  'phone' => '081122334455'],
            ['name' => 'Admin System Support',  'email' => 'admin2@cic.ac.id', 'phone' => '081122334456'],
        ];

        foreach ($admins as $aData) {
            User::updateOrCreate(
                ['email' => $aData['email']],
                [
                    'name'              => $aData['name'],
                    'role'              => 'Admin',
                    'password'          => Hash::make('password'),
                    'status'            => 'Aktif',
                    'phone'             => $aData['phone'],
                    'email_verified_at' => now(),
                ]
            );
        }

        // 2. Head of Marketing (HM Users - 3 users)
        $hmsData = [
            ['name' => 'HM Marketing Eksekutif (Cirebon)',     'email' => 'hm@cic.ac.id',  'phone' => '081234567890'],
            ['name' => 'HM Marketing (Majalengka & Kuningan)', 'email' => 'hm2@cic.ac.id', 'phone' => '081234567891'],
            ['name' => 'HM Marketing (Indramayu)',             'email' => 'hm3@cic.ac.id', 'phone' => '081234567892'],
        ];

        $hmList = [];
        foreach ($hmsData as $idx => $hData) {
            $assignedW = $parentWilayahs->isNotEmpty() ? $parentWilayahs[$idx % $parentWilayahs->count()] : $defaultWilayah;
            $hmUser = User::updateOrCreate(
                ['email' => $hData['email']],
                [
                    'name'              => $hData['name'],
                    'role'              => 'HM',
                    'password'          => Hash::make('password'),
                    'status'            => 'Aktif',
                    'phone'             => $hData['phone'],
                    'wilayah_id'        => $assignedW?->id ?? $wilayahId,
                    'email_verified_at' => now(),
                ]
            );
            $hmList[] = $hmUser;

            if ($assignedW) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $hmUser->id, 'wilayah_id' => $assignedW->id, 'role' => 'HM'],
                    ['is_active' => true, 'assigned_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // 3. Supervisor (SPV Users - 3 users)
        $spvsData = [
            ['name' => 'Hendra Setiawan, S.Kom (SPV)', 'email' => 'spv@cic.ac.id',  'phone' => '081398765432'],
            ['name' => 'Maya Kartika, M.M (SPV)',       'email' => 'spv2@cic.ac.id', 'phone' => '081398765433'],
            ['name' => 'Rian Hidayat, S.T (SPV)',       'email' => 'spv3@cic.ac.id', 'phone' => '081398765434'],
        ];

        $spvList = [];
        foreach ($spvsData as $idx => $sData) {
            $assignedW = $parentWilayahs->isNotEmpty() ? $parentWilayahs[$idx % $parentWilayahs->count()] : $defaultWilayah;
            $spvUser = User::updateOrCreate(
                ['email' => $sData['email']],
                [
                    'name'              => $sData['name'],
                    'role'              => 'SPV',
                    'password'          => Hash::make('password'),
                    'status'            => 'Aktif',
                    'phone'             => $sData['phone'],
                    'wilayah_id'        => $assignedW?->id ?? $wilayahId,
                    'email_verified_at' => now(),
                ]
            );
            $spvList[] = $spvUser;

            if ($assignedW) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $spvUser->id, 'wilayah_id' => $assignedW->id, 'role' => 'SPV'],
                    ['is_active' => true, 'assigned_at' => now(), 'updated_at' => now()]
                );
            }
        }
        $primarySpv = $spvList[0] ?? null;

        // 4. Event Organizer (EO Users - 2 users)
        $eosData = [
            ['name' => 'Tim Event Organizer (EO Utama)', 'email' => 'eo@cic.ac.id',  'phone' => '081566778899'],
            ['name' => 'EO Event Pameran & Expo',        'email' => 'eo2@cic.ac.id', 'phone' => '081566778800'],
        ];

        foreach ($eosData as $eData) {
            User::updateOrCreate(
                ['email' => $eData['email']],
                [
                    'name'              => $eData['name'],
                    'role'              => 'EO',
                    'password'          => Hash::make('password'),
                    'status'            => 'Aktif',
                    'phone'             => $eData['phone'],
                    'wilayah_id'        => $wilayahId,
                    'email_verified_at' => now(),
                ]
            );
        }

        // 5. Customer Service (CS Users - 3 users)
        $csData = [
            ['name' => 'Dina Marlina (CS)',  'email' => 'cs@cic.ac.id',  'phone' => '081711223344'],
            ['name' => 'Siti Nurhaliza (CS)', 'email' => 'cs2@cic.ac.id', 'phone' => '081711223345'],
            ['name' => 'Amanda Putri (CS)',   'email' => 'cs3@cic.ac.id', 'phone' => '081711223346'],
        ];

        foreach ($csData as $idx => $cData) {
            $assignedSpv = $spvList[$idx % count($spvList)];
            $assignedW = $parentWilayahs->isNotEmpty() ? $parentWilayahs[$idx % $parentWilayahs->count()] : $defaultWilayah;

            $csUser = User::updateOrCreate(
                ['email' => $cData['email']],
                [
                    'name'              => $cData['name'],
                    'role'              => 'CS',
                    'password'          => Hash::make('password'),
                    'status'            => 'Aktif',
                    'phone'             => $cData['phone'],
                    'supervisor_id'     => $assignedSpv->id,
                    'wilayah_id'        => $assignedW?->id ?? $wilayahId,
                    'email_verified_at' => now(),
                ]
            );

            if ($assignedW) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $csUser->id, 'wilayah_id' => $assignedW->id, 'role' => 'CS'],
                    ['is_active' => true, 'assigned_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // 6. Sales Team Members (8 users)
        $salesMembers = [
            ['name' => 'Sales Utama CIC',   'email' => 'sales@cic.ac.id',           'phone' => '081234112233'],
            ['name' => 'Aurel Calista',    'email' => 'aurel.calista@cic.ac.id',   'phone' => '081234445566'],
            ['name' => 'Rizky Pratama',    'email' => 'rizky.pratama@cic.ac.id',   'phone' => '081234778899'],
            ['name' => 'Budi Santoso',     'email' => 'budi.santoso@cic.ac.id',    'phone' => '081234001122'],
            ['name' => 'Dewi Anggraini',   'email' => 'dewi.anggraini@cic.ac.id',  'phone' => '081234556677'],
            ['name' => 'Fajar Ramadhan',   'email' => 'fajar.ramadhan@cic.ac.id',  'phone' => '081234889900'],
            ['name' => 'Nabila Syahrani',  'email' => 'nabila.syahrani@cic.ac.id', 'phone' => '081234113355'],
            ['name' => 'Kevin Wijaya',     'email' => 'kevin.wijaya@cic.ac.id',    'phone' => '081234779911'],
        ];

        foreach ($salesMembers as $idx => $salesData) {
            $assignedKec = $kecamatans->isNotEmpty() ? $kecamatans[$idx % $kecamatans->count()] : null;
            $salesWilayahId = $assignedKec?->id ?? $wilayahId;
            $assignedSpv = $spvList[$idx % count($spvList)];

            $salesUser = User::updateOrCreate(
                ['email' => $salesData['email']],
                [
                    'name'              => $salesData['name'],
                    'role'              => 'Sales',
                    'password'          => Hash::make('password'),
                    'status'            => 'Aktif',
                    'phone'             => $salesData['phone'],
                    'supervisor_id'     => $assignedSpv->id,
                    'wilayah_id'        => $salesWilayahId,
                    'email_verified_at' => now(),
                ]
            );

            if ($salesWilayahId) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $salesUser->id, 'wilayah_id' => $salesWilayahId, 'role' => 'Sales'],
                    ['is_active' => true, 'assigned_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Ensure all sales have supervisor assigned
        if ($primarySpv) {
            User::where('role', 'Sales')->whereNull('supervisor_id')->update(['supervisor_id' => $primarySpv->id]);
        }
    }
}
