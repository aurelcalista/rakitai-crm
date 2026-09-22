<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\Target;
use App\Models\User;
use Carbon\Carbon;

class SalesTargetService
{
    /**
     * Get the active target for the current month for a given Sales user.
     * Returns null if no target is set for this month.
     */
    public function getActiveTarget(User $sales): ?Target
    {
        $now = Carbon::now();

        return Target::where('sales_id', $sales->id)
            ->where('status', 'Aktif')
            ->where('tanggal_mulai', '<=', $now->toDateString())
            ->where('tanggal_selesai', '>=', $now->toDateString())
            ->latest()
            ->first();
    }

    /**
     * Calculate actual achievement for a Sales user within a target period.
     *
     * @return array{kontak_baru: int, follow_up: int, kunjungan: int}
     */
    public function getAchievement(User $sales, Target $target): array
    {
        $from = $target->tanggal_mulai;
        $to   = $target->tanggal_selesai;

        // Kontak baru = new prospects created by this Sales in the period
        $kontakBaru = Prospek::where('sales_id', $sales->id)
            ->whereBetween('created_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        // Follow-up = total follow-up records by this Sales in the period
        $followUp = FollowUp::where('user_id', $sales->id)
            ->whereBetween('tanggal', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        // Kunjungan = total visits by this Sales in the period
        $kunjungan = \App\Models\Kunjungan::where('sales_id', $sales->id)
            ->whereBetween('tanggal', [$from, $to])
            ->count();

        return compact('kontakBaru', 'followUp', 'kunjungan');
    }

    /**
     * Snowball daily target calculation:
     * Target Hari Ini = Target Base Harian + Sisa target hari-hari sebelumnya (akumulasi).
     *
     * Target base harian = Target Bulanan / jumlah hari kerja dalam bulan.
     * Sisa = target yang belum terpenuhi dari hari-hari sebelumnya.
     *
     * @return array{target_hari_ini_kontak: int, target_hari_ini_followup: int, sisa_akumulasi_kontak: int, sisa_akumulasi_followup: int}
     */
    public function calculateDailyTarget(User $sales): array
    {
        $target = $this->getActiveTarget($sales);

        if (!$target) {
            return [
                'target_hari_ini_kontak'     => 0,
                'target_hari_ini_followup'   => 0,
                'sisa_akumulasi_kontak'      => 0,
                'sisa_akumulasi_followup'    => 0,
                'pencapaian_hari_ini_kontak' => 0,
                'pencapaian_hari_ini_followup'=> 0,
            ];
        }

        $now   = Carbon::now();
        $start = $target->tanggal_mulai->copy();
        $end   = $target->tanggal_selesai->copy();
        $totalDays = $start->diffInDays($end) + 1;

        if ($target->tipe_periode === 'Harian') {
            $dailyKontak  = (int) $target->target_kontak;
            $dailyFollowup = (int) $target->target_followup;
        } else {
            // Base daily target (rounded up)
            $dailyKontak  = (int) ceil($target->target_kontak / $totalDays);
            $dailyFollowup = (int) ceil($target->target_followup / $totalDays);
        }

        // How many days have elapsed since period start (excluding today)
        $elapsedDays = max(0, $start->diffInDays($now->copy()->startOfDay()));

        // Expected achievement up to yesterday
        $expectedKontak   = $dailyKontak * $elapsedDays;
        $expectedFollowup = $dailyFollowup * $elapsedDays;

        // Actual achievement up to yesterday
        $achievedUpToYesterday = $this->getAchievementUpToDate($sales, $target, $now->copy()->subDay());

        // Sisa (snowball) = expected - achieved so far (minimum 0)
        $sisaKontak   = max(0, $expectedKontak - $achievedUpToYesterday['kontak_baru']);
        $sisaFollowup = max(0, $expectedFollowup - $achievedUpToYesterday['follow_up']);

        // Actual achievement today
        $achievedToday = $this->getAchievementUpToDate($sales, $target, $now);
        $pencapaianKontakHariIni = max(0, $achievedToday['kontak_baru'] - $achievedUpToYesterday['kontak_baru']);
        $pencapaianFollowupHariIni = max(0, $achievedToday['follow_up'] - $achievedUpToYesterday['follow_up']);

        return [
            'target_hari_ini_kontak'     => $dailyKontak + $sisaKontak,
            'target_hari_ini_followup'   => $dailyFollowup + $sisaFollowup,
            'sisa_akumulasi_kontak'      => $sisaKontak,
            'sisa_akumulasi_followup'    => $sisaFollowup,
            'pencapaian_hari_ini_kontak' => $pencapaianKontakHariIni,
            'pencapaian_hari_ini_followup'=> $pencapaianFollowupHariIni,
        ];
    }

    /**
     * Get achievement up to a specific date (for snowball calculation).
     */
    private function getAchievementUpToDate(User $sales, Target $target, Carbon $upToDate): array
    {
        $from = $target->tanggal_mulai->startOfDay();
        $to   = $upToDate->endOfDay();

        if ($to < $from) {
            return ['kontak_baru' => 0, 'follow_up' => 0];
        }

        $kontakBaru = Prospek::where('sales_id', $sales->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $followUp = FollowUp::where('user_id', $sales->id)
            ->whereBetween('tanggal', [$from, $to])
            ->count();

        return [
            'kontak_baru' => $kontakBaru,
            'follow_up'   => $followUp,
        ];
    }

    /**
     * Build the complete stats array for Sales Dashboard.
     * Fallback gracefully when no target exists.
     *
     * @return array
     */
    public function getStats(User $user): array
    {
        $now    = Carbon::now();
        $target = $this->getActiveTarget($user);
        $role = strtolower($user->role);

        // Count all prospects this user is handler for
        $prospekQuery = Prospek::query();
        if ($role === 'cs') {
            $prospekQuery->where('cs_id', $user->id);
        } else {
            $prospekQuery->where('sales_id', $user->id);
        }

        $totalProspek  = (clone $prospekQuery)->count();
        $activeProspek = (clone $prospekQuery)
            ->whereNotIn('status', ['LUNAS', 'DINGIN'])
            ->count();
        $closing = (clone $prospekQuery)
            ->where('status', 'LUNAS')
            ->count();
        $lost = (clone $prospekQuery)
            ->where('status', 'DINGIN')
            ->count();

        // Follow-up scheduled / active
        $followUpCount = (clone $prospekQuery)
            ->where('status', 'HANGAT')
            ->count();

        if ($target) {
            $achievement     = $this->getAchievement($user, $target);
            $targetBulanIni  = $target->target_kontak;
            $realisasiKontak = $achievement['kontakBaru'];
            $realisasiClosing = $closing;
            $percentage      = $targetBulanIni > 0 ? min(100, round(($realisasiKontak / $targetBulanIni) * 100)) : 0;
            $sisaTarget      = max(0, $targetBulanIni - $realisasiKontak);
        } else {
            // No target set — show zeros with graceful fallback
            $targetBulanIni  = 0;
            $realisasiKontak = 0;
            $realisasiClosing = $closing;
            $percentage      = 0;
            $sisaTarget      = 0;
        }

        return [
            'total_prospek'    => $totalProspek,
            'active_prospek'   => $activeProspek,
            'follow_up'        => $followUpCount,
            'closing'          => $closing,
            'lost'             => $lost,
            'target_bulan_ini' => $targetBulanIni,
            'realisasi_closing' => $realisasiClosing,
            'percentage'       => $percentage,
            'sisa_target'      => $sisaTarget,
            'bulan_label'      => $now->locale('id')->isoFormat('MMMM Y'),
        ];
    }

    /**
     * Get pipeline stage distribution for a specific Sales user.
     *
     * @return array
     */
    public function getPipelineStages(User $sales): array
    {
        $stageNames = [
            'BARU',
            'KONTAK',
            'HANGAT',
            'PANAS',
            'FORMULIR',
            'BERKAS',
            'LUNAS',
            'DINGIN',
        ];

        $colorMap = [
            'BARU'      => 'badge-cold-lead',
            'KONTAK'    => 'badge-interested',
            'HANGAT'    => 'badge-follow-up',
            'PANAS'     => 'badge-hot-lead',
            'FORMULIR'  => 'badge-beli-formulir',
            'BERKAS'    => 'badge-pembayaran-termin-1',
            'LUNAS'     => 'badge-closing',
            'DINGIN'    => 'badge-lost',
        ];

        return array_map(function ($name) use ($sales, $colorMap) {
            $count = Prospek::where('sales_id', $sales->id)
                ->where('status', $name)
                ->count();
            return [
                'name'  => $name,
                'count' => $count,
                'color' => $colorMap[$name] ?? '',
            ];
        }, $stageNames);
    }
}
