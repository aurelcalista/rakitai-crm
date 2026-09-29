@extends('layouts.app', ['title' => 'Master Lokasi Absensi'])

@section('content')
<div class="space-y-6" x-data="{
    modalOpen: false,
    isEdit: false,
    editId: null,
    formData: {
        name: '',
        latitude: '',
        longitude: '',
        radius: 100,
        status: 'active'
    },
    actionUrl: '{{ route('admin.attendance-locations.store') }}',
    openCreate() {
        this.isEdit = false;
        this.editId = null;
        this.formData = { name: '', latitude: '', longitude: '', radius: 100, status: 'active' };
        this.actionUrl = '{{ route('admin.attendance-locations.store') }}';
        this.modalOpen = true;
    },
    openEdit(loc) {
        this.isEdit = true;
        this.editId = loc.id;
        this.formData = {
            name: loc.name,
            latitude: loc.latitude,
            longitude: loc.longitude,
            radius: loc.radius,
            status: loc.status
        };
        this.actionUrl = '/admin/attendance-locations/' + loc.id;
        this.modalOpen = true;
    }
}">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Master Titik Lokasi Absensi</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola daftar titik koordinat resmi dan batas radius meter untuk presensi kehadiran staf lapangan.</p>
        </div>
        <div>
            <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Titik Lokasi
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif
    @if($errors->any())
        <x-alert type="error" title="Validasi Gagal" :message="$errors->first()" />
    @endif

    <!-- Filter & Search Card -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.attendance-locations.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama lokasi..." class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
            </div>
            <div class="w-full sm:w-48">
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif Saja</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif Saja</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
                    Cari
                </button>
                <a href="{{ route('admin.attendance-locations.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Locations Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                    <tr>
                        <th class="py-3.5 px-4">Nama Titik Lokasi</th>
                        <th class="py-3.5 px-4">Latitude (Presisi 6 Desimal)</th>
                        <th class="py-3.5 px-4">Longitude (Presisi 6 Desimal)</th>
                        <th class="py-3.5 px-4">Radius</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Total Absen</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($locations as $loc)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4 font-bold text-slate-800">
                                {{ $loc->name }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ number_format($loc->latitude, 6, '.', '') }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ number_format($loc->longitude, 6, '.', '') }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-700">
                                {{ $loc->radius }} meter
                            </td>
                            <td class="py-3 px-4">
                                @if($loc->status === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ $loc->attendances_count }} kali
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <form action="{{ route('admin.attendance-locations.toggle-status', $loc->id) }}" method="POST" data-confirm="Apakah Anda yakin ingin mengubah status titik lokasi '{{ $loc->name }}'?" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 rounded-lg text-[11px] font-semibold {{ $loc->status === 'active' ? 'text-amber-700 bg-amber-50 hover:bg-amber-100' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }} transition cursor-pointer" title="Ubah status">
                                            {{ $loc->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <button type="button" @click="openEdit({{ json_encode($loc) }})" class="px-2 py-1 rounded-lg text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition cursor-pointer">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.attendance-locations.destroy', $loc->id) }}" method="POST" data-confirm="Apakah Anda yakin ingin menghapus titik lokasi '{{ $loc->name }}'? Riwayat absensi terdahulu akan tetap aman tersimpan." class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                Belum ada titik lokasi absensi. Silakan klik tombol "Tambah Titik Lokasi".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $locations->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form (Tambah / Edit) -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="modalOpen = false"></div>
            
            <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle bg-white rounded-2xl shadow-xl transform transition-all border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900" x-text="isEdit ? 'Edit Titik Lokasi Absensi' : 'Tambah Titik Lokasi Absensi Baru'"></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="actionUrl" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Nama Titik Lokasi <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="formData.name" placeholder="Misal: Kampus Utama, Kantor Cirebon, Area Event A" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3" required>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Latitude <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.000001" min="-90" max="90" name="latitude" x-model="formData.latitude" placeholder="-6.982345" class="w-full text-xs font-mono rounded-xl border-slate-200 py-2.5 px-3" required>
                            <span class="text-[10px] text-slate-400">Presisi 6 desimal (-90 s/d 90)</span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Longitude <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.000001" min="-180" max="180" name="longitude" x-model="formData.longitude" placeholder="108.487654" class="w-full text-xs font-mono rounded-xl border-slate-200 py-2.5 px-3" required>
                            <span class="text-[10px] text-slate-400">Presisi 6 desimal (-180 s/d 180)</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Radius (Meter) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" min="1" max="50000" name="radius" x-model="formData.radius" placeholder="100" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3" required>
                            <span class="text-[10px] text-slate-400">Jarak toleransi absensi (m)</span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Status <span class="text-rose-500">*</span>
                            </label>
                            <select name="status" x-model="formData.status" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3" required>
                                <option value="active">Active (Bisa Dipilih)</option>
                                <option value="inactive">Inactive (Dinonaktifkan)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition">
                            Simpan Lokasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
