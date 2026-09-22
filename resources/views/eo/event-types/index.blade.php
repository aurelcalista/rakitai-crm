<x-app-layout :title="$pageTitle">
<div x-data="{ 
    modalTambah: false, 
    modalEdit: false,
    selectedItem: null
}">

    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $pageTitle }}</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola jenis event dinamis yang bisa dipilih saat membuat event baru.</p>
        </div>
        <button @click="modalTambah = true" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-xl hover:bg-purple-700 transition shadow-sm ring-1 ring-purple-700/50">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Jenis Event
        </button>
    </div>

    <!-- Data Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-5 py-4 w-12 text-center">No</th>
                        <th class="px-5 py-4">Nama Jenis Event</th>
                        <th class="px-5 py-4 w-32">Status</th>
                        <th class="px-5 py-4 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($eventTypes as $index => $type)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-5 py-4 text-center font-medium text-slate-500">{{ $index + 1 }}</td>
                            <td class="px-5 py-4 font-bold text-slate-900">{{ $type->nama }}</td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-full {{ $type->status == 'Aktif' ? 'bg-purple-100 text-purple-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $type->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" @click="selectedItem = {{ json_encode($type) }}; modalEdit = true" class="p-2 text-blue-600 hover:bg-blue-50 rounded-xl transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </button>
                                    <form action="{{ route('eo.event-types.destroy', $type->id) }}" method="POST" class="inline" data-confirm="Yakin ingin menghapus jenis event ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                <p>Belum ada jenis event.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div x-show="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0" x-cloak>
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="modalTambah = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-900">Tambah Jenis Event</h3>
                <button @click="modalTambah = false" class="text-slate-400 hover:text-slate-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            <form action="{{ route('eo.event-types.store') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1 transition-colors group-focus-within:text-purple-600">Nama Jenis Event <span class="text-rose-500">*</span></label>
                        <div class="relative group">
                            <input type="text" name="nama" required placeholder="Contoh: Seminar" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm transition-all duration-300 focus:bg-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 hover:border-purple-300">
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="modalTambah = false" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 hover:-translate-y-0.5 transition-all duration-300 active:scale-95">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-purple-600 rounded-xl hover:bg-purple-700 hover:shadow-lg hover:shadow-purple-600/30 hover:-translate-y-0.5 transition-all duration-300 active:scale-95">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div x-show="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0" x-cloak>
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="modalEdit = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-900">Edit Jenis Event</h3>
                <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            <form :action="'{{ url('eo/event-types') }}/' + selectedItem?.id" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1 transition-colors group-focus-within:text-purple-600">Nama Jenis Event <span class="text-rose-500">*</span></label>
                        <div class="relative group">
                            <input type="text" name="nama" x-model="selectedItem.nama" required class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm transition-all duration-300 focus:bg-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 hover:border-purple-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1 transition-colors group-focus-within:text-purple-600">Status <span class="text-rose-500">*</span></label>
                        <div class="relative group">
                            <select name="status" x-model="selectedItem.status" required class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm transition-all duration-300 focus:bg-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 hover:border-purple-300">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="modalEdit = false" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 hover:-translate-y-0.5 transition-all duration-300 active:scale-95">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-purple-600 rounded-xl hover:bg-purple-700 hover:shadow-lg hover:shadow-purple-600/30 hover:-translate-y-0.5 transition-all duration-300 active:scale-95">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
