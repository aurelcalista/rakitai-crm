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
        Schema::table('prospeks', function (Blueprint $table) {
            $table->string('status')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not easily reversible to ENUM without knowing previous values, leaving empty or generic.
        // DB::statement("ALTER TABLE prospeks MODIFY COLUMN status ENUM('Cold Lead','Interested','Follow Up','Beli Formulir','Pembayaran Termin 1','Closing','Lost')");
        // DB::statement("ALTER TABLE follow_ups MODIFY COLUMN status ENUM('Cold Lead','Interested','Follow Up','Beli Formulir','Pembayaran Termin 1','Closing')");
    }
};
