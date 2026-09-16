<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    public function index(Request $request)
    {
        $query = Wilayah::with('parent');
        
        if ($request->has('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $wilayahs = $query->orderBy('level')->orderBy('kode')->get();

        // For the create/edit forms
        $parents = Wilayah::whereIn('level', ['Provinsi', 'Kota/Kabupaten'])->get();

        return view('wilayah.index', [
            'wilayahs' => $wilayahs,
            'parents' => $parents,
            'currentUser' => ['role' => session('user_role', 'Head Marketing'), 'name' => session('user_name', 'Budi Santoso')]
        ]);
    }

    public function store(Request $request)
    {
        $messages = [
            'kode.required' => 'Kode wilayah wajib diisi.',
            'nama.required' => 'Nama wilayah wajib diisi.',
            'level.required' => 'Jenis wilayah wajib dipilih.',
            'level.in' => 'Jenis wilayah wajib dipilih.',
        ];

        $request->validate([
            'kode' => 'required',
            'nama' => 'required|string|max:255',
            'level' => 'required|in:Kota/Kabupaten,Kecamatan',
        ], $messages);

        $data = $request->all();
        if ($request->level === 'Kecamatan') {
            $request->validate([
                'parent_id' => 'required|exists:wilayahs,id'
            ], [
                'parent_id.required' => 'Kota/Kabupaten induk wajib dipilih.'
            ]);
            $data['parent_id'] = $request->parent_id;
            
            $existing = Wilayah::where('kode', $request->kode)->where('parent_id', $request->parent_id)->first();
            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'Gagal menambahkan wilayah. Kode wilayah "' . $request->kode . '" sudah digunakan oleh ' . $existing->nama . ' pada wilayah induk yang sama.');
            }
        } else {
            $data['parent_id'] = null;
            
            $existing = Wilayah::where('kode', $request->kode)->where('level', 'Kota/Kabupaten')->first();
            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'Gagal menambahkan wilayah. Kode wilayah "' . $request->kode . '" sudah digunakan.');
            }
        }

        Wilayah::create($data);

        return redirect()->route('wilayah.index')->with('success', 'Wilayah berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $wilayah = Wilayah::findOrFail($id);

        $messages = [
            'kode.required' => 'Kode wilayah wajib diisi.',
            'nama.required' => 'Nama wilayah wajib diisi.',
            'level.required' => 'Jenis wilayah wajib dipilih.',
            'level.in' => 'Jenis wilayah wajib dipilih.',
        ];

        $request->validate([
            'kode' => 'required',
            'nama' => 'required|string|max:255',
            'level' => 'required|in:Kota/Kabupaten,Kecamatan',
        ], $messages);

        $data = $request->all();
        if ($request->level === 'Kecamatan') {
            $request->validate([
                'parent_id' => 'required|exists:wilayahs,id'
            ], [
                'parent_id.required' => 'Kota/Kabupaten induk wajib dipilih.'
            ]);
            $data['parent_id'] = $request->parent_id;
            
            $existing = Wilayah::where('kode', $request->kode)->where('parent_id', $request->parent_id)->where('id', '!=', $id)->first();
            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui wilayah. Kode wilayah "' . $request->kode . '" sudah digunakan oleh ' . $existing->nama . ' pada wilayah induk yang sama.');
            }
        } else {
            $data['parent_id'] = null;
            
            $existing = Wilayah::where('kode', $request->kode)->where('level', 'Kota/Kabupaten')->where('id', '!=', $id)->first();
            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui wilayah. Kode wilayah "' . $request->kode . '" sudah digunakan oleh ' . $existing->nama . '.');
            }
        }

        $wilayah->update($data);

        return redirect()->route('wilayah.index')->with('success', 'Wilayah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $wilayah = Wilayah::findOrFail($id);

        // Validasi jika masih ada user (Assignment Tim) yang menggunakan wilayah ini
        $hasUsers = \App\Models\User::where('wilayah_id', $wilayah->id)->exists();
        if ($hasUsers) {
            return redirect()->route('wilayah.index')->with('error', 'Wilayah tidak dapat dihapus karena masih digunakan pada assignment tim.');
        }
        
        // Validasi jika wilayah ini punya anak (Kecamatan terkait)
        $hasChildren = Wilayah::where('parent_id', $wilayah->id)->exists();
        if ($hasChildren) {
            return redirect()->route('wilayah.index')->with('error', 'Wilayah tidak dapat dihapus karena memiliki Kecamatan yang terhubung.');
        }

        $namaWilayah = $wilayah->nama;
        $wilayah->delete();

        return redirect()->route('wilayah.index')->with('success', 'Wilayah ' . $namaWilayah . ' berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        $wilayah = Wilayah::findOrFail($id);
        $wilayah->status = $wilayah->status === 'Aktif' ? 'Nonaktif' : 'Aktif';
        $wilayah->save();

        $message = $wilayah->status === 'Aktif' ? 'Wilayah berhasil diaktifkan.' : 'Wilayah berhasil dinonaktifkan.';
        return redirect()->route('wilayah.index')->with('success', $message);
    }
}
