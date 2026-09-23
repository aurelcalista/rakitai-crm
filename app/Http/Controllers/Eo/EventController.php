<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\MasterData;
use App\Models\User;
use App\Models\Prodi;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\EventNotification;
use App\Services\EventAssignmentService;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    protected EventAssignmentService $assignmentService;
    protected GoogleCalendarService $calendarService;

    public function __construct(EventAssignmentService $assignmentService, GoogleCalendarService $calendarService)
    {
        $this->assignmentService = $assignmentService;
        $this->calendarService = $calendarService;
    }

    public function index()
    {
        $user = Auth::user();
        $pageTitle = 'Kelola Event';
        $currentUser = [
            'name' => $user->name,
            'role' => $user->role,
            'role_label' => strtoupper($user->role),
            'avatar' => strtoupper(substr($user->name, 0, 1)),
        ];

        $events = Event::with(['type', 'spvs', 'sales', 'dosen', 'prodi', 'sekolah', 'perusahaan'])
            ->where('eo_id', $user->id)
            ->orderBy('tanggal_mulai', 'desc')
            ->paginate(10);

        $eventTypes  = MasterData::where('type', 'jenis_event')->where('status', 'Aktif')->get();
        $spvs        = User::whereIn('role', ['SPV', 'Supervisor Marketing', 'Supervisor'])->where('status', 'Aktif')->get();
        $dosens      = User::whereIn('role', ['Dosen', 'Staff', 'Admin', 'SPV'])->where('status', 'Aktif')->get();
        $prodis      = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $sekolahs    = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $salesList   = User::where('role', 'Sales')->where('status', 'Aktif')->orderBy('name')->get();

        return view('eo.events.index', compact('pageTitle', 'currentUser', 'events', 'eventTypes', 'spvs', 'dosens', 'prodis', 'sekolahs', 'perusahaans', 'salesList'));
    }

    public function show($id)
    {
        $event = Event::with(['type', 'spvs', 'sales', 'dosen', 'prodi', 'sekolah', 'perusahaan'])
            ->where('eo_id', Auth::id())
            ->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json($event);
        }

        return redirect()->route('eo.events.index');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Event::class);

        $request->validate([
            'name'           => 'required|string|max:255',
            'type_id'        => 'required|exists:master_data,id',
            'tanggal'        => 'required|date',
            'waktu_mulai'    => 'required|date_format:H:i',
            'waktu_selesai'  => 'required|date_format:H:i|after:waktu_mulai',
            'lokasi'         => 'required|string|max:255',
            'spvs'           => 'required|array|min:1',
            'spvs.*'         => 'exists:users,id',
            'sales'          => 'nullable|array',
            'sales.*'        => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'Sales');
                }),
            ],
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
            'pic_whatsapp'   => 'nullable|string|max:20',
        ], [
            'waktu_selesai.after' => 'Waktu selesai harus setelah waktu mulai.',
            'spvs.required'       => 'Minimal satu SPV harus dipilih.',
            'sales.*.exists'      => 'User yang ditugaskan harus ber-role Sales.',
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

        DB::transaction(function () use ($request) {
            $event = Event::create([
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
                'eo_id'           => Auth::id(),
                'status'          => 'Scheduled',
                'dosen_id'        => $request->dosen_id,
                'dosen_pemateri'  => $request->dosen_pemateri,
                'prodi_id'        => $request->prodi_id,
                'sekolah_id'      => $request->sekolah_id,
                'perusahaan_id'   => $request->perusahaan_id,
                'jenis_institusi' => $request->jenis_institusi,
                'nama_institusi'  => $request->nama_institusi,
                'alamat'          => $request->alamat,
                'pic_name'        => $request->pic_name,
                'pic_whatsapp'    => $request->pic_whatsapp,
                'qr_code'         => Event::generateUniqueQrToken(),
            ]);

            $event->spvs()->sync($request->spvs);

            if (!empty($request->sales)) {
                foreach ($request->sales as $salesId) {
                    $event->sales()->attach($salesId, ['assigned_by_spv_id' => Auth::id()]);
                    $salesUser = User::find($salesId);
                    if ($salesUser) {
                        $this->calendarService->syncSalesEvent($event, $salesUser);
                    }
                }
            }

            // Trigger notification to SPVs
            $spvUsers = User::whereIn('id', $request->spvs)->get();
            \Illuminate\Support\Facades\Notification::send($spvUsers, new EventNotification($event, 'assigned_spv'));
        });

        return back()->with('success', 'Event berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $event = Event::where('eo_id', Auth::id())->findOrFail($id);

        Gate::authorize('update', $event);

        $request->validate([
            'name'           => 'required|string|max:255',
            'type_id'        => 'required|exists:master_data,id',
            'tanggal'        => 'required|date',
            'waktu_mulai'    => 'required|date_format:H:i',
            'waktu_selesai'  => 'required|date_format:H:i|after:waktu_mulai',
            'lokasi'         => 'required|string|max:255',
            'spvs'           => 'required|array|min:1',
            'spvs.*'         => 'exists:users,id',
            'sales'          => 'nullable|array',
            'sales.*'        => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'Sales');
                }),
            ],
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
            'pic_whatsapp'   => 'nullable|string|max:20',
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

        DB::transaction(function () use ($request, $event) {
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
                'pic_whatsapp'    => $request->pic_whatsapp,
            ]);

            $oldSpvs = $event->spvs->pluck('id')->toArray();
            $event->spvs()->sync($request->spvs);
            
            // If SPV is removed, their sales assignments are removed.
            $removedSpvs = array_diff($oldSpvs, $request->spvs);
            if (!empty($removedSpvs)) {
                $removedSalesIds = DB::table('event_sales')
                                     ->where('event_id', $event->id)
                                     ->whereIn('assigned_by_spv_id', $removedSpvs)
                                     ->pluck('sales_id')->toArray();
                
                foreach ($removedSalesIds as $rSalesId) {
                    $rSalesUser = User::find($rSalesId);
                    if ($rSalesUser) {
                        $this->calendarService->removeSalesEvent($event, $rSalesUser);
                    }
                }

                if (!empty($removedSalesIds)) {
                    $removedSalesUsers = User::whereIn('id', $removedSalesIds)->get();
                    \Illuminate\Support\Facades\Notification::send($removedSalesUsers, new EventNotification($event, 'assignment_removed'));
                }
            }

            // Sync direct sales if passed in request
            if ($request->has('sales')) {
                $newSalesList = $request->sales ?? [];
                $currentSalesIds = $event->sales->pluck('id')->toArray();
                $toRemove = array_diff($currentSalesIds, $newSalesList);
                $toAdd = array_diff($newSalesList, $currentSalesIds);

                foreach ($toRemove as $remId) {
                    $remUser = User::find($remId);
                    if ($remUser) {
                        $this->calendarService->removeSalesEvent($event, $remUser);
                    }
                }

                foreach ($toAdd as $addId) {
                    $event->sales()->attach($addId, ['assigned_by_spv_id' => Auth::id()]);
                    $addUser = User::find($addId);
                    if ($addUser) {
                        $this->calendarService->syncSalesEvent($event, $addUser);
                    }
                }
            }

            // Sync Calendar for all assigned Sales
            $this->calendarService->syncAllAssignedSales($event);

            // Check if time changed, validate existing sales. If invalid, remove them.
            if ($isTimeChanged) {
                $currentSales = $event->sales()->get();
                $invalidSalesIds = [];
                
                foreach ($currentSales as $salesUser) {
                    try {
                        $this->assignmentService->validateSalesSchedule(
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
                            $this->calendarService->removeSalesEvent($event, $invUser);
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

        return back()->with('success', 'Event berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $event = Event::where('eo_id', Auth::id())->findOrFail($id);

        Gate::authorize('delete', $event);
        
        \Illuminate\Support\Facades\Notification::send($event->spvs, new EventNotification($event, 'cancelled'));
        \Illuminate\Support\Facades\Notification::send($event->sales, new EventNotification($event, 'cancelled'));
        
        foreach ($event->sales as $salesUser) {
            $this->calendarService->removeSalesEvent($event, $salesUser);
        }

        $event->delete();
        
        return back()->with('success', 'Event berhasil dibatalkan/dihapus.');
    }
}
