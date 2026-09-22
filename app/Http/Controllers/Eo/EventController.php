<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\MasterData;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\EventNotification;
use App\Services\EventAssignmentService;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    protected EventAssignmentService $assignmentService;

    public function __construct(EventAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
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

        $events = Event::with(['type', 'spvs', 'sales'])->where('eo_id', $user->id)->orderBy('tanggal', 'desc')->paginate(10);
        $eventTypes = MasterData::where('type', 'jenis_event')->where('status', 'Aktif')->get();
        $spvs = User::whereIn('role', ['SPV', 'Supervisor Marketing', 'Supervisor'])->where('status', 'Aktif')->get();

        return view('eo.events.index', compact('pageTitle', 'currentUser', 'events', 'eventTypes', 'spvs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type_id' => 'required|exists:master_data,id',
            'tanggal' => 'required|date',
            'waktu_mulai' => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'lokasi' => 'required|string|max:255',
            'spvs' => 'required|array|min:1',
            'spvs.*' => 'exists:users,id',
            'deskripsi' => 'nullable|string'
        ], [
            'waktu_selesai.after' => 'Waktu selesai harus setelah waktu mulai.',
            'spvs.required' => 'Minimal satu SPV harus dipilih.'
        ]);

        DB::transaction(function () use ($request) {
            $event = Event::create([
                'name' => $request->name,
                'type_id' => $request->type_id,
                'tanggal' => $request->tanggal,
                'waktu_mulai' => $request->waktu_mulai,
                'waktu_selesai' => $request->waktu_selesai,
                'lokasi' => $request->lokasi,
                'deskripsi' => $request->deskripsi,
                'eo_id' => Auth::id(),
                'status' => 'Scheduled'
            ]);

            $event->spvs()->sync($request->spvs);

            // Trigger notification to SPVs
            $spvUsers = User::whereIn('id', $request->spvs)->get();
            \Illuminate\Support\Facades\Notification::send($spvUsers, new EventNotification($event, 'assigned_spv'));
        });

        return back()->with('success', 'Event berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $event = Event::where('eo_id', Auth::id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type_id' => 'required|exists:master_data,id',
            'tanggal' => 'required|date',
            'waktu_mulai' => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'lokasi' => 'required|string|max:255',
            'spvs' => 'required|array|min:1',
            'spvs.*' => 'exists:users,id',
            'deskripsi' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request, $event) {
            $isTimeChanged = $event->tanggal->format('Y-m-d') !== $request->tanggal ||
                             $event->waktu_mulai->format('H:i') !== $request->waktu_mulai ||
                             $event->waktu_selesai->format('H:i') !== $request->waktu_selesai;

            $event->update([
                'name' => $request->name,
                'type_id' => $request->type_id,
                'tanggal' => $request->tanggal,
                'waktu_mulai' => $request->waktu_mulai,
                'waktu_selesai' => $request->waktu_selesai,
                'lokasi' => $request->lokasi,
                'deskripsi' => $request->deskripsi,
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
                
                $event->sales()->wherePivotIn('assigned_by_spv_id', $removedSpvs)->detach();
                
                if (!empty($removedSalesIds)) {
                    $removedSalesUsers = User::whereIn('id', $removedSalesIds)->get();
                    \Illuminate\Support\Facades\Notification::send($removedSalesUsers, new EventNotification($event, 'assignment_removed'));
                }
            }

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
                            $event->id // Exclude current event
                        );
                    } catch (ValidationException $e) {
                        $invalidSalesIds[] = $salesUser->id;
                    }
                }
                
                if (!empty($invalidSalesIds)) {
                    $event->sales()->detach($invalidSalesIds);
                    $invalidSalesUsers = User::whereIn('id', $invalidSalesIds)->get();
                    \Illuminate\Support\Facades\Notification::send($invalidSalesUsers, new EventNotification($event, 'assignment_removed'));
                    
                    // Notify SPVs about the removed sales
                    $affectedSpvIds = DB::table('event_sales')
                        ->where('event_id', $event->id) // Wait, we just detached them. So we should get their SPVs before detaching.
                        // Let's modify the logic above to capture their SPV IDs before detach, or just notify all SPVs.
                        // For simplicity, we just notify all current SPVs.
                        ->select('assigned_by_spv_id')->distinct()->pluck('assigned_by_spv_id')->toArray();
                    
                    \Illuminate\Support\Facades\Notification::send($event->spvs, new EventNotification($event, 'updated'));
                    // Provide flash message warning
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
        
        // Notify before delete
        \Illuminate\Support\Facades\Notification::send($event->spvs, new EventNotification($event, 'cancelled'));
        \Illuminate\Support\Facades\Notification::send($event->sales, new EventNotification($event, 'cancelled'));
        
        $event->delete();

        
        return back()->with('success', 'Event berhasil dibatalkan/dihapus.');
    }
}
