<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\EventAssignmentService;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventAssignmentController extends Controller
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
        $pageTitle = 'Assignment Event';
        $currentUser = [
            'name' => $user->name,
            'role' => $user->role,
            'role_label' => strtoupper($user->role),
            'avatar' => strtoupper(substr($user->name, 0, 1)),
        ];

        // Events assigned to this SPV (Load all team Sales assigned to these events)
        $teamMemberIds = $user->teamMemberIds();
        $events = Event::with(['type', 'sales' => function ($q) use ($teamMemberIds) {
            $q->whereIn('users.id', $teamMemberIds);
        }])
        ->whereHas('spvs', function($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->orderBy('tanggal_mulai', 'desc')
        ->paginate(25);

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
            'sales.*' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'Sales');
                }),
            ],
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

            $teamMemberIds = $user->teamMemberIds();
            $oldSalesIds = $event->sales()->whereIn('users.id', $teamMemberIds)->pluck('users.id')->toArray();
            
            // Validate schedule for new additions
            $newSalesIds = array_diff($selectedSalesIds, $oldSalesIds);
            
            foreach ($newSalesIds as $salesId) {
                $this->assignmentService->validateSalesSchedule(
                    $salesId, 
                    $event->tanggal ? $event->tanggal->format('Y-m-d') : \Carbon\Carbon::parse($event->tanggal_mulai)->format('Y-m-d'), 
                    $event->waktu_mulai ? $event->waktu_mulai->format('H:i') : \Carbon\Carbon::parse($event->tanggal_mulai)->format('H:i'), 
                    $event->waktu_selesai ? $event->waktu_selesai->format('H:i') : \Carbon\Carbon::parse($event->tanggal_selesai)->format('H:i')
                );
            }

            // Detach removed
            $removedSalesIds = array_diff($oldSalesIds, $selectedSalesIds);
            if (!empty($removedSalesIds)) {
                foreach ($removedSalesIds as $rSalesId) {
                    $rSalesUser = User::find($rSalesId);
                    if ($rSalesUser) {
                        $this->calendarService->removeSalesEvent($event, $rSalesUser);
                    }
                }
                $event->sales()->detach($removedSalesIds);

                $removedUsers = User::whereIn('id', $removedSalesIds)->get();
                \Illuminate\Support\Facades\Notification::send($removedUsers, new \App\Notifications\EventNotification($event, 'assignment_removed'));
            }

            // Attach new
            if (!empty($newSalesIds)) {
                foreach ($newSalesIds as $salesId) {
                    $event->sales()->attach($salesId, ['assigned_by_spv_id' => $user->id]);
                    $nSalesUser = User::find($salesId);
                    if ($nSalesUser) {
                        $this->calendarService->syncSalesEvent($event, $nSalesUser);
                    }
                }
                $newUsers = User::whereIn('id', $newSalesIds)->get();
                \Illuminate\Support\Facades\Notification::send($newUsers, new \App\Notifications\EventNotification($event, 'assigned_sales'));
            }

            DB::commit();
            
            return back()->with('success', 'Assignment Sales berhasil diperbarui.');

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->with('error', 'Gagal menugaskan Sales. Terdapat jadwal yang tidak memenuhi syarat.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan basis data.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}
