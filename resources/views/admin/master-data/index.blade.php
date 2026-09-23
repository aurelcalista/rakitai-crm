@php $pageTitle = 'Data Master'; @endphp

<x-app-layout :title="'Data Master - CRM UCIC'">

    <div class="space-y-6" x-data="{
        activeTab: 'status_prospek',
        modalAdd: false,
        modalEdit: false,
        addTabName: '',
        selectedItem: null,
        tabs: [
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
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Konfigurasi pilihan status, kategori, dan sumber yang digunakan di seluruh modul CRM.</p>
            </div>
            <button type="button" @click="addTabName = tabs.find(t=>t.key===activeTab)?.label; modalAdd = true"
                class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Item</span>
            </button>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
            <div class="flex min-w-max">
                <template x-for="tab in tabs" :key="tab.key">
                    <button type="button"
                        @click="activeTab = tab.key"
                        :class="activeTab === tab.key ? 'border-purple-600 text-purple-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="py-3.5 px-4 text-xs font-semibold transition cursor-pointer whitespace-nowrap border-b-2"
                        x-text="tab.label"></button>
                </template>
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
                    💡 Untuk mengelola <strong>Kuota Target, SPP, dan Status Prodi</strong>, silakan gunakan modul khusus <strong>Data Prodi</strong>.
                </div>
                <a href="{{ route('admin.prodi.index') }}" class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shrink-0 shadow-xs">
                    Kelola Kuota & SPP Prodi &rarr;
                </a>
            </div>
            @include('admin.master-data._table', ['items' => $masterData['program_studi'], 'tabLabel' => 'Program Studi'])
        </div>

        <!-- Tab Content: Jenjang -->
        <div x-show="activeTab === 'jenjang'" x-cloak class="crm-card bg-white overflow-hidden">
            @include('admin.master-data._table', ['items' => $masterData['jenjang'], 'tabLabel' => 'Jenjang'])
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
