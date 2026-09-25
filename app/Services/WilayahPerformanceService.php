<?php

namespace App\Services;

use App\Models\MasterData;
use App\Models\Prospek;
use App\Models\Target;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\AkademikService;

class WilayahPerformanceService
{
    /**
     * Get dynamic configured weights for Kontak and Closing.
     * Default: Kontak = 40%, Closing = 60%.
     */
    public function getWeights(): array
    {
        $kontakItem = MasterData::where('type', 'bobot_indikator')->where('kode', 'BOBOT_KONTAK')->first();
        $closingItem = MasterData::where('type', 'bobot_indikator')->where('kode', 'BOBOT_CLOSING')->first();

        $bobotKontak = $kontakItem ? (float) $kontakItem->nama : 40.0;
        $bobotClosing = $closingItem ? (float) $closingItem->nama : 60.0;

        return [
            'bobot_kontak'  => $bobotKontak,
            'bobot_closing' => $bobotClosing,
        ];
    }

    /**
     * Update configured weights for Admin.
     * Validation: Bobot Kontak + Bobot Closing MUST equal 100%.
     */
    public function updateWeights(float $bobotKontak, float $bobotClosing): bool
    {
        if (abs(($bobotKontak + $bobotClosing) - 100.0) > 0.001) {
            throw ValidationException::withMessages([
                'bobot_kontak' => 'Total Bobot Kontak dan Bobot Closing harus tepat 100%.',
            ]);
        }

        MasterData::updateOrCreate(
            ['type' => 'bobot_indikator', 'kode' => 'BOBOT_KONTAK'],
            ['nama' => (string) $bobotKontak, 'deskripsi' => 'Bobot Kontak Indikator Wilayah (%)', 'status' => 'Aktif']
        );

        MasterData::updateOrCreate(
            ['type' => 'bobot_indikator', 'kode' => 'BOBOT_CLOSING'],
            ['nama' => (string) $bobotClosing, 'deskripsi' => 'Bobot Closing Indikator Wilayah (%)', 'status' => 'Aktif']
        );

        return true;
    }

    /**
     * Calculate Contact Volume Score for a Wilayah.
     * Formula: (Realisasi Kontak / Target Kontak) * 100
     * Behavior: If target <= 0, returns null (N/A).
     */
    public function getContactScore(float $realisasi, float $target): ?float
    {
        if ($target <= 0) {
            return null;
        }

        return round(($realisasi / $target) * 100.0, 2);
    }

    /**
     * Calculate Closing (LUNAS) Score for a Wilayah.
     * Formula: (Realisasi LUNAS / Target LUNAS) * 100
     * Behavior: If target <= 0, returns null (N/A).
     */
    public function getClosingScore(float $realisasi, float $target): ?float
    {
        if ($target <= 0) {
            return null;
        }

        return round(($realisasi / $target) * 100.0, 2);
    }

    /**
     * Calculate Combined Score.
     * Formula: (Skor Kontak * (Bobot Kontak / 100)) + (Skor Closing * (Bobot Closing / 100))
     * Behavior: If either Contact or Closing score is null (N/A), returns null (N/A).
     */
    public function getCombinedScore(?float $contactScore, ?float $closingScore, ?float $bobotKontak = null, ?float $bobotClosing = null): ?float
    {
        if ($contactScore === null || $closingScore === null) {
            return null;
        }

        if ($bobotKontak === null || $bobotClosing === null) {
            $weights = $this->getWeights();
            $bobotKontak = $weights['bobot_kontak'];
            $bobotClosing = $weights['bobot_closing'];
        }

        $combined = ($contactScore * ($bobotKontak / 100.0)) + ($closingScore * ($bobotClosing / 100.0));
        return round($combined, 2);
    }

    /**
     * Determine Grade Wilayah based on exact unrounded Combined Score boundaries.
     * null       -> N/A
     * >= 100.0   -> A — Sangat Potensial
     * 80 - 99.99 -> B — Potensial
     * 60 - 79.99 -> C — Perlu Dorongan
     * < 60.0     -> D — Perlu Perhatian Khusus
     */
    public function getGrade(?float $score): array
    {
        if ($score === null) {
            return [
                'code'  => 'N/A',
                'label' => 'N/A',
                'badge' => 'bg-slate-100 text-slate-600 border-slate-300',
            ];
        }

        if ($score >= 100.0) {
            return [
                'code'  => 'A',
                'label' => 'A — Sangat Potensial',
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            ];
        } elseif ($score >= 80.0) {
            return [
                'code'  => 'B',
                'label' => 'B — Potensial',
                'badge' => 'bg-blue-100 text-blue-800 border-blue-300',
            ];
        } elseif ($score >= 60.0) {
            return [
                'code'  => 'C',
                'label' => 'C — Perlu Dorongan',
                'badge' => 'bg-amber-100 text-amber-800 border-amber-300',
            ];
        } else {
            return [
                'code'  => 'D',
                'label' => 'D — Perlu Perhatian Khusus',
                'badge' => 'bg-rose-100 text-rose-800 border-rose-300',
            ];
        }
    }

    /**
     * Determine Matrix Condition based on Contact vs Target & Closing vs Target achievements.
     * FINAL Threshold: >= 100% achievement is considered "Tinggi", < 100% is "Rendah".
     * If scores are null (N/A), returns Matrix N/A.
     */
    public function getMatrix(?float $contactScore, ?float $closingScore): array
    {
        if ($contactScore === null || $closingScore === null) {
            return [
                'code'        => 'N/A',
                'label'       => 'N/A',
                'desc'        => 'Belum Ada Target',
                'badge_color' => 'bg-slate-50 text-slate-600 border-slate-200',
            ];
        }

        $contactHigh = $contactScore >= 100.0;
        $closingHigh = $closingScore >= 100.0;

        if ($contactHigh && $closingHigh) {
            return [
                'code'        => 'A',
                'label'       => 'A — Unggul',
                'desc'        => 'Kuantitas & Closing Sangat Baik',
                'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ];
        } elseif ($contactHigh && !$closingHigh) {
            return [
                'code'        => 'B',
                'label'       => 'B — Kuantitas oke, follow-up perlu dibenahi',
                'desc'        => 'Volume Kontak Tinggi, Konversi LUNAS Masih Rendah',
                'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200',
            ];
        } elseif (!$contactHigh && $closingHigh) {
            return [
                'code'        => 'C',
                'label'       => 'C — Kualitas bagus, perlu tambah kanal/tenaga',
                'desc'        => 'Konversi LUNAS Efektif, Volume Lead Masih Kurang',
                'badge_color' => 'bg-blue-50 text-blue-700 border-blue-200',
            ];
        } else {
            return [
                'code'        => 'D',
                'label'       => 'D — Evaluasi total',
                'desc'        => 'Kontak & Closing Di Bawah Target',
                'badge_color' => 'bg-rose-50 text-rose-700 border-rose-200',
            ];
        }
    }

    /**
     * Calculate complete performance & indicators for a single Wilayah.
     */
    public function getWilayahPerformance(Wilayah $wilayah, ?string $tahunAkademik = null): array
    {
        $activeTA = $tahunAkademik ?: AkademikService::getAktifNama();
        $weights = $this->getWeights();

        // 1. Resolve descendant territory IDs (Kota -> Kecamatans)
        $territoryIds = $wilayah->getDescendantIds();
        $territoryIds[] = $wilayah->id;

        // 2. Fetch assigned Sales / CS user IDs for this territory
        $assignedUserIds = DB::table('user_wilayah')
            ->whereIn('wilayah_id', $territoryIds)
            ->where('is_active', true)
            ->pluck('user_id')
            ->toArray();

        $directUserIds = User::whereIn('wilayah_id', $territoryIds)->pluck('id')->toArray();
        $allUserIds = array_values(array_unique(array_merge($assignedUserIds, $directUserIds)));

        // 3. Aggregate Target Kontak & Target Lunas
        $targetsQuery = Target::where('status', 'Aktif')
            ->where(function($q) use ($activeTA) {
                $q->where('tahun_akademik', $activeTA)
                  ->orWhereNull('tahun_akademik');
            })
            ->where(function($q) use ($territoryIds, $allUserIds) {
                $q->whereIn('wilayah_id', $territoryIds)
                  ->orWhereIn('sales_id', $allUserIds)
                  ->orWhereIn('spv_id', $allUserIds);
            });

        $targetKontak = (float) $targetsQuery->sum('target_kontak');
        $targetLunas  = (float) $targetsQuery->sum('target_lunas');

        // 4. Aggregate Realisasi Kontak & Realisasi Lunas
        $prospekQuery = Prospek::where(function($q) use ($territoryIds, $allUserIds) {
            $q->whereIn('wilayah_id', $territoryIds)
              ->orWhereIn('sales_id', $allUserIds)
              ->orWhereIn('cs_id', $allUserIds);
        });

        $realisasiKontak = (float) (clone $prospekQuery)->count();
        $realisasiLunas  = (float) (clone $prospekQuery)->where('status', 'LUNAS')->count();

        // 5. Calculate Scores & Grade
        $skorKontak  = $this->getContactScore($realisasiKontak, $targetKontak);
        $skorClosing = $this->getClosingScore($realisasiLunas, $targetLunas);
        $skorWilayah = $this->getCombinedScore($skorKontak, $skorClosing, $weights['bobot_kontak'], $weights['bobot_closing']);
        $grade       = $this->getGrade($skorWilayah);
        $matrix      = $this->getMatrix($skorKontak, $skorClosing);

        return [
            'wilayah'          => $wilayah,
            'nama_wilayah'     => $wilayah->nama,
            'kode_wilayah'     => $wilayah->kode,
            'target_kontak'    => $targetKontak,
            'realisasi_kontak' => $realisasiKontak,
            'skor_kontak'      => $skorKontak,
            'target_lunas'     => $targetLunas,
            'realisasi_lunas'  => $realisasiLunas,
            'skor_closing'     => $skorClosing,
            'bobot_kontak'     => $weights['bobot_kontak'],
            'bobot_closing'    => $weights['bobot_closing'],
            'skor_wilayah'     => $skorWilayah,
            'grade'            => $grade,
            'matrix'           => $matrix,
        ];
    }

    /**
     * Get performance indicators for all active Kota/Kabupaten territories in batch (optimized query).
     */
    public function getAllWilayahPerformance(?string $tahunAkademik = null, ?array $scopedWilayahIds = null): Collection
    {
        $query = Wilayah::whereNull('parent_id')
            ->where('status', 'Aktif')
            ->where('nama', 'NOT LIKE', '%Lainnya%')
            ->where('kode', '!=', 'W-LAIN');

        if (!empty($scopedWilayahIds)) {
            $query->whereIn('id', $scopedWilayahIds);
        }

        $wilayahs = $query->orderBy('nama')->get();

        return $wilayahs->map(fn($w) => $this->getWilayahPerformance($w, $tahunAkademik));
    }
}
