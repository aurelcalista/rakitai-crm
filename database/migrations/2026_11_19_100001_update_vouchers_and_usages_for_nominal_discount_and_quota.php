<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'discount_amount')) {
                $table->unsignedBigInteger('discount_amount')->default(0)->after('name');
            }
            if (!Schema::hasColumn('vouchers', 'quota')) {
                $table->unsignedInteger('quota')->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('vouchers', 'used_quota')) {
                $table->unsignedInteger('used_quota')->default(0)->after('quota');
            }
            if (Schema::hasColumn('vouchers', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable()->default(0)->change();
            }
        });

        Schema::table('voucher_usages', function (Blueprint $table) {
            if (!Schema::hasColumn('voucher_usages', 'total_before_discount')) {
                $table->unsignedBigInteger('total_before_discount')->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('voucher_usages', 'total_after_discount')) {
                $table->unsignedBigInteger('total_after_discount')->nullable()->after('total_before_discount');
            }
            if (Schema::hasColumn('voucher_usages', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable()->default(0)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('vouchers', 'used_quota')) {
                $drops[] = 'used_quota';
            }
            if (Schema::hasColumn('vouchers', 'quota')) {
                $drops[] = 'quota';
            }
            if (Schema::hasColumn('vouchers', 'discount_amount')) {
                $drops[] = 'discount_amount';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });

        Schema::table('voucher_usages', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('voucher_usages', 'total_after_discount')) {
                $drops[] = 'total_after_discount';
            }
            if (Schema::hasColumn('voucher_usages', 'total_before_discount')) {
                $drops[] = 'total_before_discount';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
