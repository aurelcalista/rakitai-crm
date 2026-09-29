<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Display attendance check-in page.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!in_array(strtolower($user->role), ['sales', 'spv', 'eo'])) {
            return redirect()->route('dashboard')->with('error', 'Role Anda tidak memiliki akses untuk melakukan absensi.');
        }

        $activeLocations = AttendanceLocation::active()->orderBy('name')->get();
        $hasCheckedInToday = $this->attendanceService->hasCheckedInToday($user->id);
        $todayAttendance = null;
        if ($hasCheckedInToday) {
            $todayAttendance = Attendance::with('location')
                ->where('user_id', $user->id)
                ->whereDate('check_in_at', Carbon::today())
                ->where('status', 'present')
                ->latest('id')
                ->first();
        }

        $userStats = $this->attendanceService->getUserAttendanceStats($user->id);

        return view('attendance.index', compact('activeLocations', 'hasCheckedInToday', 'todayAttendance', 'userStats'));
    }

    /**
     * Submit attendance check-in.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!in_array(strtolower($user->role), ['sales', 'spv', 'eo'])) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'attendance_location_id' => 'required|exists:attendance_locations,id',
            'latitude'               => 'required|numeric|between:-90,90',
            'longitude'              => 'required|numeric|between:-180,180',
            'foto'                   => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // Maks 5MB awal
            'notes'                  => 'nullable|string|max:500',
        ], [
            'attendance_location_id.required' => 'Silakan pilih lokasi absensi.',
            'latitude.required'               => 'Koordinat GPS wajib terdeteksi.',
            'longitude.required'              => 'Koordinat GPS wajib terdeteksi.',
            'foto.required'                   => 'Bukti foto selfie wajib disertakan.',
            'foto.image'                      => 'File harus berupa gambar.',
            'foto.mimes'                      => 'Format foto harus berupa JPG, PNG, atau WebP.',
            'foto.max'                        => 'Ukuran foto maksimal 5 MB.',
        ]);

        $attendance = $this->attendanceService->checkIn(
            user: $user,
            locationId: (int) $validated['attendance_location_id'],
            userLat: (float) $validated['latitude'],
            userLng: (float) $validated['longitude'],
            photoFile: $request->file('foto'),
            notes: $validated['notes'] ?? null
        );

        $locName = $attendance->location?->name ?? 'Titik Absensi';
        $dist = number_format($attendance->distance, 2);
        $time = $attendance->check_in_at->format('H:i');

        return redirect()->route('attendance.index')->with(
            'success',
            "Absensi berhasil di {$locName}. Jarak: {$dist} m pada pukul {$time} WIB."
        );
    }

    /**
     * User's own attendance history.
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $query = Attendance::with('location')->where('user_id', $user->id);

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $query->whereYear('check_in_at', $month->year)
                  ->whereMonth('check_in_at', $month->month);
        }

        if ($request->filled('location_id')) {
            $query->where('attendance_location_id', $request->location_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderByDesc('check_in_at')->paginate(10)->withQueryString();
        $locations = AttendanceLocation::orderBy('name')->get();

        $filterMonth = $request->filled('month') ? Carbon::parse($request->month) : Carbon::now('Asia/Jakarta');
        $userStats = $this->attendanceService->getUserAttendanceStats($user->id, $filterMonth->month, $filterMonth->year);

        return view('attendance.history', compact('attendances', 'locations', 'userStats'));
    }

    /**
     * Securely serve private selfie photo.
     */
    public function photo(Attendance $attendance): BinaryFileResponse
    {
        Gate::authorize('viewPhoto', $attendance);

        if (!Storage::disk('local')->exists($attendance->selfie_path)) {
            abort(404, 'Foto absensi tidak ditemukan.');
        }

        $path = Storage::disk('local')->path($attendance->selfie_path);
        return response()->file($path, [
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
