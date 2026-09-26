<?php

namespace Database\Seeders;

use App\Models\Transaksi;
use App\Models\Prospek;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $lunasProspeks = Prospek::where('status', 'LUNAS')->get();
        $formulirProspeks = Prospek::whereIn('status', ['FORMULIR', 'BERKAS'])->get();
        $ta = TahunAkademik::getAktif();

        // 1. Transactions for LUNAS prospects
        foreach ($lunasProspeks as $p) {
            $user = $p->sales ?? $p->cs;

            // Form payment
            Transaksi::create([
                'prospek_id'       => $p->id,
                'user_id'          => $user?->id,
                'jenis'            => 'Beli Formulir',
                'nominal'          => 250000,
                'tanggal'          => now()->subDays(10),
                'notes'            => 'Pembayaran voucher formulir PMB online.',
                'academic_year_id' => $ta?->id,
            ]);

            // UKT / Registration payment
            Transaksi::create([
                'prospek_id'       => $p->id,
                'user_id'          => $user?->id,
                'jenis'            => 'Pembayaran Termin 1',
                'nominal'          => 3500000,
                'tanggal'          => now()->subDays(3),
                'notes'            => 'Pelunasan biaya registrasi ulang dan SPP semester 1.',
                'academic_year_id' => $ta?->id,
            ]);
        }

        // 2. Transactions for FORMULIR & BERKAS prospects
        foreach ($formulirProspeks as $p) {
            $user = $p->sales ?? $p->cs;

            Transaksi::create([
                'prospek_id'       => $p->id,
                'user_id'          => $user?->id,
                'jenis'            => 'Beli Formulir',
                'nominal'          => 250000,
                'tanggal'          => now()->subDays(5),
                'notes'            => 'Pembelian formulir pendaftaran PMB.',
                'academic_year_id' => $ta?->id,
            ]);
        }
    }
}
