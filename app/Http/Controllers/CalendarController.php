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
        $pageTitle = 'Agenda CRM';
        $currentUser = [
            'name' => $user->name,
            'role' => $user->role,
            'role_label' => strtoupper($user->role),
            'avatar' => strtoupper(substr($user->name, 0, 1)),
        ];

        // Prepare data for the Add Agenda modal
        $bawahan = collect();
        if (in_array(strtolower($user->role), ['hm', 'admin'])) {
            $bawahan = \App\Models\User::whereIn('role', ['SPV', 'Sales', 'CS'])->where('status', 'Aktif')->orderBy('name')->get();
        } elseif (strtolower($user->role) === 'spv') {
            $teamIds = $user->teamMemberIds();
            $bawahan = \App\Models\User::whereIn('id', $teamIds)->where('status', 'Aktif')->orderBy('name')->get();
        }

        return view('calendar.index', compact('pageTitle', 'currentUser', 'bawahan'));
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
        } elseif (in_array($role, ['hm', 'head marketing'])) {
            $hmTeamIds = $user->hmMemberIds();
            $hmTeamIds[] = $user->id;
            $query->where(function($q) use ($user, $hmTeamIds) {
                $q->where('eo_id', $user->id)
                  ->orWhereHas('sales', function ($sq) use ($hmTeamIds) { $sq->whereIn('users.id', $hmTeamIds); })
                  ->orWhereHas('spvs', function ($sq) use ($hmTeamIds) { $sq->whereIn('users.id', $hmTeamIds); });
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
            $query->where('eo_id', $user->id);
        }

        $events = $query->get()->map(function ($event) use ($user) {
            $start = \Carbon\Carbon::parse($event->tanggal_mulai);
            $end = \Carbon\Carbon::parse($event->tanggal_selesai);

            $jenisLabel = $event->type ? $event->type->nama : ($event->jenis_institusi ?: 'Agenda / Meeting');
            $title = $event->nama ?: $event->name;
            
            // Extract custom jenis from title if it was prefixed with [Jenis]
            if (preg_match('/^\[(.*?)\]\s*(.*)$/', $title, $matches)) {
                $jenisLabel = $matches[1];
                $title = $matches[2];
            }
            
            $salesIds = $event->sales->pluck('id')->toArray();
            $isCreator = $event->eo_id === $user->id;
            // Personal if creator and no one else is assigned, or only creator is assigned.
            $isPersonal = $isCreator && (count($salesIds) === 0 || (count($salesIds) === 1 && in_array($user->id, $salesIds)));

            return [
                'id'            => $event->id,
                'title'         => $title,
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
                'is_personal'   => $isPersonal,
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

        if (in_array(strtolower($user->role), ['data analyst', 'data-analyst'])) {
            abort(403, 'Data Analyst tidak memiliki hak akses untuk membuat agenda rapat operasional.');
        }

        $request->validate([
            'name'          => 'required|string|max:255',
            'tanggal'       => 'required|date',
            'waktu_mulai'   => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'lokasi'        => 'nullable|string|max:255',
            'deskripsi'     => 'nullable|string',
            'jenis'         => 'required|string|max:100',
            'assigned_users'=> 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ], [
            'waktu_selesai.after' => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        $startStr = $request->tanggal . ' ' . $request->waktu_mulai . ':00';
        $endStr = $request->tanggal . ' ' . $request->waktu_selesai . ':00';

        $namaAgenda = $request->name;
        $jenisInstitusi = in_array($request->jenis, ['Sekolah', 'Perusahaan']) ? $request->jenis : null;
        
        // If it's a custom type not in the enum, embed it in the name so we can retrieve it later
        if (!$jenisInstitusi && $request->jenis && $request->jenis !== 'Lainnya') {
            $namaAgenda = '[' . $request->jenis . '] ' . $namaAgenda;
        }

        $event = Event::create([
            'name'            => $namaAgenda,
            'nama'            => $namaAgenda,
            'tanggal'         => $request->tanggal,
            'waktu_mulai'     => $request->waktu_mulai,
            'waktu_selesai'   => $request->waktu_selesai,
            'tanggal_mulai'   => $startStr,
            'tanggal_selesai' => $endStr,
            'lokasi'          => $request->lokasi,
            'deskripsi'       => $request->deskripsi,
            'jenis_institusi' => $jenisInstitusi,
            'eo_id'           => $user->id,
            'status'          => 'Scheduled',
        ]);

        $assignedIds = $request->input('assigned_users', []);
        
        // Always assign the creator if they didn't assign anyone else, or if they explicitly assigned themselves
        if (empty($assignedIds)) {
            $assignedIds[] = $user->id;
        }

        $event->sales()->attach($assignedIds, ['assigned_by_spv_id' => $user->id]);

        return redirect()->back()->with('success', "Agenda '{$event->nama}' berhasil dijadwalkan!");
    }
}
