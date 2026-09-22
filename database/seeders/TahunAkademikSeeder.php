<?php

namespace Database\Seeders;

use App\Models\TahunAkademik;
use App\Services\AkademikService;
use Illuminate\Database\Seeder;

/**
 * Bootstrap Tahun Akademik data.
 *
 * Idempotent (uses updateOrCreate). Safe to re-run.
 * 2027/2028 = Aktif (PRD-expected active year at launch).
 * 2026/2027, 2025/2026 = Non-Aktif (historical).
 *
 * Strategy: Seeder (not a data-migration) because:
 * - This is reference/config data, not schema.
 * - `php artisan db:seed` is the established deployment step for this project.
 * - Avoids duplicate-source-of-truth with a separate migration.
 */
class TahunAkademikSeeder extends Seeder
{
    public function run(): void
    {
        // Historical records — always Non-Aktif
        TahunAkademik::updateOrCreate(
            ['nama' => '2025/2026'],
            ['status' => 'Non-Aktif']
        );

        TahunAkademik::updateOrCreate(
            ['nama' => '2026/2027'],
            ['status' => 'Non-Aktif']
        );

        // Active year — use AkademikService::activate() so the guardrail is exercised
        // and the per-request cache is reset afterwards.
        $aktif = TahunAkademik::updateOrCreate(
            ['nama' => '2027/2028'],
            ['status' => 'Non-Aktif'] // start as Non-Aktif, then atomically activate
        );

        // Flush cache before activation so a stale value doesn't interfere
        AkademikService::flushCache();

        // Atomically activate 2027/2028 and deactivate any others
        AkademikService::activate($aktif);

        $this->command->info('TahunAkademik seeded: 2027/2028 = Aktif.');
    }
}
