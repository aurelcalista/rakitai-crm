<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Services\SalesTargetService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function __construct(private SalesTargetService $targetService)
    {
    }

    /**
     * Halaman Target & Performa untuk Sales.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Ambil list sales dalam tim
        // Dalam konteks Sales, mungkin hanya bisa melihat dirinya sendiri atau timnya.
        // Asumsi: Semua sales ditampilkan untuk leaderboard, atau hanya data pribadi + agregat tim.
        // Kita tampilkan semua sales untuk leaderboard seperti di mockup.
        
        $salesUsers = \App\Models\User::where('role', 'Sales')->get();
        $team = [];

        $totalTarget = 0;
        $totalRealisasi = 0;

        foreach ($salesUsers as $s) {
            $stats = $this->targetService->getStats($s);
            $dailyTarget = $this->targetService->calculateDailyTarget($s);
            $targetBulanan = 50; // Idealnya dari DB Target, kita mock ke 50 jika belum ada model Target yg fix
            
            // Kita coba pakai stats dari service
            $achievement = $targetBulanan > 0 ? round(($stats['realisasi_closing'] / $targetBulanan) * 100) : 0;
            
            $team[] = [
                'name'        => $s->name,
                'role'        => $s->role,
                'target'      => $targetBulanan,
                'prospects'   => $stats['total_prospek'],
                'follow_up'   => $stats['follow_up'],
                'closing'     => $stats['realisasi_closing'],
                'lost'        => $stats['lost'],
                'achievement' => $achievement,
                'avatar'      => strtoupper(substr($s->name, 0, 2)),
                'status'      => $stats['realisasi_closing'] >= $targetBulanan ? 'Target Achieved' : 'On Progress'
            ];

            $totalTarget += $targetBulanan;
            $totalRealisasi += $stats['realisasi_closing'];
        }

        $summary = [
            'target'      => $totalTarget,
            'realisasi'   => $totalRealisasi,
            'achievement' => $totalTarget > 0 ? round(($totalRealisasi / $totalTarget) * 100) : 0,
            'sisa_target' => max(0, $totalTarget - $totalRealisasi),
        ];

        return view('performa.index', compact('team', 'summary'));
    }
}
