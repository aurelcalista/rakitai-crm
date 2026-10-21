<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Event::whereHas('sales', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->with(['type', 'sekolah', 'perusahaan', 'spvs', 'eo'])
            ->orderByRaw('COALESCE(tanggal_mulai, CONCAT(tanggal, " ", COALESCE(waktu_mulai, "00:00:00"))) ASC');

        if ($request->wantsJson() || $request->ajax()) {
            $events = $query->get()->map(function ($e) use ($userId) {
                $salesPivot = $e->sales->firstWhere('id', $userId)?->pivot;
                $spv = $e->spvs->first();
                $start = $e->tanggal_mulai ?: ($e->tanggal ? $e->tanggal->format('Y-m-d') . ' ' . ($e->waktu_mulai ? \Carbon\Carbon::parse($e->waktu_mulai)->format('H:i') : '08:00') : null);

                return [
                    'id'              => $e->id,
                    'name'            => $e->nama ?: $e->name,
                    'status'          => $e->status,
                    'tanggal_mulai'   => $start,
                    'tanggal_mulai_formatted' => $start ? \Carbon\Carbon::parse($start)->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                    'lokasi'          => $e->lokasi ?: '-',
                    'institusi'       => $e->nama_institusi ?: ($e->sekolah?->nama ?? $e->perusahaan?->nama ?? '-'),
                    'jenis_institusi' => $e->jenis_institusi,
                    'pic_name'        => $e->pic_name,
                    'pic_whatsapp'    => $e->pic_whatsapp,
                    'spv_name'        => $spv?->name,
                    'spv_role'        => $spv?->role ?? 'SPV',
                    'eo_name'         => $e->eo?->name,
                    'kehadiran'       => $salesPivot?->kehadiran,
                    'is_started'      => $start ? \Carbon\Carbon::now('Asia/Jakarta')->greaterThanOrEqualTo(\Carbon\Carbon::parse($start, 'Asia/Jakarta')) : true,
                ];
            });

            return response()->json([
                'success' => true,
                'events'  => $events,
            ]);
        }

        $events = $query->get();

        return view('sales.event.index', compact('events'));
    }
}
