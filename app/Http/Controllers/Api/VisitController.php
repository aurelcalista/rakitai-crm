<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prospek;
use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    /**
     * List visits with role-based scoping.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Kunjungan::with(['sales', 'sekolah', 'perusahaan', 'prodi', 'dosen']);

        $role = strtolower($user->role);

        if ($role === 'sales') {
            $query->where('sales_id', $user->id);
        } elseif ($role === 'spv') {
            $teamIds = $user->teamMemberIds();
            $query->whereIn('sales_id', $teamIds);
        } elseif ($role === 'hm' && $user->wilayah_id) {
            $hmIds = $user->hmMemberIds();
            $query->whereIn('sales_id', $hmIds);
        }

        return response()->json([
            'data' => $query->orderBy('tanggal', 'desc')->paginate(20)
        ]);
    }

    /**
     * Store a new field visit with geo-validation, photo verification, Prodi, and Dosen Pemateri.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prodi_id'       => 'required|exists:prodis,id',
            'tanggal'        => 'required|date',
            'foto'           => 'required|image|max:5120',
            'lat'            => 'required|numeric',
            'lng'            => 'required|numeric',
            'prospek_id'     => 'nullable|exists:prospeks,id',
            'sekolah_id'     => 'nullable|exists:sekolahs,id',
            'perusahaan_id'  => 'nullable|exists:perusahaans,id',
            'jenis'          => 'nullable|in:Sekolah,Perusahaan',
            'tujuan'         => 'nullable|string',
            'hasil'          => 'nullable|string',
            'catatan'        => 'nullable|string',
            'pic_name'       => 'nullable|string|max:255',
            'pic_whatsapp'   => 'nullable|string|max:20',
            'dosen_id'       => 'nullable|exists:users,id',
            'dosen_pemateri' => 'nullable|string|max:255',
            'is_training'    => 'nullable|boolean',
            'kesediaan_training_ai' => 'nullable|boolean',
        ]);

        $isTraining = $request->boolean('is_training') || 
                      $request->boolean('kesediaan_training_ai') || 
                      (isset($validated['tujuan']) && stripos($validated['tujuan'], 'training') !== false);

        if ($isTraining && empty($validated['dosen_id']) && empty($validated['dosen_pemateri'])) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'dosen_id' => ['Dosen Pemateri wajib diisi untuk kunjungan / event Training.']
                ]
            ], 422);
        }

        $user = $request->user();

        // Determine destination target & coordinates
        $targetLat = null;
        $targetLng = null;
        $namaInstitusi = $validated['tujuan'] ?? 'Kunjungan';
        $jenis = $validated['jenis'] ?? 'Sekolah';
        $tujuanId = 0;
        $tier = null;
        $budget = null;

        if (!empty($validated['sekolah_id'])) {
            $sekolah = Sekolah::find($validated['sekolah_id']);
            if ($sekolah) {
                $targetLat = $sekolah->lat ? (float) $sekolah->lat : null;
                $targetLng = $sekolah->lng ? (float) $sekolah->lng : null;
                $namaInstitusi = $sekolah->nama;
                $jenis = 'Sekolah';
                $tujuanId = $sekolah->id;
                $tier = $sekolah->tier ?? 'B';
                $budget = $sekolah->max_budget;
            }
        } elseif (!empty($validated['perusahaan_id'])) {
            $perusahaan = Perusahaan::find($validated['perusahaan_id']);
            if ($perusahaan) {
                $targetLat = $perusahaan->lat ? (float) $perusahaan->lat : null;
                $targetLng = $perusahaan->lng ? (float) $perusahaan->lng : null;
                $namaInstitusi = $perusahaan->nama;
                $jenis = 'Perusahaan';
                $tujuanId = $perusahaan->id;
            }
        } elseif (!empty($validated['prospek_id'])) {
            $prospek = Prospek::find($validated['prospek_id']);
            if ($prospek) {
                $tujuanId = $prospek->id;
                $namaInstitusi = $prospek->name;
                $jenis = $prospek->type === 'Corporate' ? 'Perusahaan' : 'Sekolah';
                if ($prospek->sekolah) {
                    $targetLat = $prospek->sekolah->lat ? (float) $prospek->sekolah->lat : null;
                    $targetLng = $prospek->sekolah->lng ? (float) $prospek->sekolah->lng : null;
                    $tier = $prospek->sekolah->tier ?? 'B';
                    $budget = $prospek->sekolah->max_budget;
                } elseif ($prospek->perusahaan) {
                    $targetLat = $prospek->perusahaan->lat ? (float) $prospek->perusahaan->lat : null;
                    $targetLng = $prospek->perusahaan->lng ? (float) $prospek->perusahaan->lng : null;
                }
            }
        }

        // Validate Geolocation
        $geoResult = GeoLocationService::validateVisitLocation(
            (float) $validated['lat'],
            (float) $validated['lng'],
            $targetLat,
            $targetLng
        );

        // Update target baseline coords if newly established
        if ($geoResult['target_lat_updated']) {
            if (!empty($validated['sekolah_id']) && isset($sekolah)) {
                $sekolah->update(['lat' => $validated['lat'], 'lng' => $validated['lng']]);
            } elseif (!empty($validated['perusahaan_id']) && isset($perusahaan)) {
                $perusahaan->update(['lat' => $validated['lat'], 'lng' => $validated['lng']]);
            }
        }

        // Store photo
        $fotoPath = null;
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $fotoPath = $request->file('foto')->store('kunjungan', 'public');
        }

        $nomor = 'KNJ-' . $user->id . '-' . now()->format('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(4));

        $kunjungan = Kunjungan::create([
            'nomor'                 => $nomor,
            'tanggal'               => $validated['tanggal'],
            'waktu'                 => now()->format('H:i:s'),
            'sales_id'              => $user->id,
            'prodi_id'              => $validated['prodi_id'],
            'jenis'                 => $jenis,
            'tujuan_id'             => $tujuanId,
            'tujuan_kunjungan'      => $namaInstitusi,
            'nama_institusi'        => $namaInstitusi,
            'hasil'                 => $validated['hasil'] ?? ('Kunjungan ' . $jenis . ' — ' . $namaInstitusi),
            'catatan'               => $validated['catatan'] ?? null,
            'pic_name'              => $validated['pic_name'] ?? ($user->name),
            'pic_whatsapp'          => $validated['pic_whatsapp'] ?? '08xxxxxxxxxx',
            'foto_path'             => $fotoPath,
            'lat'                   => $validated['lat'],
            'lng'                   => $validated['lng'],
            'jarak_meter'           => $geoResult['distance_meters'],
            'status_lokasi'         => $geoResult['status_lokasi'],
            'status_verifikasi'     => $geoResult['status_verifikasi'],
            'is_outside_radius'     => $geoResult['is_outside_radius'],
            'is_verified'           => $geoResult['is_verified'],
            'status'                => $geoResult['status'],
            'dosen_id'              => $validated['dosen_id'] ?? null,
            'dosen_pemateri'        => $validated['dosen_pemateri'] ?? null,
            'kesediaan_training_ai' => $request->boolean('kesediaan_training_ai') || $request->boolean('is_training'),
            'tier'                  => $tier,
            'budget_maksimum'       => $budget,
        ]);

        return response()->json([
            'message' => 'Kunjungan berhasil ditambahkan',
            'data'    => $kunjungan->load(['sales', 'sekolah', 'perusahaan', 'prodi', 'dosen'])
        ], 201);
    }

    /**
     * Show single visit detail with authorization check.
     */
    public function show(Request $request, Kunjungan $kunjungan): JsonResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $kunjungan);

        return response()->json([
            'data' => $kunjungan->load(['sales', 'sekolah', 'perusahaan', 'prodi', 'dosen'])
        ]);
    }
}
