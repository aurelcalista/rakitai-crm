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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::table('user_wilayah')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

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
            // 2. yuda.thomas@cic.ac.id ( role admin )
            [
                'name'       => 'Yuda Thomas',
                'email'      => 'yuda.thomas@cic.ac.id',
                'role'       => 'Admin',
                'phone'      => '081234567001',
                'wilayah_id' => null,
            ],
            // 3. yuda.thomas@cic.ac.id ( role hm )
            [
                'name'       => 'Yuda Thomas',
                'email'      => 'yuda.thomas@cic.ac.id',
                'role'       => 'HM',
                'phone'      => '081234567002',
                'wilayah_id' => $wilayahId,
            ],
            // 4. yuda.thomas@cic.ac.id ( role spv )
            [
                'name'       => 'Yuda Thomas',
                'email'      => 'yuda.thomas@cic.ac.id',
                'role'       => 'SPV',
                'phone'      => '081234567003',
                'wilayah_id' => $wilayahId,
            ],
            // 5. yuda.thomas@cic.ac.id ( role sales )
            [
                'name'       => 'Yuda Thomas',
                'email'      => 'yuda.thomas@cic.ac.id',
                'role'       => 'Sales',
                'phone'      => '081234567004',
                'wilayah_id' => $kecamatans->first()?->id ?? $wilayahId,
            ],
            // 6. yuda.thomas@cic.ac.id ( role cs )
            [
                'name'       => 'Yuda Thomas',
                'email'      => 'yuda.thomas@cic.ac.id',
                'role'       => 'CS',
                'phone'      => '081234567005',
                'wilayah_id' => $wilayahId,
            ],
            // 7. yuda.thomas@cic.ac.id ( role eo )
            [
                'name'       => 'Yuda Thomas',
                'email'      => 'yuda.thomas@cic.ac.id',
                'role'       => 'EO',
                'phone'      => '081234567006',
                'wilayah_id' => $wilayahId,
            ],
            // 8. Lorenz.adam@cic.ac.id ( role admin )
            [
                'name'       => 'Lorenz Adam',
                'email'      => 'lorenz.adam@cic.ac.id',
                'role'       => 'Admin',
                'phone'      => '081398765001',
                'wilayah_id' => null,
            ],
            // 9. Lorenz.adam@cic.ac.id ( role hm )
            [
                'name'       => 'Lorenz Adam',
                'email'      => 'lorenz.adam@cic.ac.id',
                'role'       => 'HM',
                'phone'      => '081398765002',
                'wilayah_id' => $parentWilayahs->get(1)?->id ?? $wilayahId,
            ],
            // 10. Lorenz.adam@cic.ac.id ( role spv )
            [
                'name'       => 'Lorenz Adam',
                'email'      => 'lorenz.adam@cic.ac.id',
                'role'       => 'SPV',
                'phone'      => '081398765003',
                'wilayah_id' => $parentWilayahs->get(1)?->id ?? $wilayahId,
            ],
            // 11. Lorenz.adam@cic.ac.id ( role sales )
            [
                'name'       => 'Lorenz Adam',
                'email'      => 'lorenz.adam@cic.ac.id',
                'role'       => 'Sales',
                'phone'      => '081398765004',
                'wilayah_id' => $kecamatans->get(1)?->id ?? $wilayahId,
            ],
            // 12. Lorenz.adam@cic.ac.id ( role cs )
            [
                'name'       => 'Lorenz Adam',
                'email'      => 'lorenz.adam@cic.ac.id',
                'role'       => 'CS',
                'phone'      => '081398765005',
                'wilayah_id' => $parentWilayahs->get(1)?->id ?? $wilayahId,
            ],
            // 13. Lorenz.adam@cic.ac.id ( role eo )
            [
                'name'       => 'Lorenz Adam',
                'email'      => 'lorenz.adam@cic.ac.id',
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

            $createdUsers[$uData['email'] . '_' . $uData['role']] = $user;

            if (!empty($uData['wilayah_id'])) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $user->id, 'wilayah_id' => $uData['wilayah_id'], 'role' => $uData['role']],
                    ['is_active' => true, 'assigned_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Set supervisor assignments
        $spvYuda = $createdUsers['yuda.thomas@cic.ac.id_SPV'] ?? null;
        $spvLorenz = $createdUsers['lorenz.adam@cic.ac.id_SPV'] ?? null;

        if ($spvYuda) {
            if (isset($createdUsers['yuda.thomas@cic.ac.id_Sales'])) {
                $createdUsers['yuda.thomas@cic.ac.id_Sales']->update(['supervisor_id' => $spvYuda->id]);
            }
            if (isset($createdUsers['yuda.thomas@cic.ac.id_CS'])) {
                $createdUsers['yuda.thomas@cic.ac.id_CS']->update(['supervisor_id' => $spvYuda->id]);
            }
            if (isset($createdUsers['mei.fie@cic.ac.id_CS'])) {
                $createdUsers['mei.fie@cic.ac.id_CS']->update(['supervisor_id' => $spvYuda->id]);
            }
        }

        if ($spvLorenz) {
            if (isset($createdUsers['lorenz.adam@cic.ac.id_Sales'])) {
                $createdUsers['lorenz.adam@cic.ac.id_Sales']->update(['supervisor_id' => $spvLorenz->id]);
            }
            if (isset($createdUsers['lorenz.adam@cic.ac.id_CS'])) {
                $createdUsers['lorenz.adam@cic.ac.id_CS']->update(['supervisor_id' => $spvLorenz->id]);
            }
        }
    }
}
