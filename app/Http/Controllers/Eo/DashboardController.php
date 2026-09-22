<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $pageTitle = 'Dashboard EO';
        
        $currentUser = [
            'name' => $user->name,
            'role' => $user->role,
            'role_label' => strtoupper($user->role),
            'avatar' => strtoupper(substr($user->name, 0, 1)),
        ];

        $today = Carbon::today();

        // Query basis
        $baseQuery = Event::where('eo_id', $user->id);

        // Metrics
        $totalEvents = (clone $baseQuery)->count();
        $todayEventsCount = (clone $baseQuery)->whereDate('tanggal', $today)->count();
        $upcomingEventsCount = (clone $baseQuery)->whereDate('tanggal', '>', $today)->where('status', 'Scheduled')->count();
        $completedEventsCount = (clone $baseQuery)->where('status', 'Completed')->count();
        $cancelledEventsCount = (clone $baseQuery)->where('status', 'Cancelled')->count();

        // Upcoming events
        $upcomingEvents = (clone $baseQuery)
            ->with(['type', 'spvs', 'sales'])
            ->whereDate('tanggal', '>=', $today)
            ->where('status', 'Scheduled')
            ->orderBy('tanggal', 'asc')
            ->orderBy('waktu_mulai', 'asc')
            ->take(5)
            ->get();

        // Event type breakdown
        $eventsByType = (clone $baseQuery)
            ->selectRaw('type_id, count(*) as count')
            ->with('type')
            ->groupBy('type_id')
            ->get();

        return view('eo.dashboard', compact(
            'pageTitle', 
            'currentUser', 
            'totalEvents',
            'todayEventsCount',
            'upcomingEventsCount',
            'completedEventsCount',
            'cancelledEventsCount',
            'upcomingEvents',
            'eventsByType'
        ));
    }
}
