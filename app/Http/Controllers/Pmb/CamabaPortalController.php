<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CamabaPortalController extends Controller
{
    /**
     * Dapatkan record Prospek terkait akun CAMABA yang sedang login.
     */
    protected function getCamabaProspek(): ?Prospek
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // Cari via relasi user_id, fallback ke email jika belum terlink
        $prospek = $user->prospek;
        if (!$prospek) {
            $prospek = Prospek::where('email', strtolower($user->email))->first();
            if ($prospek && !$prospek->user_id) {
                $prospek->user_id = $user->id;
                $prospek->save();
            }
        }

        return $prospek;
    }

    /**
     * Halaman Publik Utama Portal PMB (Landing Page).
     */
    public function landing(): View
    {
        $prodis = Prodi::where('status', 'Aktif')
            ->orWhereNull('status')
            ->orderBy('fakultas')
            ->orderBy('nama')
            ->get();

        return view('pmb.portal.landing', compact('prodis'));
    }

    /**
     * Dashboard Akun CAMABA.
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        $transaksiAktif = null;
        $riwayatTransaksi = collect();

        if ($prospek) {
            $riwayatTransaksi = $prospek->transaksis()
                ->orderBy('created_at', 'desc')
                ->get();

            $transaksiAktif = $prospek->transaksis()
                ->where('kategori', \App\Models\Transaksi::KATEGORI_DP_DAFTAR)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('pmb.portal.dashboard', compact('user', 'prospek', 'transaksiAktif', 'riwayatTransaksi'));
    }

    /**
     * Halaman Status Pendaftaran CAMABA.
     */
    public function status(): View
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        $transaksiDaftar = null;
        if ($prospek) {
            $transaksiDaftar = $prospek->transaksis()
                ->where('kategori', \App\Models\Transaksi::KATEGORI_DP_DAFTAR)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('pmb.portal.status', compact('user', 'prospek', 'transaksiDaftar'));
    }

    /**
     * Halaman Profil CAMABA (Lihat & Edit Data Pribadi).
     */
    public function profile(): View
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();
        $prodis = Prodi::where('status', 'Aktif')->orWhereNull('status')->orderBy('nama')->get();

        return view('pmb.portal.profile', compact('user', 'prospek', 'prodis'));
    }

    /**
     * Update Data Profil Pribadi CAMABA.
     * Tidak mengizinkan perubahan status, verifikasi pembayaran, nilai ujian, atau role.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        $validated = $request->validate([
            'name'                => 'required|string|min:2|max:255',
            'whatsapp'            => ['required', 'string', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
            'asal_sekolah'        => 'nullable|string|max:255',
            'prodi_id'            => 'nullable|exists:prodis,id',
            'prodi_pilihan_2_id'  => 'nullable|different:prodi_id|exists:prodis,id',
            'minat_kuliah'        => 'nullable|in:ya,tidak',
            'minat_miniclass'     => 'nullable|in:ya,tidak',
        ], [
            'name.required'             => 'Nama lengkap wajib diisi.',
            'whatsapp.required'         => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex'            => 'Format nomor WhatsApp tidak valid.',
            'prodi_pilihan_2_id.different' => 'Pilihan program studi kedua tidak boleh sama dengan pilihan pertama.',
        ]);

        $formattedPhone = \App\Support\WhatsAppHelper::formatForDatabase($validated['whatsapp']);

        DB::transaction(function () use ($user, $prospek, $validated, $formattedPhone) {
            // Update User record
            $user->name = trim($validated['name']);
            $user->phone = $formattedPhone;
            $user->save();

            // Update Prospek record
            if ($prospek) {
                $prospek->name = trim($validated['name']);
                $prospek->whatsapp = $formattedPhone;
                $prospek->pic_phone = $formattedPhone;
                $prospek->pic = trim($validated['name']);

                if (!empty($validated['asal_sekolah'])) {
                    $sekolah = Sekolah::where('nama', 'like', trim($validated['asal_sekolah']))->first();
                    if ($sekolah) {
                        $prospek->sekolah_id = $sekolah->id;
                    }
                    $notes = $prospek->notes ?? '';
                    $cleanSchool = trim($validated['asal_sekolah']);
                    if (!str_contains($notes, $cleanSchool)) {
                        $prospek->notes = trim($notes . ' | Asal sekolah: ' . $cleanSchool);
                    }
                }

                if (isset($validated['prodi_id'])) {
                    $prospek->prodi_id = $validated['prodi_id'];
                }
                if (isset($validated['prodi_pilihan_2_id'])) {
                    $prospek->prodi_pilihan_2_id = $validated['prodi_pilihan_2_id'];
                }
                if (isset($validated['minat_kuliah'])) {
                    $prospek->minat_kuliah = $validated['minat_kuliah'];
                }
                if (isset($validated['minat_miniclass'])) {
                    $prospek->minat_miniclass = $validated['minat_miniclass'];
                }

                $prospek->save();
            }
        });

        return redirect()->route('pmb.akun.profil')
            ->with('success', 'Data profil Anda berhasil diperbarui.');
    }

    /**
     * Halaman Pembayaran CAMABA (Integrasi Midtrans Snap).
     */
    public function bayar(Request $request, MidtransService $midtransService)
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        if (!$prospek) {
            return redirect()->route('pmb.akun.dashboard')
                ->with('error', 'Data pendaftaran Anda belum terhubung. Silakan hubungi admin PMB.');
        }

        // Dapatkan transaksi pendaftaran (Rp 300.000) yang terhubung ke prospek
        $transaksi = $midtransService->getOrCreateRegistrationTransaksi($prospek);

        // Jika token Snap belum ada dan belum verified, buat token
        if ($transaksi->payment_status !== \App\Models\Transaksi::STATUS_VERIFIED && empty($transaksi->snap_token)) {
            $midtransService->createSnapToken($transaksi);
            $transaksi->refresh();
        }

        $riwayatTransaksi = $prospek->transaksis()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pmb.portal.pembayaran', compact('user', 'prospek', 'transaksi', 'riwayatTransaksi'));
    }

    /**
     * Halaman Bantuan dan Kontak PMB UCIC.
     */
    public function bantuan(): View
    {
        return view('pmb.portal.bantuan');
    }
}
