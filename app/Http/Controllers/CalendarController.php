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
        $events = Event::with(['type', 'eo', 'spvs', 'sales'])
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
                    'spvs'          => $event->spvs->map(fn($spv) => ['id' => $spv->id, 'name' => $spv->name]),
                    'sales'         => $event->sales->map(fn($s) => ['id' => $s->id, 'name' => $s->name]),
                ];
            });

        return response()->json($events);
    }

    /**
     * Store Internal Meeting / Schedule.
     * Accessible by operational roles.
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
            'assigned_users'=> 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
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

        $assignedIds = $request->input('assigned_users', []);
        
        // Always assign the creator if they didn't assign anyone else, or if they explicitly assigned themselves
        if (empty($assignedIds)) {
            $assignedIds[] = $user->id;
        }

        $event->sales()->attach($assignedIds, ['assigned_by_spv_id' => $user->id]);

        // Kirim notifikasi ke seluruh personil aktif lain lintas role
        try {
            $otherUsers = \App\Models\User::where('status', 'Aktif')
                ->where('id', '!=', $user->id)
                ->get();

            if ($otherUsers->isNotEmpty()) {
                $cleanTitle = $request->name;
                $tanggalFormat = \Carbon\Carbon::parse($request->tanggal)->locale('id')->isoFormat('D MMMM Y');
                $lokasiInfo = $request->lokasi ? " di {$request->lokasi}" : '';
                $jamInfo = "{$request->waktu_mulai} - {$request->waktu_selesai} WIB";

                $assignedLookup = array_flip($assignedIds);

                foreach ($otherUsers as $recipient) {
                    $isAssigned = isset($assignedLookup[$recipient->id]);
                    
                    if ($isAssigned) {
                        $title = "Penugasan Agenda Kalender";
                        $message = "Anda ditugaskan pada agenda '{$cleanTitle}' oleh {$user->name} ({$user->role}) pada {$tanggalFormat} ({$jamInfo}){$lokasiInfo}.";
                    } else {
                        $title = "Agenda Kalender Baru";
                        $message = "{$user->name} ({$user->role}) menambahkan agenda: '{$cleanTitle}' pada {$tanggalFormat} ({$jamInfo}){$lokasiInfo}.";
                    }

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
}
