<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAkademik;
use App\Services\AkademikService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TahunAkademikController extends Controller
{
    public function index(): View
    {
        $list       = TahunAkademik::orderByDesc('nama')->get();
        $active     = AkademikService::getAktif();
        $activeList = AkademikService::getAktifList();

        return view('admin.tahun-akademik.index', compact('list', 'active', 'activeList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:20|unique:tahun_akademiks,nama',
        ]);

        TahunAkademik::create([
            'nama'   => $request->nama,
            'status' => 'Non-Aktif',
        ]);

        return back()->with('success', "Tahun Akademik {$request->nama} berhasil ditambahkan.");
    }

    /**
     * Atomically activate a Tahun Akademik (max 2 active).
     */
    public function activate(TahunAkademik $tahunAkademik): RedirectResponse
    {
        try {
            AkademikService::activate($tahunAkademik);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Tahun Akademik {$tahunAkademik->nama} sekarang Aktif.");
    }

    /**
     * Atomically deactivate a Tahun Akademik (min 1 active).
     */
    public function deactivate(TahunAkademik $tahunAkademik): RedirectResponse
    {
        try {
            AkademikService::deactivate($tahunAkademik);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Tahun Akademik {$tahunAkademik->nama} sekarang Non-Aktif.");
    }
}
