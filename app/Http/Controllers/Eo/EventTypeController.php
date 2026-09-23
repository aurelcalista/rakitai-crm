<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MasterData;

class EventTypeController extends Controller
{
    public function index()
    {
        $pageTitle = 'Manajemen Jenis Event';
        $currentUser = [
            'name' => auth()->user()->name,
            'role' => auth()->user()->role,
            'role_label' => strtoupper(auth()->user()->role),
            'avatar' => strtoupper(substr(auth()->user()->name, 0, 1)),
        ];

        $eventTypes = MasterData::where('type', 'jenis_event')->orderBy('nama')->get();

        return view('eo.event-types.index', compact('pageTitle', 'currentUser', 'eventTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        MasterData::create([
            'type' => 'jenis_event',
            'nama' => $request->nama,
            'kode' => strtoupper(str_replace(' ', '_', $request->nama)),
            'status' => 'Aktif'
        ]);

        return redirect()->route('eo.event-types.index')->with('success', 'Jenis event berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $eventType = MasterData::findOrFail($id);
        $eventType->update([
            'nama' => $request->nama,
            'status' => $request->status,
        ]);

        return redirect()->route('eo.event-types.index')->with('success', 'Jenis event berhasil diubah');
    }

    public function destroy($id)
    {
        $eventType = MasterData::findOrFail($id);
        // Cek jika sudah terpakai
        if (\App\Models\Event::where('type_id', $id)->exists()) {
            return redirect()->route('eo.event-types.index')->with('error', 'Gagal menghapus jenis event karena sudah digunakan');
        }

        $eventType->delete();

        return redirect()->route('eo.event-types.index')->with('success', 'Jenis event berhasil dihapus');
    }
}
