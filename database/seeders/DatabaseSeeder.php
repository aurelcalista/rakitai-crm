<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Admin\MasterDataSeeder;
use Database\Seeders\Admin\WilayahSeeder;
use Database\Seeders\Admin\ProdiSeeder;
use Database\Seeders\Admin\SekolahSeeder;
use Database\Seeders\Admin\PerusahaanSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class,
            TahunAkademikSeeder::class,
            WilayahSeeder::class,
            UserSeeder::class,
            ProdiSeeder::class,
            SekolahSeeder::class,
            PerusahaanSeeder::class,
            TargetSeeder::class,
            BankAccountSeeder::class,
            EventSeeder::class,
            ProspekSeeder::class,
            FollowUpSeeder::class,
            KunjunganSeeder::class,
            TransaksiSeeder::class,
        ]);
    }
}
