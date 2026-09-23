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
        $list   = TahunAkademik::orderByDesc('nama')->get();
        $active = AkademikService::getAktif();

        return view('admin.tahun-akademik.index', compact('list', 'active'));
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
     * Atomically activate a Tahun Akademik.
     * Uses AkademikService::activate() which holds a DB-level lock.
     */
    public function activate(TahunAkademik $tahunAkademik): RedirectResponse
    {
        AkademikService::activate($tahunAkademik);

        return back()->with('success', "Tahun Akademik {$tahunAkademik->nama} sekarang Aktif.");
    }
}
