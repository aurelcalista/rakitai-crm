<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE kunjungans MODIFY COLUMN status VARCHAR(255)');
    }

    public function down(): void
    {
        // Not reversible
    }
};
