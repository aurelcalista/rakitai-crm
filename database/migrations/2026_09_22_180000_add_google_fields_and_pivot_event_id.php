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
            if (!Schema::hasColumn('users', 'google_access_token')) {
                $table->text('google_access_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'google_refresh_token')) {
                $table->text('google_refresh_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'google_token_expires_at')) {
                $table->timestamp('google_token_expires_at')->nullable();
            }
        });

        Schema::table('event_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('event_sales', 'google_event_id')) {
                $table->string('google_event_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_access_token', 'google_refresh_token', 'google_token_expires_at']);
        });

        Schema::table('event_sales', function (Blueprint $table) {
            $table->dropColumn('google_event_id');
        });
    }
};
