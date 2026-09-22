<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * View the internal calendar.
     */
    public function index()
    {
        $user = Auth::user();
        $pageTitle = 'Kalender Internal';
        $currentUser = [
            'name' => $user->name,
            'role' => $user->role,
            'role_label' => strtoupper($user->role),
            'avatar' => strtoupper(substr($user->name, 0, 1)),
        ];

        $role = strtolower($user->role);
        $query = Event::query();

        if ($role === 'eo') {
            $query->where('eo_id', $user->id);
        } elseif (in_array($role, ['spv', 'supervisor', 'supervisor marketing'])) {
            $query->whereHas('spvs', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($role === 'sales') {
            $query->whereHas('sales', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif (!in_array($role, ['hm', 'head marketing', 'admin'])) {
            $query->where('id', -1);
        }

        $events = $query->orderBy('tanggal_mulai', 'asc')->get();

        return view('calendar.index', compact('pageTitle', 'currentUser', 'events'));
    }

    /**
     * API endpoint to fetch events dynamically based on user role.
     */
    public function fetchEvents(Request $request)
    {
        $user = Auth::user();
        $role = strtolower($user->role);

        $query = Event::with(['type', 'eo', 'spvs', 'sales']);

        if ($role === 'eo') {
            $query->where('eo_id', $user->id);
        } elseif (in_array($role, ['spv', 'supervisor', 'supervisor marketing'])) {
            // Events assigned to SPV
            $query->whereHas('spvs', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($role === 'sales') {
            // Events assigned to Sales
            $query->whereHas('sales', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif (in_array($role, ['hm', 'head marketing', 'admin'])) {
            // HM / Admin can see all events
            // No filter applied
        } else {
            // Others see none for now
            $query->where('id', -1);
        }

        $events = $query->get()->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->name,
                'start' => $event->tanggal->format('Y-m-d') . 'T' . $event->waktu_mulai->format('H:i:s'),
                'end' => $event->tanggal->format('Y-m-d') . 'T' . $event->waktu_selesai->format('H:i:s'),
                'tanggal' => $event->tanggal->format('Y-m-d'),
                'waktu_mulai' => $event->waktu_mulai->format('H:i'),
                'waktu_selesai' => $event->waktu_selesai->format('H:i'),
                'lokasi' => $event->lokasi,
                'deskripsi' => $event->deskripsi,
                'jenis' => $event->type ? $event->type->nama : '-',
                'eo_name' => $event->eo ? $event->eo->name : '-',
                'status' => $event->status,
                'spvs' => $event->spvs->map(function ($spv) {
                    return ['id' => $spv->id, 'name' => $spv->name];
                }),
                'sales' => $event->sales->map(function ($sales) {
                    return [
                        'id' => $sales->id,
                        'name' => $sales->name,
                        'assigned_by_spv_id' => $sales->pivot->assigned_by_spv_id,
                    ];
                }),
            ];
        });

        return response()->json($events);
    }
}
