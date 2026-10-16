<?php

namespace Database\Seeders;

use App\Models\Transaksi;
use App\Models\Prospek;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Menyisakan 1 data transaksi sampel.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Transaksi::truncate();
        Schema::enableForeignKeyConstraints();

        $prospek = Prospek::first();
        $ta = TahunAkademik::getAktif();

        if (!$prospek) return;

        $user = $prospek->sales ?? $prospek->cs;

        // 1 Transaksi Sampel (Pembayaran Formulir PMB)
        Transaksi::create([
            'prospek_id'        => $prospek->id,
            'user_id'           => $user?->id,
            'jenis'             => 'Beli Formulir',
            'kategori'          => 'Lunas',
            'nominal'           => 250000,
            'tanggal'           => now()->subDays(2),
            'notes'             => 'Pembayaran pendaftaran PMB online.',
            'academic_year_id'  => $ta?->id,
            'metode_pembayaran' => 'bank_transfer',
            'payment_status'    => 'verified',
            'verified_by'       => $user?->id,
            'verified_at'       => now()->subDays(1),
        ]);
    }
}
