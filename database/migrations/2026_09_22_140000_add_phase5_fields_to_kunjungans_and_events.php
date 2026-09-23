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
        Schema::table('kunjungans', function (Blueprint $table) {
            if (!Schema::hasColumn('kunjungans', 'prodi_id')) {
                $table->foreignId('prodi_id')->nullable()->after('sales_id')->constrained('prodis')->nullOnDelete();
            }
            if (!Schema::hasColumn('kunjungans', 'jarak_meter')) {
                $table->double('jarak_meter', 12, 2)->nullable()->after('lng');
            }
            if (!Schema::hasColumn('kunjungans', 'status_lokasi')) {
                $table->string('status_lokasi', 50)->nullable()->after('jarak_meter');
            }
            if (!Schema::hasColumn('kunjungans', 'dosen_id')) {
                $table->foreignId('dosen_id')->nullable()->after('sales_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('kunjungans', 'dosen_pemateri')) {
                $table->string('dosen_pemateri')->nullable()->after('dosen_id');
            }
            if (!Schema::hasColumn('kunjungans', 'qr_code')) {
                $table->string('qr_code')->nullable()->unique()->after('status_verifikasi');
            }
            if (!Schema::hasColumn('kunjungans', 'tier')) {
                $table->string('tier', 10)->nullable()->after('nama_institusi');
            }
            if (!Schema::hasColumn('kunjungans', 'budget_maksimum')) {
                $table->decimal('budget_maksimum', 14, 2)->nullable()->after('tier');
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'dosen_id')) {
                $table->foreignId('dosen_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('events', 'dosen_pemateri')) {
                $table->string('dosen_pemateri')->nullable();
            }
            if (!Schema::hasColumn('events', 'prodi_id')) {
                $table->foreignId('prodi_id')->nullable()->constrained('prodis')->nullOnDelete();
            }
            if (!Schema::hasColumn('events', 'sekolah_id')) {
                $table->foreignId('sekolah_id')->nullable()->constrained('sekolahs')->nullOnDelete();
            }
            if (!Schema::hasColumn('events', 'perusahaan_id')) {
                $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaans')->nullOnDelete();
            }
            if (!Schema::hasColumn('events', 'qr_code')) {
                $table->string('qr_code')->nullable()->unique();
            }
        });

        Schema::table('sekolahs', function (Blueprint $table) {
            if (!Schema::hasColumn('sekolahs', 'tier')) {
                $table->string('tier', 10)->default('B')->nullable()->after('nama');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropForeign(['prodi_id']);
            $table->dropForeign(['dosen_id']);
            $table->dropColumn([
                'prodi_id',
                'jarak_meter',
                'status_lokasi',
                'dosen_id',
                'dosen_pemateri',
                'qr_code',
                'tier',
                'budget_maksimum',
            ]);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['dosen_id']);
            $table->dropForeign(['prodi_id']);
            $table->dropForeign(['sekolah_id']);
            $table->dropForeign(['perusahaan_id']);
            $table->dropColumn([
                'dosen_id',
                'dosen_pemateri',
                'prodi_id',
                'sekolah_id',
                'perusahaan_id',
                'qr_code',
            ]);
        });

        Schema::table('sekolahs', function (Blueprint $table) {
            $table->dropColumn(['tier']);
        });
    }
};
