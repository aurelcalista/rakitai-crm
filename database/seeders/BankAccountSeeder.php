<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankAccount;

class BankAccountSeeder extends Seeder
{
    /**
     * Seed master rekening bank institusi untuk pembayaran transfer.
     * Admin dapat menambah/mengedit rekening di menu Admin -> Rekening Bank.
     */
    public function run(): void
    {
        // Hapus semua rekening lama agar tidak duplikat saat re-seed
        BankAccount::truncate();

        BankAccount::insert([
            [
                'bank_name'      => 'Bank Mandiri',
                'account_number' => '1380010015599',
                'account_name'   => 'Universitas Catur Insan Cendekia',
                'is_active'      => true,
                'notes'          => 'Rekening utama penerimaan PMB UCIC via Bank Mandiri.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'bank_name'      => 'BCA',
                'account_number' => '8210998877',
                'account_name'   => 'Universitas Catur Insan Cendekia',
                'is_active'      => true,
                'notes'          => 'Rekening penerimaan PMB UCIC via BCA.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'bank_name'      => 'BNI',
                'account_number' => '0298877665',
                'account_name'   => 'Universitas Catur Insan Cendekia',
                'is_active'      => true,
                'notes'          => 'Rekening penerimaan PMB UCIC via BNI.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
