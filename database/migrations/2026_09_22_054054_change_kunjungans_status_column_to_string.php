<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE kunjungans MODIFY COLUMN status VARCHAR(255)');
        } else {
            \Illuminate\Support\Facades\Schema::table('kunjungans', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('status')->change();
            });
        }
    }

    public function down(): void
    {
        // Not reversible
    }
};
