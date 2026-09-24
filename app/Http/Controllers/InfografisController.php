<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Kunjungan;
use App\Models\Target;
use App\Models\Wilayah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InfografisController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Prospek::query();

        // Scope by authorization
        if ($user->role === 'Sales') {
            $query->where('sales_id', $user->id);
        } elseif ($user->role === 'CS') {
            $query->where('cs_id', $user->id);
        } elseif ($user->role === 'SPV') {
            $query->whereIn('sales_id', User::where('supervisor_id', $user->id)->pluck('id'));
        } elseif ($user->role === 'HM') {
            $hmWilayahIds = $user->activeWilayahIds();
            if (!empty($hmWilayahIds)) {
                $query->whereIn('wilayah_id', $hmWilayahIds);
            }
        }

        // Metrics Summary
        $cancelCount = (clone $query)->where('status', 'CANCEL')->count();
        $pemberkasanCount = (clone $query)->where('status', 'BERKAS')->count();
        $lunasCount = (clone $query)->where('status', 'LUNAS')->count();
        $totalProspek = (clone $query)->count();

        // Target Pemberkasan
        $targetPemberkasan = Target::where('status', 'Aktif')->sum('target_pemberkasan');

        // Territory metrics
        $wilayahStats = Wilayah::withCount(['prospeks as total_prospek', 'prospeks as total_lunas' => function($q) {
            $q->where('status', 'LUNAS');
        }, 'prospeks as total_cancel' => function($q) {
            $q->where('status', 'CANCEL');
        }, 'prospeks as total_berkas' => function($q) {
            $q->where('status', 'BERKAS');
        }])->get();

        // Indikator & Skor Wilayah Performance Service
        $perfService = new \App\Services\WilayahPerformanceService();
        $scopedIds = strtolower($user->role) === 'hm' ? $user->activeWilayahIds() : null;
        $wilayahIndicators = $perfService->getAllWilayahPerformance(null, $scopedIds);
        $bobotWeights = $perfService->getWeights();

        return view('infografis.index', compact(
            'cancelCount',
            'pemberkasanCount',
            'lunasCount',
            'totalProspek',
            'targetPemberkasan',
            'wilayahStats',
            'wilayahIndicators',
            'bobotWeights'
        ));
    }
}
