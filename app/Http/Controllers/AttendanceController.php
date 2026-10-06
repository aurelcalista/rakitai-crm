<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['SPV', 'HM', 'Admin'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $query = \App\Models\Attendance::with(['user.wilayah', 'wilayah']);

        if ($user->role === 'SPV') {
            $teamIds = $user->teamMemberIds();
            $query->whereIn('user_id', $teamIds);
        } elseif ($user->role === 'HM') {
            $hmIds = $user->hmMemberIds();
            $subordinates = $user->subordinates()->pluck('id')->toArray();
            $allHmIds = array_unique(array_merge($hmIds, $subordinates));
            
            $extraUsers = \App\Models\User::whereIn('role', ['CS', 'EO'])->get()->filter(function($u) use ($user) {
                $hm = $u->getAssignedHm();
                return $hm && $hm->id === $user->id;
            })->pluck('id')->toArray();
            
            $finalIds = array_unique(array_merge($allHmIds, $extraUsers));
            $query->whereIn('user_id', $finalIds);
        }

        if ($request->filled('tipe_waktu')) {
            $tipe = $request->tipe_waktu;
            if ($tipe === 'harian' && $request->filled('tanggal')) {
                $query->whereDate('date', $request->tanggal);
            } elseif ($tipe === 'mingguan' && $request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('date', [$request->start_date, $request->end_date]);
            } elseif ($tipe === 'bulanan' && $request->filled('bulan') && $request->filled('tahun')) {
                $query->whereMonth('date', $request->bulan)->whereYear('date', $request->tahun);
            } elseif ($tipe === 'tahunan' && $request->filled('tahun_only')) {
                $query->whereYear('date', $request->tahun_only);
            }
        }

        if ($request->filled('search_nama')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search_nama . '%');
            });
        }

        if ($request->filled('filter_role')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('role', $request->filter_role);
            });
        }

        if ($request->filled('filter_spv') && in_array($user->role, ['Admin', 'HM'])) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('supervisor_id', $request->filter_spv);
            });
        }

        if ($request->filled('filter_hm') && $user->role === 'Admin') {
            $hm = \App\Models\User::find($request->filter_hm);
            if ($hm && $hm->role === 'HM') {
                $hmIds = $hm->hmMemberIds();
                $subordinates = $hm->subordinates()->pluck('id')->toArray();
                $allHmIds = array_unique(array_merge($hmIds, $subordinates));
                
                $extraUsers = \App\Models\User::whereIn('role', ['CS', 'EO'])->get()->filter(function($u) use ($hm) {
                    $uHm = $u->getAssignedHm();
                    return $uHm && $uHm->id === $hm->id;
                })->pluck('id')->toArray();
                
                $finalIds = array_unique(array_merge($allHmIds, $extraUsers));
                $query->whereIn('user_id', $finalIds);
            }
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('wilayah_id')) {
            $selWil = \App\Models\Wilayah::find($request->wilayah_id);
            if ($selWil) {
                $descIds = $selWil->getDescendantIds();
                $descIds[] = $selWil->id;
                $query->whereIn('wilayah_id', $descIds);
            }
        }

        // Summary Aggregation
        $summary = [
            'total' => (clone $query)->count(),
            'hadir' => (clone $query)->where('status', 'Hadir')->count(),
            'izin' => (clone $query)->where('status', 'Izin')->count(),
            'sakit' => (clone $query)->where('status', 'Sakit')->count(),
            'tidak_hadir' => (clone $query)->where('status', 'Tidak Hadir')->count(),
        ];

        // Rekap Per Orang
        $rekapRaw = (clone $query)
            ->select('user_id', 'status', \Illuminate\Support\Facades\DB::raw('count(*) as aggregate'))
            ->groupBy('user_id', 'status')
            ->with('user')
            ->get();

        $rekapPerOrang = [];
        foreach ($rekapRaw as $row) {
            $userId = $row->user_id;
            if (!isset($rekapPerOrang[$userId])) {
                $rekapPerOrang[$userId] = [
                    'user' => $row->user,
                    'Hadir' => 0,
                    'Izin' => 0,
                    'Sakit' => 0,
                    'Tidak Hadir' => 0,
                    'Total' => 0,
                ];
            }
            if (isset($rekapPerOrang[$userId][$row->status])) {
                $rekapPerOrang[$userId][$row->status] += $row->aggregate;
            }
            $rekapPerOrang[$userId]['Total'] += $row->aggregate;
        }

        // Sort rekap by name
        usort($rekapPerOrang, function($a, $b) {
            return strcmp($a['user']->name ?? '', $b['user']->name ?? '');
        });

        if ($request->has('print')) {
            $attendances = $query->latest('date')->latest('time')->get();
            return view('attendance.print', compact('attendances', 'summary', 'rekapPerOrang'));
        }

        $attendances = $query->latest('date')->latest('time')->paginate(20)->withQueryString();
        
        $wilayahs = collect();
        if (in_array($user->role, ['HM', 'Admin'])) {
            $wilayahs = \App\Models\Wilayah::where('level', 'Kota/Kabupaten')->orderBy('nama')->get();
        } elseif ($user->role === 'SPV') {
            $spvWilayahIds = $user->activeWilayahIds();
            $descendants = [];
            foreach ($spvWilayahIds as $wid) {
                $wil = \App\Models\Wilayah::find($wid);
                if ($wil) {
                    $descendants = array_merge($descendants, $wil->getDescendantIds());
                }
            }
            if (!empty($descendants)) {
                $wilayahs = \App\Models\Wilayah::whereIn('id', $descendants)
                    ->where('level', 'Kecamatan')
                    ->orderBy('nama')
                    ->get();
            }
        }

        $hms = in_array($user->role, ['Admin']) ? \App\Models\User::where('role', 'HM')->where('status', 'Aktif')->get() : collect();
        $spvs = in_array($user->role, ['Admin', 'HM']) ? \App\Models\User::where('role', 'SPV')->where('status', 'Aktif')->get() : collect();

        return view('attendance.index', compact('attendances', 'wilayahs', 'summary', 'rekapPerOrang', 'hms', 'spvs'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!in_array($user->role, ['Sales', 'CS', 'EO', 'SPV'])) {
            abort(403, 'Role Anda tidak memiliki fitur input absensi.');
        }

        $today = \Carbon\Carbon::now()->toDateString();
        
        $hasAttended = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->exists();

        $wilayahs = collect();

        return view('attendance.create', compact('hasAttended', 'today', 'wilayahs'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['Sales', 'CS', 'EO', 'SPV'])) {
            abort(403, 'Role Anda tidak memiliki fitur input absensi.');
        }

        $now = \Carbon\Carbon::now();
        $today = $now->toDateString();
        
        if ($now->format('H:i:s') > '23:59:59') {
            return back()->with('error', 'Waktu absensi hari ini telah berakhir.');
        }

        $hasAttended = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->exists();

        if ($hasAttended) {
            return back()->with('error', 'Anda sudah melakukan absensi hari ini.');
        }

        $rules = [
            'status' => 'required|in:Hadir,Izin,Sakit',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'notes' => 'nullable|max:300',
        ];

        if (in_array($request->status, ['Izin', 'Sakit'])) {
            $rules['notes'] = 'required|max:300';
        }

        $request->validate($rules);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendances', 'public');
        }

        $wilayahId = null;
        if (in_array($user->role, ['Sales', 'SPV'])) {
            $wilayahId = $user->wilayah_id;
        }

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time' => $now->toTimeString(),
            'status' => $request->status,
            'photo' => $photoPath,
            'notes' => $request->notes,
            'wilayah_id' => $wilayahId,
        ]);

        return redirect()->route('attendance.create')->with('success', 'Absensi berhasil disimpan.');
    }

    public function show($id)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['SPV', 'HM', 'Admin'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $attendance = \App\Models\Attendance::with(['user.wilayah', 'wilayah'])->findOrFail($id);

        if ($user->role === 'SPV') {
            $teamIds = $user->teamMemberIds();
            if (!in_array($attendance->user_id, $teamIds)) {
                abort(403, 'Anda tidak memiliki akses ke data ini.');
            }
        } elseif ($user->role === 'HM') {
            $hmIds = $user->hmMemberIds();
            $subordinates = $user->subordinates()->pluck('id')->toArray();
            $allHmIds = array_unique(array_merge($hmIds, $subordinates));
            
            if (!in_array($attendance->user_id, $allHmIds)) {
                $targetUser = $attendance->user;
                if (in_array($targetUser->role, ['CS', 'EO'])) {
                    $assignedHm = $targetUser->getAssignedHm();
                    if (!$assignedHm || $assignedHm->id !== $user->id) {
                        abort(403, 'Anda tidak memiliki akses ke data ini.');
                    }
                } else {
                    abort(403, 'Anda tidak memiliki akses ke data ini.');
                }
            }
        }

        return view('attendance.show', compact('attendance'));
    }
}
