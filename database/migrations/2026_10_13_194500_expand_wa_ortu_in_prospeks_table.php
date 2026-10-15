<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE `prospeks` MODIFY `wa_ortu` VARCHAR(255) NULL");
        } else {
            Schema::table('prospeks', function (Blueprint $table) {
                $table->string('wa_ortu', 255)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE `prospeks` MODIFY `wa_ortu` VARCHAR(20) NULL");
        }
    }
};
