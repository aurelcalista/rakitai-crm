<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttendanceLocationController extends Controller
{
    /**
     * Display master locations list.
     */
    public function index(Request $request)
    {
        Gate::authorize('create', AttendanceLocation::class);

        $query = AttendanceLocation::withCount('attendances');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $locations = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.attendance-locations.index', compact('locations'));
    }

    /**
     * Store new location.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', AttendanceLocation::class);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'required|integer|min:1|max:50000', // Meter
            'status'    => 'required|in:active,inactive',
        ], [
            'name.required'      => 'Nama lokasi wajib diisi.',
            'latitude.required'  => 'Latitude wajib diisi (-90 sampai 90).',
            'longitude.required' => 'Longitude wajib diisi (-180 sampai 180).',
            'radius.required'    => 'Radius wajib diisi dalam satuan meter.',
            'radius.min'         => 'Radius minimal 1 meter.',
        ]);

        AttendanceLocation::create([
            'name'      => $validated['name'],
            'latitude'  => round((float) $validated['latitude'], 6),
            'longitude' => round((float) $validated['longitude'], 6),
            'radius'    => (int) $validated['radius'],
            'status'    => $validated['status'],
        ]);

        return redirect()->route('admin.attendance-locations.index')
            ->with('success', 'Titik lokasi absensi berhasil ditambahkan.');
    }

    /**
     * Update existing location.
     */
    public function update(Request $request, AttendanceLocation $location)
    {
        Gate::authorize('update', $location);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'required|integer|min:1|max:50000',
            'status'    => 'required|in:active,inactive',
        ]);

        $location->update([
            'name'      => $validated['name'],
            'latitude'  => round((float) $validated['latitude'], 6),
            'longitude' => round((float) $validated['longitude'], 6),
            'radius'    => (int) $validated['radius'],
            'status'    => $validated['status'],
        ]);

        return redirect()->route('admin.attendance-locations.index')
            ->with('success', 'Titik lokasi absensi berhasil diperbarui.');
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(AttendanceLocation $location)
    {
        Gate::authorize('update', $location);

        $newStatus = $location->status === 'active' ? 'inactive' : 'active';
        $location->update(['status' => $newStatus]);

        return back()->with('success', "Status lokasi {$location->name} diubah menjadi {$newStatus}.");
    }

    /**
     * Delete location safely (soft delete).
     */
    public function destroy(AttendanceLocation $location)
    {
        Gate::authorize('delete', $location);

        $location->delete();

        return redirect()->route('admin.attendance-locations.index')
            ->with('success', 'Lokasi absensi berhasil dinonaktifkan/dihapus.');
    }
}
