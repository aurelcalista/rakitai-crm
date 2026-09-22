<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Prospek;

class ProspekController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Prospek::with(['sales', 'cs']);

        // Isolation
        if ($user->role === 'Sales') {
            $query->where('sales_id', $user->id);
        } elseif ($user->role === 'CS') {
            $query->where('cs_id', $user->id);
        } elseif ($user->role === 'SPV' && $user->wilayah_id) {
            $query->where('wilayah_id', $user->wilayah_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->orderBy('updated_at', 'desc')->paginate(20)
        ]);
    }

    public function show(Request $request, Prospek $prospek)
    {
        $user = $request->user();
        if ($user->role === 'Sales' && $prospek->sales_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->role === 'CS' && $prospek->cs_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->role === 'SPV' && $user->wilayah_id && $prospek->wilayah_id !== $user->wilayah_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $prospek->load(['followUps', 'kunjungans', 'timelines'])
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Corporate,Sekolah,Individu',
            'whatsapp' => 'required|string|max:20',
            'wilayah_id' => 'required|exists:wilayahs,id',
            'source' => 'required|string',
        ]);

        $validated['owner_id'] = $user->id;
        $validated['sales_id'] = $user->role === 'Sales' ? $user->id : null;
        $validated['status'] = 'Baru';
        $validated['stage_number'] = 1;

        $prospek = Prospek::create($validated);

        return response()->json([
            'message' => 'Prospek berhasil dibuat',
            'data' => $prospek
        ], 201);
    }

    public function updateStatus(Request $request, Prospek $prospek)
    {
        $user = $request->user();
        if ($user->role === 'Sales' && $prospek->sales_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string', // Ensure mobile app sends correct new status
        ]);

        $oldStatus = $prospek->status;
        $prospek->status = $validated['status'];
        $stages = [
            'Baru' => 1, 'Follow Up' => 2, 'Kunjungan' => 3, 'Beli Formulir' => 4,
            'Pembayaran Termin 1' => 5, 'Pemberkasan' => 6, 'Wawancara' => 7, 'Closing (Lunas)' => 8
        ];
        if (isset($stages[$validated['status']])) {
            $prospek->stage_number = $stages[$validated['status']];
        }
        $prospek->save();

        \App\Models\ProspekTimeline::create([
            'prospek_id' => $prospek->id,
            'user_id' => $user->id,
            'title' => 'Update Status via API',
            'notes' => 'Status diubah ke ' . $validated['status'],
            'status_before' => $oldStatus,
            'status_after' => $prospek->status,
            'time' => now(),
        ]);

        return response()->json([
            'message' => 'Status berhasil diupdate',
            'data' => $prospek
        ]);
    }
}
