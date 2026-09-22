<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kunjungan;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Kunjungan::with('prospek');

        if ($user->role === 'Sales') {
            $query->where('sales_id', $user->id);
        } elseif ($user->role === 'SPV' && $user->wilayah_id) {
            $query->whereHas('prospek', function ($q) use ($user) {
                $q->where('wilayah_id', $user->wilayah_id);
            });
        }

        return response()->json([
            'data' => $query->orderBy('tanggal', 'desc')->paginate(20)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prospek_id' => 'required|exists:prospeks,id',
            'tanggal' => 'required|date',
            'tujuan' => 'required|string',
            'hasil' => 'required|string',
            'foto' => 'required|image|max:5120',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $validated['sales_id'] = $request->user()->id;
        $validated['status_lokasi'] = 'Valid'; // simplify for API
        $validated['jarak_meter'] = 0;

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('kunjungan', 'public');
        }

        $kunjungan = Kunjungan::create($validated);

        return response()->json([
            'message' => 'Kunjungan berhasil ditambahkan',
            'data' => $kunjungan
        ], 201);
    }
}
