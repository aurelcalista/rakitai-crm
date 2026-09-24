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
        // 1. Add kode & jabatan columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'kode')) {
                $table->string('kode', 50)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'jabatan')) {
                $table->string('jabatan', 100)->nullable()->after('role');
            }
        });

        // 2. Add ukt and ukt_reguler columns to prodis table
        Schema::table('prodis', function (Blueprint $table) {
            if (!Schema::hasColumn('prodis', 'ukt')) {
                $table->string('ukt', 100)->nullable()->after('spp');
            }
            if (!Schema::hasColumn('prodis', 'ukt_reguler')) {
                $table->string('ukt_reguler', 100)->nullable()->after('ukt');
            }
        });

        // Copy existing spp data to ukt if ukt is null
        try {
            \Illuminate\Support\Facades\DB::table('prodis')
                ->whereNull('ukt')
                ->whereNotNull('spp')
                ->update(['ukt' => \Illuminate\Support\Facades\DB::raw('spp')]);
        } catch (\Throwable $e) {}

        // 3. Add target_pemberkasan to targets table
        Schema::table('targets', function (Blueprint $table) {
            if (!Schema::hasColumn('targets', 'target_pemberkasan')) {
                $table->integer('target_pemberkasan')->default(0)->after('target_formulir');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'kode')) {
                $table->dropColumn('kode');
            }
        });

        Schema::table('prodis', function (Blueprint $table) {
            if (Schema::hasColumn('prodis', 'ukt_reguler')) {
                $table->dropColumn('ukt_reguler');
            }
            if (Schema::hasColumn('prodis', 'ukt')) {
                $table->dropColumn('ukt');
            }
        });

        Schema::table('targets', function (Blueprint $table) {
            if (Schema::hasColumn('targets', 'target_pemberkasan')) {
                $table->dropColumn('target_pemberkasan');
            }
        });
    }
};
