<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\Request;

class TimController extends Controller
{
    public function index()
    {
        // Supervisors
        $supervisors = User::where('role', 'SPV')->with('wilayah')->get();
        // Sales
        $sales = User::where('role', 'Sales')->with('wilayah.parent')->get();

        $kota = Wilayah::where('level', 'Kota/Kabupaten')->where('status', 'Aktif')->get();
        $kecamatan = Wilayah::where('level', 'Kecamatan')->where('status', 'Aktif')->with('parent')->get();

        // Build Tim Tree for display
        $tims = User::where('role', 'SPV')->with(['wilayah', 'subordinates.wilayah'])->get();

        return view('tim.index', [
            'supervisors' => $supervisors,
            'salesList' => $sales,
            'kota' => $kota,
            'kecamatan' => $kecamatan,
            'tims' => $tims,
            'currentUser' => ['role' => session('user_role', 'Head Marketing'), 'name' => session('user_name', 'Budi Santoso')]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supervisor_id' => 'required|exists:users,id',
            'kota_id' => 'required|exists:wilayahs,id',
            'kecamatan_id' => 'required|exists:wilayahs,id',
            'sales_id' => 'required|exists:users,id',
        ]);

        $supervisor = User::findOrFail($request->supervisor_id);
        $kota = Wilayah::findOrFail($request->kota_id);
        $kecamatan = Wilayah::findOrFail($request->kecamatan_id);
        $sales = User::findOrFail($request->sales_id);

        if ($supervisor->role !== 'SPV' || $sales->role !== 'Sales') {
            return back()->withErrors(['message' => 'Role tidak sesuai untuk assignment ini.']);
        }

        if ($supervisor->wilayah_id && $supervisor->wilayah_id !== $kota->id) {
            return back()->withErrors(['message' => 'Supervisor ini sudah ditugaskan ke Kota/Kabupaten lain.']);
        }

        if ($kecamatan->parent_id !== $kota->id) {
            return back()->withErrors(['message' => 'Kecamatan bukan merupakan bagian dari Kota/Kabupaten yang dipilih.']);
        }

        // Check 1 Kecamatan = 1 Sales rule
        $existingSales = User::where('role', 'Sales')
            ->where('wilayah_id', $kecamatan->id)
            ->where('id', '!=', $sales->id)
            ->first();
        
        $assignmentCode = $kota->kode . $kecamatan->kode;
        
        if ($existingSales) {
            return back()->withErrors(['message' => "Assignment gagal. Kecamatan {$kecamatan->nama} ({$assignmentCode}) sudah memiliki Sales."]);
        }

        // Update assignments
        $supervisor->update(['wilayah_id' => $kota->id]);
        $sales->update(['wilayah_id' => $kecamatan->id, 'supervisor_id' => $supervisor->id]);

        return redirect()->route('tim.index')->with('success', 'Assignment tim berhasil disimpan.');
    }
}
