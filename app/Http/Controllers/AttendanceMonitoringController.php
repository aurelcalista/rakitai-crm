<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttendanceMonitoringController extends Controller
{
    /**
     * Display monitoring list for SPV, HM, Admin.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!in_array(strtolower($user->role), ['admin', 'hm', 'spv'])) {
            abort(403, 'Akses monitoring absensi hanya untuk SPV, HM, dan Admin.');
        }

        $query = Attendance::with(['user', 'location']);

        // 1. Role hierarchy scope
        $role = strtolower($user->role);
        $monitoredIds = $user->getAttendanceMonitoredUserIds();

        if ($role !== 'admin') {
            $query->whereIn('user_id', $monitoredIds);
        }
        // Admin has global scope over all users

        // 2. Filters
        if ($request->filled('date')) {
            $query->whereDate('check_in_at', Carbon::parse($request->date));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('role')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        if ($request->filled('location_id')) {
            $query->where('attendance_location_id', $request->location_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderByDesc('check_in_at')->paginate(10)->withQueryString();

        // Candidates for user filter dropdown
        $monitoredUsersQuery = User::whereIn('role', ['SPV', 'Sales', 'EO'])->orderBy('name');
        if ($role !== 'admin') {
            $monitoredUsersQuery->whereIn('id', $monitoredIds);
        }
        $monitoredUsers = $monitoredUsersQuery->get();

        $locations = AttendanceLocation::orderBy('name')->get();

        // Attendance Percentage & Metric Calculations
        $attendanceService = app(\App\Services\AttendanceService::class);
        $totalRecords = (clone $query)->count();
        $totalPresent = (clone $query)->where('status', 'present')->count();
        $totalRejected = (clone $query)->where('status', 'rejected')->count();
        $validRate = $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0;

        $targetDate = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today('Asia/Jakarta');
        $eligibleUserIds = $monitoredUsers->pluck('id')->toArray();
        $totalPersonil = count($eligibleUserIds);

        $presentTodayCount = Attendance::whereIn('user_id', $eligibleUserIds)
            ->whereDate('check_in_at', $targetDate)
            ->where('status', 'present')
            ->distinct('user_id')
            ->count('user_id');

        $attendanceRateToday = $totalPersonil > 0 ? round(($presentTodayCount / $totalPersonil) * 100, 1) : 0;

        // Attach monthly attendance stats per user
        $userStatsMap = [];
        foreach ($monitoredUsers as $u) {
            $stats = $attendanceService->getUserAttendanceStats($u->id);
            $userStatsMap[$u->id] = $stats;
            $u->attendance_percentage = $stats['percentage'];
            $u->attendance_present_days = $stats['present_days'];
            $u->attendance_work_days = $stats['work_days'];
        }

        $monitoringStats = [
            'total_records'          => $totalRecords,
            'total_present'          => $totalPresent,
            'total_rejected'         => $totalRejected,
            'valid_rate'             => $validRate,
            'total_personil'         => $totalPersonil,
            'present_today_count'    => $presentTodayCount,
            'attendance_rate_today'  => $attendanceRateToday,
            'target_date_label'      => $targetDate->translatedFormat('d M Y'),
        ];

        return view('attendance.monitoring', compact('attendances', 'monitoredUsers', 'locations', 'monitoringStats', 'userStatsMap'));
    }

    /**
     * Show single attendance detail.
     */
    public function show(Attendance $attendance)
    {
        Gate::authorize('view', $attendance);

        $attendance->load(['user', 'location']);

        return response()->json([
            'id'           => $attendance->id,
            'user_name'    => $attendance->user?->name,
            'user_role'    => $attendance->user?->role,
            'location'     => $attendance->location?->name,
            'check_in_at'  => $attendance->check_in_at->format('d M Y H:i:s'),
            'latitude'     => $attendance->latitude,
            'longitude'    => $attendance->longitude,
            'distance'     => $attendance->distance,
            'status'       => $attendance->status,
            'notes'        => $attendance->notes,
            'photo_url'    => route('attendance.photo', $attendance->id),
        ]);
    }
}
