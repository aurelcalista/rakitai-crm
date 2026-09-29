<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Prospek;
use App\Http\Requests\StoreProspectRequest;
use Illuminate\Support\Facades\Gate;

class ProspekController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Prospek::with(['sales', 'cs']);

        $role = strtolower($user->role);

        // Role-based Isolation & Scoping
        if ($role === 'sales') {
            $query->where(function ($q) use ($user) {
                $q->where('sales_id', $user->id)
                  ->orWhere('owner_id', $user->id);
            });
        } elseif ($role === 'cs') {
            $query->where('cs_id', $user->id);
        } elseif ($role === 'spv') {
            $teamIds = $user->teamMemberIds();
            $query->where(function ($q) use ($teamIds) {
                $q->whereIn('sales_id', $teamIds)
                  ->orWhereIn('owner_id', $teamIds)
                  ->orWhereIn('cs_id', $teamIds);
            });
        } elseif ($role === 'hm' && $user->wilayah_id) {
            $hmIds = $user->hmMemberIds();
            $query->where(function ($q) use ($user, $hmIds) {
                $q->where('wilayah_id', $user->wilayah_id)
                  ->orWhereIn('sales_id', $hmIds)
                  ->orWhereIn('owner_id', $hmIds);
            });
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
        Gate::authorize('view', $prospek);

        return response()->json([
            'data' => $prospek->load(['followUps', 'kunjungans', 'timelines'])
        ]);
    }

    public function store(StoreProspectRequest $request)
    {
        $user = $request->user();
        
        $validated = $request->validated();

        $validated['owner_id'] = $user->id;
        $validated['sales_id'] = strtolower($user->role) === 'sales' ? $user->id : null;
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
        Gate::authorize('updateStatus', $prospek);

        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        if ($validated['status'] === 'LUNAS' && !\App\Services\ProspekService::isClosingValid($prospek)) {
            return response()->json(['message' => 'Status LUNAS tidak valid. Prospek harus melunasi Pembayaran Formulir dan Termin 1.'], 422);
        }

        $oldStatus = $prospek->status;
        $prospek->status = $validated['status'];
        $stages = [
            'BARU'        => 1,
            'KONTAK'      => 2,
            'PROSPEK'     => 3,
            'HANGAT'      => 3,
            'HOT PROSPEK' => 4,
            'PANAS'       => 4,
            'FORMULIR'    => 5,
            'BERKAS'      => 6,
            'LUNAS'       => 7,
            'NO RESPON'   => 8,
            'DINGIN'      => 8,
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
