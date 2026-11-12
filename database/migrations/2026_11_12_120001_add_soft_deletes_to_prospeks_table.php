<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom soft delete (deleted_at) dan pelacak admin penghapus (deleted_by)
     * pada tabel prospeks untuk mendukung fitur Soft Delete & Trash Data Pembayaran PMB.
     */
    public function up(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            if (!Schema::hasColumn('prospeks', 'deleted_at')) {
                $table->softDeletes();
            }

            if (!Schema::hasColumn('prospeks', 'deleted_by')) {
                $table->foreignId('deleted_by')
                    ->nullable()
                    ->after('deleted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            if (Schema::hasColumn('prospeks', 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn('deleted_by');
            }

            if (Schema::hasColumn('prospeks', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

