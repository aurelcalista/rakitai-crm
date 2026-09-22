<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
// use App\Notifications\EventNotification;

class EventAssignmentController extends Controller
{
    protected EventAssignmentService $assignmentService;

    public function __construct(EventAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function index()
    {
        $user = Auth::user();
        $pageTitle = 'Assignment Event';
        $currentUser = [
            'name' => $user->name,
            'role' => $user->role,
            'role_label' => strtoupper($user->role),
            'avatar' => strtoupper(substr($user->name, 0, 1)),
        ];

        // Events assigned to this SPV
        $events = Event::with(['type', 'sales' => function ($q) use ($user) {
            $q->where('assigned_by_spv_id', $user->id);
        }])
        ->whereHas('spvs', function($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->orderBy('tanggal', 'desc')
        ->paginate(10);

        // SPV's team Sales
        $teamSales = $user->teamSales()->get();

        return view('spv.events.index', compact('pageTitle', 'currentUser', 'events', 'teamSales'));
    }

    public function assignSales(Request $request, $eventId)
    {
        $user = Auth::user();
        
        // Ensure SPV is assigned to this event
        $event = Event::whereHas('spvs', function($q) use ($user) {
            $q->where('users.id', $user->id);
        })->findOrFail($eventId);

        $request->validate([
            'sales' => 'array',
            'sales.*' => 'exists:users,id',
        ]);

        $selectedSalesIds = $request->sales ?? [];

        // Validate that all selected sales belong to this SPV's team
        $teamMemberIds = $user->teamMemberIds();
        $invalidSales = array_diff($selectedSalesIds, $teamMemberIds);
        if (!empty($invalidSales)) {
            return back()->with('error', 'Anda hanya dapat menugaskan Sales dari tim Anda sendiri.');
        }

        try {
            DB::beginTransaction();

            $oldSalesIds = $event->sales()->wherePivot('assigned_by_spv_id', $user->id)->pluck('users.id')->toArray();
            
            // Validate schedule for new additions
            $newSalesIds = array_diff($selectedSalesIds, $oldSalesIds);
            foreach ($newSalesIds as $salesId) {
                $this->assignmentService->validateSalesSchedule(
                    $salesId, 
                    $event->tanggal->format('Y-m-d'), 
                    $event->waktu_mulai->format('H:i'), 
                    $event->waktu_selesai->format('H:i')
                );
            }

            // Sync sales assigned by this SPV
            // To sync only pivot records managed by this SPV without detaching others, we must do it manually
            // Detach removed
            $removedSalesIds = array_diff($oldSalesIds, $selectedSalesIds);
            if (!empty($removedSalesIds)) {
                $event->sales()->wherePivot('assigned_by_spv_id', $user->id)->detach($removedSalesIds);
                // Notify removed sales
                $removedUsers = \App\Models\User::whereIn('id', $removedSalesIds)->get();
                \Illuminate\Support\Facades\Notification::send($removedUsers, new \App\Notifications\EventNotification($event, 'assignment_removed'));
            }

            // Attach new
            if (!empty($newSalesIds)) {
                foreach ($newSalesIds as $salesId) {
                    $event->sales()->attach($salesId, ['assigned_by_spv_id' => $user->id]);
                }
                // Notify new sales
                $newUsers = \App\Models\User::whereIn('id', $newSalesIds)->get();
                \Illuminate\Support\Facades\Notification::send($newUsers, new \App\Notifications\EventNotification($event, 'assigned_sales'));
            }

            DB::commit();
            return back()->with('success', 'Assignment Sales berhasil diperbarui.');

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->with('error', 'Gagal menugaskan Sales. Terdapat jadwal yang tidak memenuhi syarat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}
