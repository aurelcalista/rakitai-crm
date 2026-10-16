<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Schema;

class BankAccountSeeder extends Seeder
{
    /**
     * Seed master rekening bank institusi untuk pembayaran transfer.
     * Menyisakan 1 data rekening bank sampel.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        BankAccount::truncate();
        Schema::enableForeignKeyConstraints();

        BankAccount::create([
            'bank_name'      => 'Bank Mandiri',
            'account_number' => '1380010015599',
            'account_name'   => 'Universitas Catur Insan Cendekia',
            'is_active'      => true,
            'notes'          => 'Rekening utama penerimaan PMB UCIC via Bank Mandiri.',
        ]);
    }
}
