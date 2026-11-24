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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'agent')) {
                $table->string('agent', 100)->nullable()->after('status')->index();
            }
        });

        Schema::table('prospeks', function (Blueprint $table) {
            if (!Schema::hasColumn('prospeks', 'agent')) {
                $table->string('agent', 100)->nullable()->after('source')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'agent')) {
                $table->dropColumn('agent');
            }
        });

        Schema::table('prospeks', function (Blueprint $table) {
            if (Schema::hasColumn('prospeks', 'agent')) {
                $table->dropColumn('agent');
            }
        });
    }
};
