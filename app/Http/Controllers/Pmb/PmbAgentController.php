<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PmbAgentController extends Controller
{
    /**
     * Tampilkan halaman Dasbor & Rekapitulasi Agent PMB.
     */
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));
        $search    = trim((string) $request->input('search_agent', ''));

        $startCarbon = Carbon::parse($startDate)->startOfDay();
        $endCarbon   = Carbon::parse($endDate)->endOfDay();

        // Base Query untuk filter rentang tanggal
        $baseQuery = Prospek::whereBetween('created_at', [$startCarbon, $endCarbon]);

        // KPI Ringkasan Statistik
        $totalPendaftar = (clone $baseQuery)->count();

        $pendaftarViaAgent = (clone $baseQuery)
            ->whereNotNull('agent')
            ->where(DB::raw("TRIM(agent)"), '!=', '')
            ->count();

        $tanpaAgent = (clone $baseQuery)
            ->where(function ($q) {
                $q->whereNull('agent')
                  ->orWhere(DB::raw("TRIM(agent)"), '=', '');
            })
            ->count();

        $totalAgentAktif = (clone $baseQuery)
            ->whereNotNull('agent')
            ->where(DB::raw("TRIM(agent)"), '!=', '')
            ->distinct('agent')
            ->count('agent');

        $pctViaAgent   = $totalPendaftar > 0 ? round(($pendaftarViaAgent / $totalPendaftar) * 100, 1) : 0;
        $pctTanpaAgent = $totalPendaftar > 0 ? round(($tanpaAgent / $totalPendaftar) * 100, 1) : 0;

        // Rekapitulasi per Agent (GROUP BY)
        $groupsQuery = (clone $baseQuery)
            ->select(
                DB::raw("CASE WHEN agent IS NULL OR TRIM(agent) = '' THEN 'Tanpa Agent' ELSE TRIM(agent) END AS agent_name"),
                DB::raw('COUNT(*) as total_pendaftar')
            )
            ->groupBy('agent_name')
            ->orderByDesc('total_pendaftar');

        if (!empty($search)) {
            $groupsQuery->having('agent_name', 'like', "%{$search}%");
        }

        $agentGroups = $groupsQuery->get()->map(function ($row) use ($totalPendaftar) {
            $isTanpaAgent = ($row->agent_name === 'Tanpa Agent');
            $proporsi = $totalPendaftar > 0 ? round(($row->total_pendaftar / $totalPendaftar) * 100, 1) : 0;

            return (object) [
                'agent_key'       => $isTanpaAgent ? 'none' : $row->agent_name,
                'agent_name'      => $row->agent_name,
                'is_tanpa_agent'  => $isTanpaAgent,
                'total_pendaftar' => (int) $row->total_pendaftar,
                'proporsi'        => $proporsi,
            ];
        });

        $isCurrentMonth = ($startDate === now()->startOfMonth()->format('Y-m-d') && $endDate === now()->format('Y-m-d'));

        return view('pmb.agent.index', compact(
            'startDate',
            'endDate',
            'search',
            'totalPendaftar',
            'pendaftarViaAgent',
            'tanpaAgent',
            'totalAgentAktif',
            'pctViaAgent',
            'pctTanpaAgent',
            'agentGroups',
            'isCurrentMonth'
        ));
    }

    /**
     * Ekspor rekapitulasi data agent ke CSV / Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));
        $search    = trim((string) $request->input('search_agent', ''));

        $startCarbon = Carbon::parse($startDate)->startOfDay();
        $endCarbon   = Carbon::parse($endDate)->endOfDay();

        $baseQuery = Prospek::whereBetween('created_at', [$startCarbon, $endCarbon]);
        $totalPendaftar = (clone $baseQuery)->count();

        $groupsQuery = (clone $baseQuery)
            ->select(
                DB::raw("CASE WHEN agent IS NULL OR TRIM(agent) = '' THEN 'Tanpa Agent' ELSE TRIM(agent) END AS agent_name"),
                DB::raw('COUNT(*) as total_pendaftar')
            )
            ->groupBy('agent_name')
            ->orderByDesc('total_pendaftar');

        if (!empty($search)) {
            $groupsQuery->having('agent_name', 'like', "%{$search}%");
        }

        $records = $groupsQuery->get();
        $fileName = 'rekapitulasi_agent_pmb_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records, $totalPendaftar, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['LAPORAN REKAPITULASI PENDAFTAR PER AGENT PMB UCIC']);
            fputcsv($handle, ['Periode', $startDate . ' s/d ' . $endDate]);
            fputcsv($handle, ['Total Pendaftar', $totalPendaftar]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'Nama Agent',
                'Status Tipe',
                'Jumlah Pendaftar',
                'Proporsi (%)',
                'URL Referral',
            ]);

            $no = 1;
            foreach ($records as $row) {
                $isTanpa = ($row->agent_name === 'Tanpa Agent');
                $proporsi = $totalPendaftar > 0 ? round(($row->total_pendaftar / $totalPendaftar) * 100, 1) : 0;
                $urlReferral = $isTanpa ? '-' : url('/register/' . $row->agent_name);

                fputcsv($handle, [
                    $no++,
                    $row->agent_name,
                    $isTanpa ? 'Tanpa Agent' : 'Agent Resmi',
                    $row->total_pendaftar,
                    $proporsi . '%',
                    $urlReferral,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
