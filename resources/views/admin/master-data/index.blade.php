@php $pageTitle = 'Data Master'; @endphp

<x-app-layout :title="'Data Master - CRM UCIC'">

    <div class="space-y-6" x-data="{
        activeTab: 'tahun_akademik',
        modalAdd: false,
        modalAddTA: false,
        modalEdit: false,
        addTabName: '',
        selectedItem: null,
        tabs: [
            {key:'tahun_akademik',   label:'Tahun Akademik'},
            {key:'status_prospek',   label:'Status Prospek'},
            {key:'status_followup',  label:'Status Follow Up'},
            {key:'jenis_kunjungan',  label:'Jenis Kunjungan'},
            {key:'kategori_prospek', label:'Kategori Prospek'},
            {key:'sumber_prospek',   label:'Sumber Prospek'},
            {key:'kategori_sekolah', label:'Kategori Sekolah'},
            {key:'kategori_perusahaan', label:'Kategori Perusahaan'},
            {key:'program_studi', label:'Program Studi'},
            {key:'jenjang', label:'Jenjang'},
        ]
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Master CRM</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Konfigurasi Tahun Akademik, status, kategori, dan pilihan master data di seluruh sistem CRM.</p>
            </div>
            <div>
                <button 
                    type="button" 
                    x-show="activeTab === 'tahun_akademik'"
                    @click="modalAddTA = true"
                    class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Tambah Tahun Akademik</span>
                </button>
                <button 
                    type="button" 
                    x-show="activeTab !== 'tahun_akademik'"
                    @click="addTabName = tabs.find(t=>t.key===activeTab)?.label; modalAdd = true"
                    class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Tambah Item</span>
                </button>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
            <div class="flex min-w-max">
                <template x-for="tab in tabs" :key="tab.key">
                    <button type="button"
                        @click="activeTab = tab.key"
                        :class="activeTab === tab.key ? 'border-blue-600 text-blue-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="py-3.5 px-4 text-xs font-semibold transition cursor-pointer whitespace-nowrap border-b-2"
                        x-text="tab.label"></button>
                </template>
            </div>
        </div>

        <!-- Tab Content: Tahun Akademik (Special Guardrail) -->
        <div x-show="activeTab === 'tahun_akademik'" class="crm-card bg-white overflow-hidden space-y-4 p-5 sm:p-6 border border-slate-200/80 rounded-2xl shadow-xs">
            <!-- Notice Banner -->
            <div class="p-4 bg-blue-50/70 border border-blue-200/80 rounded-2xl flex items-start gap-3">
                <div class="p-2 bg-blue-100 text-blue-700 rounded-xl shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-xs text-blue-900 leading-relaxed">
                    <h4 class="font-bold text-sm text-blue-950 mb-0.5">Aturan Sistem: Tepat 1 Tahun Akademik Aktif</h4>
                    <p>Hanya boleh ada <strong>1 Tahun Akademik yang aktif</strong> dalam satu waktu. Tahun Akademik yang aktif secara otomatis menjadi basis data utama untuk <strong>Target & Performa Mingguan, Kunjungan Lapangan, Event PMB, serta Alur Pipeline Prospek</strong>.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase tracking-wider text-[11px] bg-slate-50/50">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Tahun Akademik</th>
                            <th class="py-3.5 px-4">Status Sistem</th>
                            <th class="py-3.5 px-4">Basis Data Terhubung</th>
                            <th class="py-3.5 px-4 text-right">Aksi & Kontrol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($tahunAkademiks as $ta)
                            <tr class="hover:bg-slate-50/80 transition {{ $ta->status === 'Aktif' ? 'bg-emerald-50/30' : '' }}">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400">{{ $loop->iteration }}</td>
                                <td class="py-3.5 px-4 font-bold text-sm text-slate-900">
                                    {{ $ta->nama }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($ta->status === 'Aktif')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            AKTIF
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                            Non-Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 font-medium">
                                    @if($ta->status === 'Aktif')
                                        <span class="text-emerald-700 font-semibold">Digunakan untuk Target Mingguan, Kunjungan & Prospek</span>
                                    @else
                                        <span class="text-slate-400">Arsip data periode</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($ta->status !== 'Aktif')
                                            <form action="{{ route('admin.tahun-akademik.activate', $ta->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengaktifkan Tahun Akademik {{ $ta->nama }}? Tahun akademik yang aktif sebelumnya akan dinonaktifkan.');">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs shadow-xs transition cursor-pointer">
                                                    Set Aktif
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.tahun-akademik.destroy', $ta->id) }}" method="POST" onsubmit="return confirm('Hapus Tahun Akademik {{ $ta->nama }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 text-xs font-semibold transition cursor-pointer">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-lg">
                                                Sedang Aktif (Kunci)
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 italic">
                                    Belum ada data Tahun Akademik.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: Status Prospek -->
        <div x-show="activeTab === 'status_prospek'" class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['status_prospek'], 'tabLabel' => 'Status Prospek'])
        </div>

        <!-- Tab Content: Status Follow Up -->
        <div x-show="activeTab === 'status_followup'" x-cloak class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['status_followup'], 'tabLabel' => 'Status Follow Up'])
        </div>

        <!-- Tab Content: Jenis Kunjungan -->
        <div x-show="activeTab === 'jenis_kunjungan'" x-cloak class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['jenis_kunjungan'], 'tabLabel' => 'Jenis Kunjungan'])
        </div>

        <!-- Tab Content: Kategori Prospek -->
        <div x-show="activeTab === 'kategori_prospek'" x-cloak class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['kategori_prospek'], 'tabLabel' => 'Kategori Prospek'])
        </div>

        <!-- Tab Content: Sumber Prospek -->
        <div x-show="activeTab === 'sumber_prospek'" x-cloak class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['sumber_prospek'], 'tabLabel' => 'Sumber Prospek'])
        </div>

        <!-- Tab Content: Kategori Sekolah -->
        <div x-show="activeTab === 'kategori_sekolah'" x-cloak class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['kategori_sekolah'], 'tabLabel' => 'Kategori Sekolah'])
        </div>

        <!-- Tab Content: Kategori Perusahaan -->
        <div x-show="activeTab === 'kategori_perusahaan'" x-cloak class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['kategori_perusahaan'], 'tabLabel' => 'Kategori Perusahaan'])
        </div>

        <!-- Tab Content: Program Studi -->
        <div x-show="activeTab === 'program_studi'" x-cloak class="crm-card bg-white overflow-hidden">
            <div class="p-4 bg-purple-50 border-b border-purple-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="text-xs text-purple-900 font-medium">
                    💡 Untuk mengelola <strong>Kuota Target, UKT, dan Status Prodi</strong>, silakan gunakan modul khusus <strong>Data Prodi</strong>.
                </div>
                <a href="{{ route('admin.prodi.index') }}" class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shrink-0 shadow-xs">
                    Kelola Kuota & UKT Prodi &rarr;
                </a>
            </div>
            @include('admin.master-data._table', ['items' => $masterData['program_studi'], 'tabLabel' => 'Program Studi'])
        </div>

        <!-- Tab Content: Jenjang -->
        <div x-show="activeTab === 'jenjang'" x-cloak class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['jenjang'], 'tabLabel' => 'Jenjang'])
        </div>

        <!-- MODAL TAMBAH TAHUN AKADEMIK -->
        <div x-show="modalAddTA" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAddTA = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Tambah Tahun Akademik</h3>
                            <p class="text-xs text-slate-500">Basis data kalender target & alur prospek.</p>
                        </div>
                        <button @click="modalAddTA = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form action="{{ route('admin.tahun-akademik.store') }}" method="POST" class="mt-4 space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tahun Akademik *</label>
                            <input 
                                type="text" 
                                name="nama" 
                                required 
                                pattern="^\d{4}\/\d{4}$" 
                                placeholder="Contoh: 2028/2029" 
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-200 font-semibold"
                            >
                            <span class="text-[11px] text-slate-400 mt-1 block">Format: <strong>YYYY/YYYY</strong> (misal 2028/2029)</span>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Status Awal *</label>
                            <select name="status" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-blue-200 font-medium">
                                <option value="Non-Aktif" selected>Non-Aktif (Sebagai Periode Mendatang / Arsip)</option>
                                <option value="Aktif">Aktif (Jadikan Basis Data Utama Sekarang)</option>
                            </select>
                            <span class="text-[11px] text-amber-600 mt-1 block font-medium">Catatan: Jika memilih Aktif, tahun akademik lain otomatis dinonaktifkan.</span>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalAddTA = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white cursor-pointer shadow-xs">Simpan Tahun Akademik</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah <span x-text="addTabName"></span></h3>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.master-data.store') }}" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        <input type="hidden" name="type" :value="activeTab">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Kode *</label>
                            <input type="text" name="kode" required placeholder="Contoh: SP-07" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama *</label>
                            <input type="text" name="nama" required placeholder="Nama item" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Deskripsi</label>
                            <textarea name="deskripsi" rows="2" placeholder="Keterangan singkat..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                <option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalAdd = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Item</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form :action="selectedItem ? '/admin/master-data/' + selectedItem.id : ''" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="type" :value="activeTab">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Kode</label>
                            <input type="text" name="kode" :value="selectedItem ? selectedItem.kode : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama</label>
                            <input type="text" name="nama" :value="selectedItem ? selectedItem.nama : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Deskripsi</label>
                            <textarea name="deskripsi" rows="2" :value="selectedItem ? selectedItem.deskripsi : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" :value="selectedItem ? selectedItem.status : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none">
                                <option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
