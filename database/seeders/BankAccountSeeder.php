<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankAccount;

class BankAccountSeeder extends Seeder
{
    /**
     * Seed master rekening bank institusi untuk pembayaran transfer.
     * Kontak admin untuk mengubah nomor rekening ini.
     */
    public function run(): void
    {
        // Hapus semua rekening lama agar tidak duplikat saat re-seed
        BankAccount::truncate();

        BankAccount::insert([
            [
                'bank_name'      => 'Mandiri',
                'account_number' => '1380010015599',
                'account_name'   => 'Universitas CIC Cirebon',
                'is_active'      => true,
                'notes'          => 'Rekening utama pembayaran UCIC. Hubungi Admin untuk mengubah.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
