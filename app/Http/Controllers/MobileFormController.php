<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Kunjungan;
use App\Models\MasterData;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MobileFormController extends Controller
{
    /**
     * Authenticate user via active session, Sanctum Bearer token, or valid temporary signed URL.
     * Raw query parameter or body spoofing of user_id is strictly disallowed (CWE-287 fix).
     */
    protected function authenticateFromRequest(Request $request): ?User
    {
        // 1. Existing authenticated web session
        if (Auth::check()) {
            return Auth::user();
        }

        // 2. Sanctum Bearer Token (for Flutter / mobile API clients)
        if ($request->bearerToken()) {
            $sanctumUser = auth('sanctum')->user();
            if ($sanctumUser && $sanctumUser->status === 'Aktif') {
                Auth::login($sanctumUser);
                return $sanctumUser;
            }
        }

        // 3. Cryptographically signed temporary URL (for Flutter WebView integration)
        if ($request->hasValidSignature() && $request->filled('signed_user_id')) {
            $signedUser = User::where('status', 'Aktif')->find($request->signed_user_id);
            if ($signedUser) {
                Auth::login($signedUser);
                return $signedUser;
            }
        }

        return null;
    }

    /**
     * Mobile Form: Input Prospek Baru
     */
    public function prospekCreate(Request $request)
    {
        $user = $this->authenticateFromRequest($request);
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'))->with('error', 'Silakan login terlebih dahulu untuk mengakses formulir lapangan.');
        }

        $sekolahs = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $prodis = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $sources = MasterData::where('type', 'sumber_prospek')->where('status', 'Aktif')->get();
        $categories = MasterData::where('type', 'kategori_prospek')->where('status', 'Aktif')->get();

        // Subordinate sales if user is SPV
        $salesList = [];
        if ($user && in_array($user->role, ['SPV', 'Admin', 'Head Marketing', 'HM'])) {
            $salesList = User::where('role', 'Sales')->where('status', 'Aktif')->get();
        }

        return view('mobile.prospek-create', compact('sekolahs', 'perusahaans', 'prodis', 'sources', 'categories', 'salesList', 'user'));
    }

    /**
     * Mobile Store: Simpan Prospek Baru
     */
    public function prospekStore(Request $request)
    {
        $user = $this->authenticateFromRequest($request);
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            abort(401, 'Sesi tidak valid atau telah berakhir. Akses form lapangan memerlukan autentikasi yang sah.');
        }

        if ($request->filled('whatsapp')) {
            $request->merge(['whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->whatsapp)]);
        }

        $request->validate([
            'type'      => 'required|in:Sekolah,Corporate,Individu',
            'prodi_id'  => 'nullable|exists:prodis,id',
            'pic'       => 'required|string|max:255',
            'whatsapp'  => ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)],
            'source'    => 'required|string|max:100',
        ]);

        // Resolve sales handler with role authorization (only SPV/Admin/HM can assign subordinate sales)
        if (in_array($user->role, ['SPV', 'Admin', 'Head Marketing', 'HM']) && $request->filled('sales_id')) {
            $targetSales = User::where('role', 'Sales')->where('status', 'Aktif')->find($request->sales_id);
            $salesId = $targetSales ? $targetSales->id : $user->id;
        } else {
            $salesId = $user->id;
        }

        // Auto-resolve Name
        $nama = $request->input('name');
        if (empty($nama)) {
            if ($request->type === 'Sekolah' && $request->filled('sekolah_id')) {
                $sek = Sekolah::find($request->sekolah_id);
                $nama = $sek ? $sek->nama : $request->pic;
            } elseif ($request->type === 'Corporate' && $request->filled('perusahaan_id')) {
                $per = Perusahaan::find($request->perusahaan_id);
                $nama = $per ? $per->nama : $request->pic;
            } else {
                $nama = $request->pic;
            }
        }

        $activeTa = TahunAkademik::getAktif();

        $prospek = Prospek::create([
            'name'             => $nama,
            'type'             => $request->type,
            'category'         => $request->category,
            'sekolah_id'       => $request->type === 'Sekolah' ? $request->sekolah_id : null,
            'perusahaan_id'    => $request->type === 'Corporate' ? $request->perusahaan_id : null,
            'prodi_id'         => $request->prodi_id,
            'pic'              => $request->pic,
            'pic_phone'        => \App\Support\WhatsAppHelper::formatForDatabase($request->whatsapp),
            'whatsapp'         => \App\Support\WhatsAppHelper::formatForDatabase($request->whatsapp),
            'sales_id'         => $salesId,
            'owner_id'         => $user->id,
            'wilayah_id'       => $user->wilayah_id,
            'status'           => 'BARU',
            'stage_number'     => 1,
            'notes'            => $request->notes ?? 'Input melalui Form Mobile Flutter',
            'source'           => $request->source,
            'academic_year_id' => $activeTa?->id,
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => $user->id,
            'title'        => 'Prospek Dibuat via Mobile App',
            'notes'        => 'Prospek berhasil diinput langsung melalui form mobile lapangan.',
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return redirect()->route('mobile.sukses', [
            'type'    => 'prospek',
            'title'   => 'Prospek Berhasil Disimpan!',
            'message' => 'Data prospek ' . $prospek->name . ' telah masuk ke alur pipeline.',
        ]);
    }

    /**
     * Mobile Form: Input Laporan Kunjungan
     */
    public function kunjunganCreate(Request $request)
    {
        $user = $this->authenticateFromRequest($request);
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'))->with('error', 'Silakan login terlebih dahulu untuk mengakses formulir kunjungan.');
        }

        $prospekQuery = Prospek::where('type', 'Sekolah');
        if ($user && $user->role === 'Sales') {
            $prospekQuery->where('sales_id', $user->id);
        } elseif ($user && $user->role === 'SPV') {
            $subIds = User::where('supervisor_id', $user->id)->pluck('id')->push($user->id);
            $prospekQuery->whereIn('sales_id', $subIds);
        }
        $availableProspekSekolah = $prospekQuery->orderBy('name')->get();

        $sekolahs = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $prodis = Prodi::where('status', 'Aktif')->orderBy('nama')->get();

        return view('mobile.kunjungan-create', compact('availableProspekSekolah', 'sekolahs', 'perusahaans', 'prodis', 'user'));
    }

    /**
     * Mobile Store: Simpan Laporan Kunjungan
     */
    public function kunjunganStore(Request $request)
    {
        $user = $this->authenticateFromRequest($request);
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            abort(401, 'Sesi tidak valid atau telah berakhir. Akses form lapangan memerlukan autentikasi yang sah.');
        }

        if ($request->filled('pic_whatsapp')) {
            $request->merge(['pic_whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->pic_whatsapp)]);
        }

        $request->validate([
            'jenis'        => 'required|in:Sekolah,Perusahaan',
            'tanggal'      => 'required|date',
            'pic_name'     => 'required|string|max:255',
            'pic_whatsapp' => ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)],
            'lat'          => 'required|numeric',
            'lng'          => 'required|numeric',
            'foto'         => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $fotoPath = $request->file('foto')->store('kunjungan', 'public');
        }

        // Resolve nama institusi
        $namaInstitusi = $request->nama_institusi;
        $tujuanId = 0;
        if ($request->jenis === 'Sekolah') {
            if ($request->filled('sekolah_manual')) {
                $manualName = trim($request->sekolah_manual);
                if ($manualName !== '') {
                    $s = Sekolah::firstOrCreate(
                        ['nama' => $manualName],
                        [
                            'kode' => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'status' => 'Aktif',
                            'pic_name' => $request->pic_name,
                            'pic_phone' => $request->pic_whatsapp,
                        ]
                    );
                    $namaInstitusi = $s->nama;
                    $tujuanId = $s->id;
                }
            } elseif ($request->filled('prospek_id')) {
                $p = Prospek::find($request->prospek_id);
                if ($p) {
                    $namaInstitusi = $p->name;
                    $tujuanId = $p->sekolah_id ?? 0;
                }
            } elseif ($request->filled('sekolah_id')) {
                $s = Sekolah::find($request->sekolah_id);
                if ($s) {
                    $namaInstitusi = $s->nama;
                    $tujuanId = $s->id;
                }
            }
        } else {
            if ($request->filled('perusahaan_manual')) {
                $manualName = trim($request->perusahaan_manual);
                if ($manualName !== '') {
                    $per = Perusahaan::firstOrCreate(
                        ['nama' => $manualName],
                        [
                            'kode' => 'CORP-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'status' => 'Aktif',
                            'pic_name' => $request->pic_name,
                            'pic_phone' => $request->pic_whatsapp,
                        ]
                    );
                    $namaInstitusi = $per->nama;
                    $tujuanId = $per->id;
                }
            } elseif ($request->filled('perusahaan_id')) {
                $per = Perusahaan::find($request->perusahaan_id);
                if ($per) {
                    $namaInstitusi = $per->nama;
                    $tujuanId = $per->id;
                }
            }
        }

        if (empty($namaInstitusi)) {
            $namaInstitusi = $request->jenis . ' Kunjungan Lapangan';
        }

        // Resolve sales handler with role authorization (only SPV/Admin/HM can assign subordinate sales)
        if (in_array($user->role, ['SPV', 'Admin', 'Head Marketing', 'HM']) && $request->filled('sales_id')) {
            $targetSales = User::where('role', 'Sales')->where('status', 'Aktif')->find($request->sales_id);
            $salesId = $targetSales ? $targetSales->id : $user->id;
        } else {
            $salesId = $user->id;
        }

        $nomor = 'KNJ-M-' . $salesId . '-' . now()->format('YmdHis');

        $activeTa = TahunAkademik::getAktif();

        $rawProdiIds = $request->input('prodi_ids', []);
        if (!is_array($rawProdiIds)) {
            $rawProdiIds = !empty($rawProdiIds) ? [$rawProdiIds] : [];
        }
        if (empty($rawProdiIds) && $request->filled('prodi_id')) {
            $rawProdiIds = [$request->input('prodi_id')];
        }
        $prodiIds = array_values(array_unique(array_filter(array_map('intval', $rawProdiIds))));
        $firstProdiId = $prodiIds[0] ?? $request->input('prodi_id') ?? 1;

        $kunjungan = Kunjungan::create([
            'nomor'                 => $nomor,
            'tanggal'               => $request->tanggal,
            'waktu'                 => now()->format('H:i:s'),
            'sales_id'              => $salesId,
            'prodi_id'              => $firstProdiId,
            'prodi_ids'             => !empty($prodiIds) ? $prodiIds : [$firstProdiId],
            'jenis'                 => $request->jenis,
            'tujuan_id'             => $tujuanId,
            'tujuan_kunjungan'      => $namaInstitusi,
            'hasil'                 => 'Kunjungan Lapangan Mobile: ' . $namaInstitusi,
            'catatan'               => $request->catatan,
            'status'                => 'Selesai',
            'status_verifikasi'     => 'Valid',
            'is_verified'           => true,
            'academic_year_id'      => $activeTa?->id,
            'nama_institusi'        => $namaInstitusi,
            'alamat'                => $request->alamat,
            'lokasi_penugasan'      => $request->lokasi_penugasan ?? 'Check-in Mobile GPS',
            'pic_name'              => $request->pic_name,
            'pic_whatsapp'          => \App\Support\WhatsAppHelper::formatForDatabase($request->pic_whatsapp),
            'foto_path'             => $fotoPath,
            'lat'                   => $request->lat,
            'lng'                   => $request->lng,
            'potensi_mahasiswa'     => $request->potensi_mahasiswa,
            'detail_potensi_mahasiswa' => $request->detail_potensi_mahasiswa,
            'kesediaan_training_ai' => $request->boolean('kesediaan_training_ai'),
        ]);

        if ($request->filled('prospek_id')) {
            $prospek = Prospek::find($request->prospek_id);
            if ($prospek) {
                $prospek->needs_visit_report = false;
                if ($prospek->status === 'BARU') {
                    $prospek->status = 'KONTAK';
                    $prospek->stage_number = 2;
                }
                $prospek->save();

                ProspekTimeline::create([
                    'prospek_id'   => $prospek->id,
                    'user_id'      => $user->id,
                    'title'        => 'Laporan Kunjungan Mobile Sukses',
                    'notes'        => 'Kunjungan lapangan selesai diinput via Flutter WebView (' . $nomor . ')',
                    'status_after' => $prospek->status,
                    'time'         => now(),
                ]);
            }
        }

        return redirect()->route('mobile.sukses', [
            'type'    => 'kunjungan',
            'title'   => 'Laporan Kunjungan Berhasil Terkirim!',
            'message' => 'Laporan kunjungan ke ' . $namaInstitusi . ' telah tersimpan dengan nomor ' . $nomor,
        ]);
    }

    /**
     * Mobile Success Page
     */
    public function sukses(Request $request)
    {
        $type = $request->query('type', 'general');
        $title = $request->query('title', 'Berhasil Disimpan!');
        $message = $request->query('message', 'Data formulir lapangan telah sukses dikirim ke sistem CRM.');

        return view('mobile.sukses', compact('type', 'title', 'message'));
    }
}
