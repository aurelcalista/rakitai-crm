<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksis', 'snap_token')) {
                $table->string('snap_token', 255)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('transaksis', 'snap_redirect_url')) {
                $table->text('snap_redirect_url')->nullable()->after('snap_token');
            }
            if (!Schema::hasColumn('transaksis', 'payment_type')) {
                $table->string('payment_type', 100)->nullable()->after('metode_pembayaran');
            }
            if (!Schema::hasColumn('transaksis', 'fraud_status')) {
                $table->string('fraud_status', 50)->nullable()->after('payment_type');
            }
            if (!Schema::hasColumn('transaksis', 'transaction_time')) {
                $table->dateTime('transaction_time')->nullable()->after('tanggal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $columns = [
                'snap_token',
                'snap_redirect_url',
                'payment_type',
                'fraud_status',
                'transaction_time',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('transaksis', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
