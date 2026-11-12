<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'name')) {
                $table->string('name', 150)->nullable()->after('code');
            }
        });

        Schema::table('voucher_usages', function (Blueprint $table) {
            if (!Schema::hasColumn('voucher_usages', 'voucher_name')) {
                $table->string('voucher_name', 150)->nullable()->after('voucher_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'name')) {
                $table->dropColumn('name');
            }
        });

        Schema::table('voucher_usages', function (Blueprint $table) {
            if (Schema::hasColumn('voucher_usages', 'voucher_name')) {
                $table->dropColumn('voucher_name');
            }
        });
    }
};

