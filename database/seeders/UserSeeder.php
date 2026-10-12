<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        User::truncate();
        DB::table('user_wilayah')->truncate();
        Schema::enableForeignKeyConstraints();

        $parentWilayahs = Wilayah::whereNull('parent_id')->get();
        $defaultKota = Wilayah::where('kode', 'W-CRB')->first() ?? $parentWilayahs->first();
        $wilayahId = $defaultKota?->id;
        $kecamatans = Wilayah::whereNotNull('parent_id')->get();

        $users = [
            // 1. admin@cic.ac.id ( role admin )
            [
                'name'       => 'Admin Utama',
                'email'      => 'admin@cic.ac.id',
                'role'       => 'Admin',
                'phone'      => '081122334455',
                'wilayah_id' => null,
            ],
            // 2. admin.yuda.thomas@cic.ac.id ( role admin )
            [
                'name'       => 'Admin Yuda Thomas',
                'email'      => 'admin.yuda.thomas@cic.ac.id',
                'role'       => 'Admin',
                'phone'      => '081234567001',
                'wilayah_id' => null,
            ],
            // 3. hm.yuda.thomas@cic.ac.id ( role hm )
            [
                'name'       => 'HM Yuda Thomas',
                'email'      => 'hm.yuda.thomas@cic.ac.id',
                'role'       => 'HM',
                'phone'      => '081234567002',
                'wilayah_id' => $wilayahId,
            ],
            // 4. spv.yuda.thomas@cic.ac.id ( role spv )
            [
                'name'       => 'SPV Yuda Thomas',
                'email'      => 'spv.yuda.thomas@cic.ac.id',
                'role'       => 'SPV',
                'phone'      => '081234567003',
                'wilayah_id' => $wilayahId,
            ],
            // 5. sales.yuda.thomas@cic.ac.id ( role sales )
            [
                'name'       => 'Sales Yuda Thomas',
                'email'      => 'sales.yuda.thomas@cic.ac.id',
                'role'       => 'Sales',
                'phone'      => '081234567004',
                'wilayah_id' => $kecamatans->first()?->id ?? $wilayahId,
            ],
            // 6. cs.yuda.thomas@cic.ac.id ( role cs )
            [
                'name'       => 'CS Yuda Thomas',
                'email'      => 'cs.yuda.thomas@cic.ac.id',
                'role'       => 'CS',
                'phone'      => '081234567005',
                'wilayah_id' => $wilayahId,
            ],
            // 7. eo.yuda.thomas@cic.ac.id ( role eo )
            [
                'name'       => 'EO Yuda Thomas',
                'email'      => 'eo.yuda.thomas@cic.ac.id',
                'role'       => 'EO',
                'phone'      => '081234567006',
                'wilayah_id' => $wilayahId,
            ],
            // 8. admin.lorenz.adam@cic.ac.id ( role admin )
            [
                'name'       => 'Admin Lorenz Adam',
                'email'      => 'admin.lorenz.adam@cic.ac.id',
                'role'       => 'Admin',
                'phone'      => '081398765001',
                'wilayah_id' => null,
            ],
            // 9. hm.lorenz.adam@cic.ac.id ( role hm )
            [
                'name'       => 'HM Lorenz Adam',
                'email'      => 'hm.lorenz.adam@cic.ac.id',
                'role'       => 'HM',
                'phone'      => '081398765002',
                'wilayah_id' => $parentWilayahs->get(1)?->id ?? $wilayahId,
            ],
            // 10. spv.lorenz.adam@cic.ac.id ( role spv )
            [
                'name'       => 'SPV Lorenz Adam',
                'email'      => 'spv.lorenz.adam@cic.ac.id',
                'role'       => 'SPV',
                'phone'      => '081398765003',
                'wilayah_id' => $parentWilayahs->get(1)?->id ?? $wilayahId,
            ],
            // 11. sales.lorenz.adam@cic.ac.id ( role sales )
            [
                'name'       => 'Sales Lorenz Adam',
                'email'      => 'sales.lorenz.adam@cic.ac.id',
                'role'       => 'Sales',
                'phone'      => '081398765004',
                'wilayah_id' => $kecamatans->get(1)?->id ?? $wilayahId,
            ],
            // 12. cs.lorenz.adam@cic.ac.id ( role cs )
            [
                'name'       => 'CS Lorenz Adam',
                'email'      => 'cs.lorenz.adam@cic.ac.id',
                'role'       => 'CS',
                'phone'      => '081398765005',
                'wilayah_id' => $parentWilayahs->get(1)?->id ?? $wilayahId,
            ],
            // 13. eo.lorenz.adam@cic.ac.id ( role eo )
            [
                'name'       => 'EO Lorenz Adam',
                'email'      => 'eo.lorenz.adam@cic.ac.id',
                'role'       => 'EO',
                'phone'      => '081398765006',
                'wilayah_id' => $wilayahId,
            ],
            // 14. mei.fie@cic.ac.id ( role cs )
            [
                'name'       => 'Mei Fie',
                'email'      => 'mei.fie@cic.ac.id',
                'role'       => 'CS',
                'phone'      => '081711223399',
                'wilayah_id' => $wilayahId,
            ],
            // 15. analyst@cic.ac.id ( role data analyst )
            [
                'name'       => 'Data Analyst',
                'email'      => 'analyst@cic.ac.id',
                'role'       => 'Data Analyst',
                'phone'      => '081299887766',
                'wilayah_id' => null,
            ],
        ];

        $createdUsers = [];
        foreach ($users as $uData) {
            $user = User::create([
                'name'              => $uData['name'],
                'email'             => strtolower($uData['email']),
                'role'              => $uData['role'],
                'password'          => Hash::make('password'),
                'status'            => 'Aktif',
                'phone'             => $uData['phone'],
                'wilayah_id'        => $uData['wilayah_id'],
                'email_verified_at' => now(),
            ]);

            $createdUsers[$uData['email']] = $user;

            if (!empty($uData['wilayah_id'])) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $user->id, 'wilayah_id' => $uData['wilayah_id'], 'role' => $uData['role']],
                    ['is_active' => true, 'assigned_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Set supervisor assignments
        $spvYuda = $createdUsers['spv.yuda.thomas@cic.ac.id'] ?? null;
        $spvLorenz = $createdUsers['spv.lorenz.adam@cic.ac.id'] ?? null;

        if ($spvYuda) {
            if (isset($createdUsers['sales.yuda.thomas@cic.ac.id'])) {
                $createdUsers['sales.yuda.thomas@cic.ac.id']->update(['supervisor_id' => $spvYuda->id]);
            }
            if (isset($createdUsers['cs.yuda.thomas@cic.ac.id'])) {
                $createdUsers['cs.yuda.thomas@cic.ac.id']->update(['supervisor_id' => $spvYuda->id]);
            }
            if (isset($createdUsers['mei.fie@cic.ac.id'])) {
                $createdUsers['mei.fie@cic.ac.id']->update(['supervisor_id' => $spvYuda->id]);
            }
        }

        if ($spvLorenz) {
            if (isset($createdUsers['sales.lorenz.adam@cic.ac.id'])) {
                $createdUsers['sales.lorenz.adam@cic.ac.id']->update(['supervisor_id' => $spvLorenz->id]);
            }
            if (isset($createdUsers['cs.lorenz.adam@cic.ac.id'])) {
                $createdUsers['cs.lorenz.adam@cic.ac.id']->update(['supervisor_id' => $spvLorenz->id]);
            }
        }
    }
}
