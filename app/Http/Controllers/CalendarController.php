<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\MasterData;
use App\Models\Prodi;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\User;
use App\Services\EventAssignmentService;
use App\Services\GoogleCalendarService;
use App\Notifications\EventNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $bawahan = User::where('status', 'Aktif')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'email']);

        // Master data for Event EO Edit modal on Calendar
        $eventTypes = MasterData::where('type', 'jenis_event')->where('status', 'Aktif')->get();
        $spvs = User::whereIn('role', ['SPV', 'Supervisor Marketing', 'Supervisor'])->where('status', 'Aktif')->get();
        $salesList = User::where('role', 'Sales')->where('status', 'Aktif')->orderBy('name')->get();
        $prodis = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $sekolahs = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $dosens = User::whereIn('role', ['Dosen', 'Staff', 'Admin', 'SPV'])->where('status', 'Aktif')->get();

        return view('calendar.index', compact(
            'pageTitle',
            'currentUser',
            'bawahan',
            'eventTypes',
            'spvs',
            'salesList',
            'prodis',
            'sekolahs',
            'perusahaans',
            'dosens'
        ));
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
                    'eo_id'         => $event->eo_id,
                    'eo_name'       => $event->eo ? $event->eo->name : '-',
                    'status'        => $event->status,
                    'is_personal'   => $isPersonal,
                    // is_eo_event: true when created by EO role (has a master-data type_id)
                    'is_eo_event'   => !is_null($event->type_id),
                    'type_id'       => $event->type_id,
                    'name'          => $event->name ?: $event->nama,
                    'takeover_by_id'=> $event->takeover_by_id,
                    'takeover_by'   => $event->takeoverBy ? [
                        'id'   => $event->takeoverBy->id,
                        'name' => $event->takeoverBy->name,
                        'role' => $event->takeoverBy->role,
                    ] : null,
                    'takeover_at'   => $event->takeover_at ? \Carbon\Carbon::parse($event->takeover_at)->format('d/m/Y H:i') : null,
                    'spvs'          => $event->spvs->map(fn($spv) => ['id' => $spv->id, 'name' => $spv->name]),
                    'sales'         => $event->sales->map(fn($s) => ['id' => $s->id, 'name' => $s->name]),
                    'spv_ids'       => $event->spvs->pluck('id')->toArray(),
                    'sales_ids'     => $event->sales->pluck('id')->toArray(),
                    'dosen_id'      => $event->dosen_id,
                    'dosen_pemateri'=> $event->dosen_pemateri,
                    'prodi_id'      => $event->prodi_id,
                    'sekolah_id'    => $event->sekolah_id,
                    'perusahaan_id' => $event->perusahaan_id,
                    'jenis_institusi'=> $event->jenis_institusi,
                    'nama_institusi'=> $event->nama_institusi,
                    'alamat'        => $event->alamat,
                    'pic_name'      => $event->pic_name,
                    'pic_whatsapp'  => $event->pic_whatsapp,
                ];
            });

        return response()->json($events);
    }

    /**
     * Store Internal Meeting / Schedule.
     * Accessible by operational roles except Sales.
     * Agenda is global/shared - no assigned_users selection and no takeover needed.
     * Automatically dispatches notifications to other active users across roles.
     */
    public function storeMeeting(Request $request)
    {
        $user = Auth::user();
        if (strtolower($user->role ?? '') === 'sales') {
            abort(403, 'Role Sales tidak diizinkan menambahkan agenda internal.');
        }

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
     * Update an existing internal agenda / meeting.
     * Accessible by EO, Admin, or the agenda's creator.
     */
    public function updateMeeting(Request $request, $id)
    {
        $event = Event::find($id);
        if (!$event) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Agenda tidak ditemukan.'], 404);
            }
            return redirect()->route('calendar.index')->with('error', 'Agenda tidak ditemukan atau sudah dihapus.');
        }

        // Must be an agenda (type_id is null)
        if (!is_null($event->type_id)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Record ini bukan agenda internal.'], 400);
            }
            return redirect()->route('calendar.index')->with('error', 'Record ini bukan agenda internal.');
        }

        $user = Auth::user();
        if (strtolower($user->role ?? '') === 'sales') {
            abort(403, 'Role Sales tidak diizinkan mengedit agenda.');
        }

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

        if (!$jenisInstitusi && $rawJenis && $rawJenis !== 'Lainnya') {
            $namaAgenda = '[' . $rawJenis . '] ' . $namaAgenda;
        }

        $event->update([
            'name'            => $namaAgenda,
            'nama'            => $namaAgenda,
            'tanggal'         => $request->tanggal,
            'waktu_mulai'     => $request->waktu_mulai,
            'waktu_selesai'   => $request->waktu_selesai,
            'tanggal_mulai'   => $startStr,
            'tanggal_selesai' => $endStr,
            'lokasi'          => $request->lokasi ?? $event->lokasi ?? '-',
            'deskripsi'       => $request->deskripsi,
            'jenis_institusi' => $jenisInstitusi,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Agenda '{$event->nama}' berhasil diperbarui!",
            ]);
        }

        return redirect()->route('calendar.index')->with('success', "Agenda '{$event->nama}' berhasil diperbarui!");
    }

    /**
     * Delete an existing internal agenda / meeting.
     * Accessible by all roles except Sales.
     */
    public function destroyMeeting(Request $request, $id)
    {
        $event = Event::find($id);
        if (!$event) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Agenda tidak ditemukan.'], 404);
            }
            return redirect()->route('calendar.index')->with('error', 'Agenda tidak ditemukan atau sudah dihapus.');
        }

        // Must be an agenda (type_id is null)
        if (!is_null($event->type_id)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Record ini bukan agenda internal.'], 400);
            }
            return redirect()->route('calendar.index')->with('error', 'Record ini bukan agenda internal.');
        }

        $user = Auth::user();
        if (strtolower($user->role ?? '') === 'sales') {
            abort(403, 'Role Sales tidak diizinkan menghapus agenda.');
        }

        $nama = $event->nama ?: $event->name;

        // Clean up relations if any exist
        if ($event->sales()->exists()) {
            $event->sales()->detach();
        }
        if ($event->spvs()->exists()) {
            $event->spvs()->detach();
        }

        $event->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Agenda '{$nama}' berhasil dihapus!",
            ]);
        }

        return redirect()->route('calendar.index')->with('success', "Agenda '{$nama}' berhasil dihapus!");
    }

    /**
     * Update an existing Event EO from Calendar.
     * Accessible by all roles except Sales.
     * Preserves takeover data and verifies schedule clashes.
     */
    public function updateEvent(Request $request, $id, EventAssignmentService $assignmentService, GoogleCalendarService $calendarService)
    {
        $event = Event::whereNotNull('type_id')->findOrFail($id);

        Gate::authorize('update', $event);

        $request->validate([
            'name'           => 'required|string|max:255',
            'type_id'        => 'required|exists:master_data,id',
            'tanggal'        => 'required|date',
            'waktu_mulai'    => 'required|date_format:H:i',
            'waktu_selesai'  => 'required|date_format:H:i|after:waktu_mulai',
            'lokasi'         => 'required|string|max:255',
            'spvs'           => 'nullable|array',
            'spvs.*'         => 'exists:users,id',
            'sales'          => 'nullable|array',
            'sales.*'        => 'exists:users,id',
            'deskripsi'      => 'nullable|string',
            'dosen_id'       => 'nullable|exists:users,id',
            'dosen_pemateri' => 'nullable|string|max:255',
            'prodi_id'       => 'nullable|exists:prodis,id',
            'sekolah_id'     => 'nullable|exists:sekolahs,id',
            'perusahaan_id'  => 'nullable|exists:perusahaans,id',
            'jenis_institusi'=> 'nullable|in:Sekolah,Perusahaan',
            'nama_institusi' => 'nullable|string|max:255',
            'alamat'         => 'nullable|string|max:500',
            'pic_name'       => 'nullable|string|max:255',
            'pic_whatsapp'   => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
        ]);

        // Check if event type is Training
        $type = MasterData::find($request->type_id);
        $isTraining = ($type && stripos($type->nama, 'training') !== false) ||
                      stripos($request->name, 'training') !== false ||
                      $request->boolean('is_training');

        if ($isTraining && empty($request->dosen_id) && empty($request->dosen_pemateri)) {
            throw ValidationException::withMessages([
                'dosen_id' => 'Dosen Pemateri wajib diisi untuk event Training.'
            ]);
        }

        DB::transaction(function () use ($request, $event, $assignmentService, $calendarService) {
            $oldStart = \Carbon\Carbon::parse($event->tanggal_mulai);
            $oldEnd = \Carbon\Carbon::parse($event->tanggal_selesai);

            $isTimeChanged = $oldStart->format('Y-m-d') !== $request->tanggal ||
                             $oldStart->format('H:i') !== $request->waktu_mulai ||
                             $oldEnd->format('H:i') !== $request->waktu_selesai;

            $event->update([
                'name'            => $request->name,
                'nama'            => $request->name,
                'type_id'         => $request->type_id,
                'tanggal'         => $request->tanggal,
                'waktu_mulai'     => $request->waktu_mulai,
                'waktu_selesai'   => $request->waktu_selesai,
                'tanggal_mulai'   => $request->tanggal . ' ' . $request->waktu_mulai . ':00',
                'tanggal_selesai' => $request->tanggal . ' ' . $request->waktu_selesai . ':00',
                'lokasi'          => $request->lokasi,
                'deskripsi'       => $request->deskripsi,
                'dosen_id'        => $request->dosen_id,
                'dosen_pemateri'  => $request->dosen_pemateri,
                'prodi_id'        => $request->prodi_id,
                'sekolah_id'      => $request->sekolah_id,
                'perusahaan_id'   => $request->perusahaan_id,
                'jenis_institusi' => $request->jenis_institusi,
                'nama_institusi'  => $request->nama_institusi,
                'alamat'          => $request->alamat,
                'pic_name'        => $request->pic_name,
                'pic_whatsapp'    => \App\Support\WhatsAppHelper::formatForDatabase($request->pic_whatsapp),
            ]);

            if ($request->has('spvs')) {
                $oldSpvs = $event->spvs->pluck('id')->toArray();
                $newSpvs = $request->spvs ?? [];
                $event->spvs()->sync($newSpvs);

                $removedSpvs = array_diff($oldSpvs, $newSpvs);
                if (!empty($removedSpvs)) {
                    $removedSalesIds = DB::table('event_sales')
                                         ->where('event_id', $event->id)
                                         ->whereIn('assigned_by_spv_id', $removedSpvs)
                                         ->pluck('sales_id')->toArray();

                    foreach ($removedSalesIds as $rSalesId) {
                        $rSalesUser = User::find($rSalesId);
                        if ($rSalesUser) {
                            $calendarService->removeSalesEvent($event, $rSalesUser);
                        }
                    }

                    if (!empty($removedSalesIds)) {
                        $removedSalesUsers = User::whereIn('id', $removedSalesIds)->get();
                        \Illuminate\Support\Facades\Notification::send($removedSalesUsers, new EventNotification($event, 'assignment_removed'));
                    }
                }
            }

            if ($request->has('sales')) {
                $newSalesList = $request->sales ?? [];
                $currentSalesIds = $event->sales->pluck('id')->toArray();
                $toRemove = array_diff($currentSalesIds, $newSalesList);
                $toAdd = array_diff($newSalesList, $currentSalesIds);

                foreach ($toRemove as $remId) {
                    $remUser = User::find($remId);
                    if ($remUser) {
                        $calendarService->removeSalesEvent($event, $remUser);
                    }
                }

                foreach ($toAdd as $addId) {
                    $event->sales()->attach($addId, ['assigned_by_spv_id' => Auth::id()]);
                    $addUser = User::find($addId);
                    if ($addUser) {
                        $calendarService->syncSalesEvent($event, $addUser);
                    }
                }
            }

            $calendarService->syncAllAssignedSales($event);

            if ($isTimeChanged) {
                $currentSales = $event->sales()->get();
                $invalidSalesIds = [];

                foreach ($currentSales as $salesUser) {
                    try {
                        $assignmentService->validateSalesSchedule(
                            $salesUser->id,
                            $request->tanggal,
                            $request->waktu_mulai,
                            $request->waktu_selesai,
                            $event->id
                        );
                    } catch (ValidationException $e) {
                        $invalidSalesIds[] = $salesUser->id;
                    }
                }

                if (!empty($invalidSalesIds)) {
                    foreach ($invalidSalesIds as $invId) {
                        $invUser = User::find($invId);
                        if ($invUser) {
                            $calendarService->removeSalesEvent($event, $invUser);
                        }
                    }

                    $invalidSalesUsers = User::whereIn('id', $invalidSalesIds)->get();
                    \Illuminate\Support\Facades\Notification::send($invalidSalesUsers, new EventNotification($event, 'assignment_removed'));

                    \Illuminate\Support\Facades\Notification::send($event->spvs, new EventNotification($event, 'updated'));
                    session()->flash('warning', 'Beberapa Sales telah dihapus dari tugas karena jadwal baru menyebabkan bentrok.');
                } else {
                    \Illuminate\Support\Facades\Notification::send($event->sales, new EventNotification($event, 'updated'));
                    \Illuminate\Support\Facades\Notification::send($event->spvs, new EventNotification($event, 'updated'));
                }
            } else {
                \Illuminate\Support\Facades\Notification::send($event->sales, new EventNotification($event, 'updated'));
                \Illuminate\Support\Facades\Notification::send($event->spvs, new EventNotification($event, 'updated'));
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Event '{$event->name}' berhasil diperbarui!",
            ]);
        }

        return redirect()->route('calendar.index')->with('success', "Event '{$event->name}' berhasil diperbarui!");
    }

    /**
     * Delete an existing Event EO from Calendar.
     * Accessible by all roles except Sales.
     */
    public function destroyEvent(Request $request, $id, GoogleCalendarService $calendarService)
    {
        $event = Event::whereNotNull('type_id')->findOrFail($id);

        Gate::authorize('delete', $event);

        $nama = $event->nama ?: $event->name;

        \Illuminate\Support\Facades\Notification::send($event->spvs, new EventNotification($event, 'cancelled'));
        \Illuminate\Support\Facades\Notification::send($event->sales, new EventNotification($event, 'cancelled'));

        foreach ($event->sales as $salesUser) {
            $calendarService->removeSalesEvent($event, $salesUser);
        }

        $event->spvs()->detach();
        $event->sales()->detach();

        $event->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Event '{$nama}' berhasil dibatalkan/dihapus!",
            ]);
        }

        return redirect()->route('calendar.index')->with('success', "Event '{$nama}' berhasil dibatalkan/dihapus!");
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
