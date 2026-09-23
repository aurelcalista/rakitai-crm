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
            TahunAkademikSeeder::class,
            UserSeeder::class,
            MasterDataSeeder::class,
            WilayahSeeder::class,
            ProdiSeeder::class,
            SekolahSeeder::class,
            PerusahaanSeeder::class,
        ]);

    }
}

