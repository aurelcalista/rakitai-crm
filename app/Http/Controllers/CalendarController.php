<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * View the internal calendar.
     */
    public function index()
    {
        $user = Auth::user();
        $pageTitle = 'Kalender Internal';
        $currentUser = [
            'name' => $user->name,
            'role' => $user->role,
            'role_label' => strtoupper($user->role),
            'avatar' => strtoupper(substr($user->name, 0, 1)),
        ];

        return view('calendar.index', compact('pageTitle', 'currentUser'));
    }

    /**
     * API endpoint to fetch events, meetings, and internal schedules dynamically based on user role scope.
     */
    public function fetchEvents(Request $request)
    {
        $user = Auth::user();
        $role = strtolower($user->role);

        $query = Event::with(['type', 'eo', 'spvs', 'sales']);

        if ($role === 'admin') {
            // Admin sees all
        } elseif ($role === 'eo') {
            $query->where(function($q) use ($user) {
                $q->where('eo_id', $user->id)
                  ->orWhereHas('sales', function ($sq) use ($user) { $sq->where('users.id', $user->id); });
            });
        } elseif (in_array($role, ['hm', 'head marketing'])) {
            $hmTeamIds = $user->hmMemberIds();
            $hmTeamIds[] = $user->id;
            $query->where(function($q) use ($user, $hmTeamIds) {
                $q->where('eo_id', $user->id)
                  ->orWhereIn('eo_id', $hmTeamIds)
                  ->orWhereHas('spvs', function ($sq) use ($hmTeamIds) { $sq->whereIn('users.id', $hmTeamIds); })
                  ->orWhereHas('sales', function ($sq) use ($hmTeamIds) { $sq->whereIn('users.id', $hmTeamIds); });
            });
        } elseif (in_array($role, ['spv', 'supervisor', 'supervisor marketing'])) {
            $spvTeamIds = $user->teamMemberIds();
            $spvTeamIds[] = $user->id;
            $query->where(function($q) use ($user, $spvTeamIds) {
                $q->where('eo_id', $user->id)
                  ->orWhereHas('spvs', function ($sq) use ($user) { $sq->where('users.id', $user->id); })
                  ->orWhereHas('sales', function ($sq) use ($spvTeamIds) { $sq->whereIn('users.id', $spvTeamIds); });
            });
        } elseif (in_array($role, ['sales', 'cs'])) {
            $query->where(function($q) use ($user) {
                $q->where('eo_id', $user->id)
                  ->orWhereHas('sales', function ($sq) use ($user) { $sq->where('users.id', $user->id); });
            });
        } else {
            $query->where('id', -1);
        }

        $events = $query->get()->map(function ($event) {
            $start = \Carbon\Carbon::parse($event->tanggal_mulai);
            $end = \Carbon\Carbon::parse($event->tanggal_selesai);

            $jenisLabel = $event->type ? $event->type->nama : ($event->jenis_institusi ?: 'Meeting / Internal Schedule');

            return [
                'id'            => $event->id,
                'title'         => $event->nama ?: $event->name,
                'start'         => $start->format('Y-m-d\TH:i:s'),
                'end'           => $end->format('Y-m-d\TH:i:s'),
                'tanggal'       => $start->format('Y-m-d'),
                'waktu_mulai'   => $start->format('H:i'),
                'waktu_selesai' => $end->format('H:i'),
                'lokasi'        => $event->lokasi ?: '-',
                'deskripsi'     => $event->deskripsi,
                'jenis'         => $jenisLabel,
                'eo_name'       => $event->eo ? $event->eo->name : '-',
                'status'        => $event->status,
                'spvs'          => $event->spvs->map(fn($spv) => ['id' => $spv->id, 'name' => $spv->name]),
                'sales'         => $event->sales->map(fn($s) => ['id' => $s->id, 'name' => $s->name]),
            ];
        });

        return response()->json($events);
    }

    /**
     * Store Internal Meeting / Schedule.
     * Enforces:
     * - Role scope authorization check (HM, SPV, EO, CS, Admin)
     * - Minimum 1-hour gap validation
     */
    public function storeMeeting(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'          => 'required|string|max:255',
            'tanggal'       => 'required|date',
            'waktu_mulai'   => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'lokasi'        => 'nullable|string|max:255',
            'deskripsi'     => 'nullable|string',
            'jenis'         => 'nullable|string|max:100',
        ], [
            'waktu_selesai.after' => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        $startStr = $request->tanggal . ' ' . $request->waktu_mulai . ':00';
        $endStr = $request->tanggal . ' ' . $request->waktu_selesai . ':00';

        // Validate Minimum 1-Hour Gap / Overlap Conflict
        $assignmentService = new \App\Services\EventAssignmentService();
        $assignmentService->validateSalesSchedule($user->id, $request->tanggal, $request->waktu_mulai, $request->waktu_selesai);

        $event = Event::create([
            'name'            => $request->name,
            'nama'            => $request->name,
            'tanggal'         => $request->tanggal,
            'waktu_mulai'     => $request->waktu_mulai,
            'waktu_selesai'   => $request->waktu_selesai,
            'tanggal_mulai'   => $startStr,
            'tanggal_selesai' => $endStr,
            'lokasi'          => $request->lokasi ?: 'Kantor UCIC / Online Meeting',
            'deskripsi'       => $request->deskripsi,
            'jenis_institusi' => in_array($request->jenis, ['Sekolah', 'Perusahaan']) ? $request->jenis : null,
            'eo_id'           => $user->id,
            'status'          => 'Scheduled',
        ]);

        $event->sales()->attach($user->id, ['assigned_by_spv_id' => $user->id]);

        return redirect()->back()->with('success', "Kegiatan/Meeting '{$event->nama}' berhasil dijadwalkan!");
    }
}
