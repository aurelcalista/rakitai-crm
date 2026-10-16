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
            'avatar' => $user->initials,
            'avatar_url' => $user->avatar_url,
        ];

        // Prepare data for the Add Agenda modal - allow tagging all active colleagues
        $bawahan = \App\Models\User::where('status', 'Aktif')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'email']);

        return view('calendar.index', compact('pageTitle', 'currentUser', 'bawahan'));
    }

    /**
     * API endpoint to fetch events, meetings, and internal schedules dynamically.
     * All events and agendas are visible across all roles in Kalender Internal.
     */
    public function fetchEvents(Request $request)
    {
        $user = Auth::user();

        // Internal calendar shows all agendas and events to all authenticated users
        $events = Event::with(['type', 'eo', 'spvs', 'sales', 'takeoverBy'])
            ->get()
            ->map(function ($event) use ($user) {
                $start = $event->tanggal_mulai ? \Carbon\Carbon::parse($event->tanggal_mulai) : ($event->tanggal ? \Carbon\Carbon::parse($event->tanggal . ' ' . ($event->waktu_mulai ?? '08:00')) : now());
                $end = $event->tanggal_selesai ? \Carbon\Carbon::parse($event->tanggal_selesai) : ($event->tanggal ? \Carbon\Carbon::parse($event->tanggal . ' ' . ($event->waktu_selesai ?? '09:00')) : now());

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

                $waktuMulai = $event->waktu_mulai ? (is_string($event->waktu_mulai) ? substr($event->waktu_mulai, 0, 5) : \Carbon\Carbon::parse($event->waktu_mulai)->format('H:i')) : $start->format('H:i');
                $waktuSelesai = $event->waktu_selesai ? (is_string($event->waktu_selesai) ? substr($event->waktu_selesai, 0, 5) : \Carbon\Carbon::parse($event->waktu_selesai)->format('H:i')) : $end->format('H:i');
                $tanggal = $event->tanggal ? \Carbon\Carbon::parse($event->tanggal)->format('Y-m-d') : $start->format('Y-m-d');

                return [
                    'id'            => $event->id,
                    'title'         => $title,
                    'start'         => $start->format('Y-m-d\TH:i:s'),
                    'end'           => $end->format('Y-m-d\TH:i:s'),
                    'tanggal'       => $tanggal,
                    'waktu_mulai'   => $waktuMulai,
                    'waktu_selesai' => $waktuSelesai,
                    'lokasi'        => $event->lokasi ?: '-',
                    'deskripsi'     => $event->deskripsi,
                    'jenis'         => $jenisLabel,
                    'eo_name'       => $event->eo ? $event->eo->name : '-',
                    'status'        => $event->status,
                    'is_personal'   => $isPersonal,
                    // is_eo_event: true when created by EO role (has a master-data type_id)
                    'is_eo_event'   => !is_null($event->type_id),
                    'takeover_by_id'=> $event->takeover_by_id,
                    'takeover_by'   => $event->takeoverBy ? [
                        'id'   => $event->takeoverBy->id,
                        'name' => $event->takeoverBy->name,
                        'role' => $event->takeoverBy->role,
                    ] : null,
                    'takeover_at'   => $event->takeover_at ? \Carbon\Carbon::parse($event->takeover_at)->format('d/m/Y H:i') : null,
                    'spvs'          => $event->spvs->map(fn($spv) => ['id' => $spv->id, 'name' => $spv->name]),
                    'sales'         => $event->sales->map(fn($s) => ['id' => $s->id, 'name' => $s->name]),
                ];
            });

        return response()->json($events);
    }

    /**
     * Store Internal Meeting / Schedule.
     * Accessible by operational roles.
     * Agenda is global/shared - no assigned_users selection and no takeover needed.
     * Automatically dispatches notifications to other active users across roles.
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

        $rawJenis = $request->input('jenis') ?: 'Lainnya';
        $namaAgenda = $request->name;
        $jenisInstitusi = in_array($rawJenis, ['Sekolah', 'Perusahaan']) ? $rawJenis : null;
        
        // If it's a custom type not in the enum, embed it in the name so we can retrieve it later
        if (!$jenisInstitusi && $rawJenis && $rawJenis !== 'Lainnya') {
            $namaAgenda = '[' . $rawJenis . '] ' . $namaAgenda;
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

        // Kirim notifikasi agenda kalender baru ke seluruh personil aktif lain lintas role
        try {
            $otherUsers = \App\Models\User::where('status', 'Aktif')
                ->where('id', '!=', $user->id)
                ->get();

            if ($otherUsers->isNotEmpty()) {
                $cleanTitle = $request->name;
                $tanggalFormat = \Carbon\Carbon::parse($request->tanggal)->locale('id')->isoFormat('D MMMM Y');
                $lokasiInfo = $request->lokasi ? " di {$request->lokasi}" : '';
                $jamInfo = "{$request->waktu_mulai} - {$request->waktu_selesai} WIB";

                $title = "Agenda Kalender Baru";
                $message = "{$user->name} ({$user->role}) menambahkan agenda: '{$cleanTitle}' pada {$tanggalFormat} ({$jamInfo}){$lokasiInfo}.";

                foreach ($otherUsers as $recipient) {
                    $recipient->notify(new \App\Notifications\CrmActivityNotification(
                        title: $title,
                        message: $message,
                        type: 'info',
                        link: '/calendar',
                        icon: '',
                        senderName: $user->name,
                        senderRole: $user->role,
                        action: 'calendar_agenda_created',
                        extraData: [
                            'event_id' => $event->id,
                            'url' => '/calendar',
                        ]
                    ));
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal mengirim notifikasi agenda kalender: " . $e->getMessage());
        }

        return redirect()->back()->with('success', "Agenda '{$event->nama}' berhasil dijadwalkan!");
    }

    /**
     * Take over responsibility for an event.
     * Allowed for all operational roles with calendar access EXCEPT Admin and Data Analyst.
     * Protected with database transaction and row-level locking (lockForUpdate)
     * to atomically prevent race conditions and double takeovers.
     */
    public function takeoverEvent(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $disallowedRoles = ['admin', 'data analyst', 'data-analyst', 'analyst'];
        if (in_array(strtolower($user->role ?? ''), $disallowedRoles)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Role Anda tidak diizinkan melakukan Takeover.'], 403);
            }
            return redirect()->back()->with('error', 'Role Anda tidak diizinkan melakukan Takeover.');
        }

        try {
            $event = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $user) {
                $event = Event::where('id', $id)->lockForUpdate()->firstOrFail();

                if (!is_null($event->takeover_by_id)) {
                    throw new \DomainException('Event sudah memiliki penanggung jawab.');
                }

                $event->update([
                    'takeover_by_id' => $user->id,
                    'takeover_at'    => now(),
                ]);

                return $event;
            });

            // Refresh model with relation
            $event->load('takeoverBy');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Berhasil melakukan Takeover untuk event '{$event->name}'!",
                    'event'   => [
                        'id'             => $event->id,
                        'takeover_by_id' => $user->id,
                        'takeover_by'    => [
                            'id'   => $user->id,
                            'name' => $user->name,
                            'role' => $user->role,
                        ],
                        'takeover_at'    => $event->takeover_at ? $event->takeover_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                    ],
                ]);
            }

            return redirect()->back()->with('success', "Berhasil melakukan Takeover untuk event '{$event->name}'!");
        } catch (\DomainException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Event tidak ditemukan.'], 404);
            }
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }
    }
}
