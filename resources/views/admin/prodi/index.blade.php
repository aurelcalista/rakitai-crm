@php $pageTitle = 'Data Program Studi'; @endphp

<x-app-layout :title="'Data Prodi - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: false, modalEdit: false, modalDetail: false,
        selectedProdi: null, searchQuery: '', filterFakultas: 'all', filterJenjang: 'all',
        currentPage: 1,
        perPage: 25,
        prodi: {{ json_encode($prodi) }},
        get filtered() {
            return this.prodi.filter(p => {
                const q = this.searchQuery.toLowerCase();
                const mQ = !q || p.kode.toLowerCase().includes(q) || p.nama.toLowerCase().includes(q) || p.fakultas.toLowerCase().includes(q);
                const mF = this.filterFakultas === 'all' || p.fakultas === this.filterFakultas;
                const mJ = this.filterJenjang === 'all' || p.jenjang === this.filterJenjang;
                return mQ && mF && mJ;
            });
        },
        get paginated() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filtered.length / this.perPage) || 1;
        }
    }" x-effect="searchQuery; filterFakultas; filterJenjang; currentPage = 1">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Program Studi</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola daftar program studi UCIC yang digunakan dalam data prospek dan kunjungan.</p>
            </div>
            <button type="button" @click="modalAdd = true" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Prodi</span>
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-3 gap-3">
            @foreach([['label'=>'S1','color'=>'blue'],['label'=>'D3','color'=>'teal'],['label'=>'S2','color'=>'purple']] as $j)
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs text-center">
                <div class="text-2xl font-extrabold text-{{ $j['color'] }}-700">{{ $prodi->where('jenjang', $j['label'])->count() }}</div>
                <p class="text-xs font-bold text-slate-500 mt-1">Prodi {{ $j['label'] }}</p>
            </div>
            @endforeach
        </div>

        <!-- Filter -->
        <div class="crm-card bg-white p-4 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="searchQuery" placeholder="Cari nama prodi, kode, atau fakultas..." class="w-full text-xs pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-200">
            </div>
            <select x-model="filterFakultas" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-64">
                <option value="all">Semua Fakultas</option>
                @foreach($fakultasList as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
            </select>
            <select x-model="filterJenjang" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-28">
                <option value="all">Semua Jenjang</option>
                @foreach($jenjangList as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
            </select>
        </div>

        <!-- Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Kode / Nama Prodi</th>
                            <th class="py-3.5 px-3">Fakultas</th>
                            <th class="py-3.5 px-3 text-center">Jenjang</th>
                            <th class="py-3.5 px-3 text-center">Kuota</th>
                            <th class="py-3.5 px-3 text-center">Terdaftar</th>
                            <th class="py-3.5 px-3">UKT</th>
                            <th class="py-3.5 px-3">UKT Reguler</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(p, index) in paginated" :key="p.id + '-' + currentPage">
                            <tr class="hover:bg-slate-50/80 transition crm-table-slide">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900" x-text="p.nama"></div>
                                    <div class="text-[11px] font-mono text-purple-600 mt-0.5" x-text="p.kode"></div>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600 font-medium text-[11px]" x-text="p.fakultas"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold"
                                        :class="{
                                            'bg-blue-50 text-blue-700 border border-blue-200': p.jenjang === 'S1',
                                            'bg-teal-50 text-teal-700 border border-teal-200': p.jenjang === 'D3',
                                            'bg-purple-50 text-purple-700 border border-purple-200': p.jenjang === 'S2'
                                        }"
                                        x-text="p.jenjang"></span>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800" x-text="p.kuota + ' mhs'"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="font-bold" :class="p.terdaftar >= p.kuota ? 'text-red-600' : 'text-emerald-700'" x-text="p.terdaftar + ' mhs'"></span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-500" x-text="p.ukt || p.spp"></td>
                                <td class="py-3.5 px-3 text-slate-500" x-text="p.ukt_reguler || '-'"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="p.status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="p.status === 'Aktif' ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        <span x-text="p.status"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="selectedProdi = p; modalDetail = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer">Detail</button>
                                        <button @click="selectedProdi = p; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold cursor-pointer">Edit</button>
                                        <form :action="'/admin/prodi/' + p.id + '/toggle-status'" method="POST" class="inline" @submit="if(!confirm(p.status === 'Aktif' ? 'Nonaktifkan prodi ini?' : 'Aktifkan prodi ini?')) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer"
                                                :class="p.status === 'Aktif' ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'"
                                                x-text="p.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'"></button>
                                        </form>
                                        <form :action="'/admin/prodi/' + p.id" method="POST" class="inline" @submit="if(!confirm('Hapus prodi ini?')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filtered.length === 0">
                            <td colspan="10" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada program studi yang cocok.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Control -->
            <x-table-pagination
                total="filtered.length"
                page="currentPage"
                perPage="perPage"
                totalPages="totalPages"
                color="purple"
            />
        </div>

        <!-- MODAL DETAIL PRODI -->
        <div x-show="modalDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalDetail = false"></div>
                <div class="inline-block w-full max-w-md bg-white shadow-2xl rounded-2xl relative z-10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-start">
                        <div>
                            <p class="text-[11px] font-mono text-purple-600 font-bold" x-text="selectedProdi ? selectedProdi.kode : ''"></p>
                            <h3 class="text-base font-bold text-slate-900" x-text="selectedProdi ? selectedProdi.nama : ''"></h3>
                        </div>
                        <button @click="modalDetail = false" class="text-slate-400 hover:text-slate-600 cursor-pointer mt-1"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <template x-if="selectedProdi">
                        <div class="p-6 space-y-3 text-xs">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-slate-400 mb-0.5">Jenjang</p>
                                    <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-bold"
                                        :class="{
                                            'bg-blue-50 text-blue-700 border border-blue-200': selectedProdi.jenjang === 'S1',
                                            'bg-teal-50 text-teal-700 border border-teal-200': selectedProdi.jenjang === 'D3',
                                            'bg-purple-50 text-purple-700 border border-purple-200': selectedProdi.jenjang === 'S2'
                                        }"
                                        x-text="selectedProdi.jenjang">
                                    </span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-slate-400 mb-0.5">Status</p>
                                    <p class="font-bold" :class="selectedProdi.status === 'Aktif' ? 'text-emerald-600' : 'text-red-600'" x-text="selectedProdi.status"></p>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-purple-50 border border-purple-100">
                                <p class="text-purple-500 mb-0.5">Fakultas</p>
                                <p class="font-bold text-slate-800" x-text="selectedProdi.fakultas"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-xl bg-blue-50 border border-blue-100 text-center">
                                    <p class="text-blue-500 mb-1">Kuota</p>
                                    <p class="text-2xl font-extrabold text-blue-700" x-text="selectedProdi.kuota"></p>
                                    <p class="text-[10px] text-blue-400">mahasiswa</p>
                                </div>
                                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-center">
                                    <p class="text-emerald-500 mb-1">Terdaftar</p>
                                    <p class="text-2xl font-extrabold" :class="selectedProdi.terdaftar >= selectedProdi.kuota ? 'text-red-600' : 'text-emerald-700'" x-text="selectedProdi.terdaftar"></p>
                                    <p class="text-[10px] text-emerald-400">mahasiswa</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex flex-col">
                                    <span class="text-slate-500">UKT</span>
                                    <span class="font-bold text-slate-800" x-text="selectedProdi.ukt || selectedProdi.spp || '-'"></span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex flex-col">
                                    <span class="text-slate-500">UKT Reguler</span>
                                    <span class="font-bold text-slate-800" x-text="selectedProdi.ukt_reguler || '-'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2">
                        <button @click="modalDetail = false; modalEdit = true" class="px-4 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white cursor-pointer">Edit</button>
                        <button @click="modalDetail = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH PRODI -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Program Studi</h3>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.prodi.store') }}" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kode Prodi *</label>
                                <input type="text" name="kode" required placeholder="TI-S1" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Jenjang *</label>
                                <select name="jenjang" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Jenjang</option>
                                    @foreach($jenjangList as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Program Studi *</label>
                            <input type="text" name="nama" required placeholder="Contoh: S1 Teknik Informatika" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                        </div>
                        <div x-data="{ modeManualFakultas: false }">
                            <label class="flex justify-between items-end font-semibold text-slate-700 mb-1">
                                <span>Fakultas *</span>
                                <button type="button" @click="modeManualFakultas = !modeManualFakultas" class="text-[10px] text-purple-600 hover:underline" x-text="modeManualFakultas ? 'Pilih dari List' : 'Input Manual'"></button>
                            </label>
                            <select x-show="!modeManualFakultas" name="fakultas" :required="!modeManualFakultas" :disabled="modeManualFakultas" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                <option value="">Pilih Fakultas</option>
                                @foreach($fakultasList as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                            </select>
                            <input x-show="modeManualFakultas" type="text" name="fakultas" :required="modeManualFakultas" :disabled="!modeManualFakultas" placeholder="Ketik nama fakultas baru..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Target Kuota</label>
                                <input type="number" name="kuota" required placeholder="100" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">UKT</label>
                                <input type="text" name="ukt" placeholder="Rp 4.500.000" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">UKT Reguler</label>
                                <input type="text" name="ukt_reguler" placeholder="Rp 5.500.000" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalAdd = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white">Simpan Prodi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT PRODI -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Program Studi</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form :action="selectedProdi ? '/admin/prodi/' + selectedProdi.id : ''" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kode Prodi</label>
                                <input type="text" name="kode" required :value="selectedProdi ? selectedProdi.kode : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Jenjang</label>
                                <select name="jenjang" required :value="selectedProdi ? selectedProdi.jenjang : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none">
                                    <option value="">Pilih Jenjang</option>
                                    @foreach($jenjangList as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Prodi</label>
                            <input type="text" name="nama" required :value="selectedProdi ? selectedProdi.nama : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                        </div>
                        <div x-data="{ modeManualFakultasEdit: false }">
                            <label class="flex justify-between items-end font-semibold text-slate-700 mb-1">
                                <span>Fakultas *</span>
                                <button type="button" @click="modeManualFakultasEdit = !modeManualFakultasEdit" class="text-[10px] text-purple-600 hover:underline" x-text="modeManualFakultasEdit ? 'Pilih dari List' : 'Input Manual'"></button>
                            </label>
                            <select x-show="!modeManualFakultasEdit" name="fakultas" :required="!modeManualFakultasEdit" :disabled="modeManualFakultasEdit" :value="selectedProdi ? selectedProdi.fakultas : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                <option value="">Pilih Fakultas</option>
                                @foreach($fakultasList as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                            </select>
                            <input x-show="modeManualFakultasEdit" type="text" name="fakultas" :required="modeManualFakultasEdit" :disabled="!modeManualFakultasEdit" :value="selectedProdi ? selectedProdi.fakultas : ''" placeholder="Ketik nama fakultas baru..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kuota</label>
                                <input type="number" name="kuota" required :value="selectedProdi ? selectedProdi.kuota : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">UKT</label>
                                <input type="text" name="ukt" :value="selectedProdi ? (selectedProdi.ukt || selectedProdi.spp) : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">UKT Reguler</label>
                                <input type="text" name="ukt_reguler" :value="selectedProdi ? selectedProdi.ukt_reguler : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" :value="selectedProdi ? selectedProdi.status : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
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
