@php $pageTitle = 'Data Sekolah'; @endphp

<x-app-layout :title="'Data Sekolah - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: false, modalEdit: false, modalDetail: false,
        selectedSkl: null, searchQuery: '', filterWilayah: 'all', filterStatus: 'all',
        currentPage: 1, perPage: 25,
        sekolah: {{ json_encode($sekolah) }},
        wilayahData: {{ json_encode($wilayahList->map(fn($w) => ['id' => $w->id, 'nama' => $w->nama, 'kecamatans' => $w->children->pluck('nama')->toArray()])) }},
        addKecList: [],
        editKecList: [],
        onAddWilayahChange(id) {
            const w = this.wilayahData.find(x => x.id == id);
            this.addKecList = w ? (w.kecamatans || []) : [];
        },
        onEditWilayahChange(id) {
            const w = this.wilayahData.find(x => x.id == id);
            this.editKecList = w ? (w.kecamatans || []) : [];
        },
        get filtered() {
            return this.sekolah.filter(s => {
                const q = this.searchQuery.toLowerCase();
                const mQ = !q || s.kode.toLowerCase().includes(q) || s.nama.toLowerCase().includes(q) || s.kecamatan.toLowerCase().includes(q) || s.pic.toLowerCase().includes(q);
                const mW = this.filterWilayah === 'all' || s.wilayah_nama === this.filterWilayah;
                const mS = this.filterStatus === 'all' || s.status.toLowerCase() === this.filterStatus.toLowerCase();
                return mQ && mW && mS;
            });
        },
        get paginatedList() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filtered.length / this.perPage) || 1;
        }
    }" x-effect="searchQuery; filterWilayah; filterStatus; currentPage = 1">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Sekolah</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola database sekolah mitra — digunakan Sales saat input kunjungan & prospek.</p>
            </div>
            <button type="button" @click="modalAdd = true" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Sekolah</span>
            </button>
        </div>

        <!-- Filter -->
        <div class="crm-card bg-white p-4 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="searchQuery" placeholder="Cari nama sekolah, kecamatan, PIC..." class="w-full text-xs pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-200">
            </div>
            <select x-model="filterWilayah" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-52">
                <option value="all">Semua Wilayah</option>
                @foreach($wilayahList as $w)<option value="{{ $w->nama }}">{{ $w->nama }}</option>@endforeach
            </select>
            <select x-model="filterStatus" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-36">
                <option value="all">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </div>

        <!-- Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Sekolah</th>
                            <th class="py-3.5 px-3">Kategori</th>
                            <th class="py-3.5 px-3">Wilayah / Kecamatan</th>
                            <th class="py-3.5 px-3">Telepon</th>
                            <th class="py-3.5 px-3 text-center">Kunjungan</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(s, index) in paginatedList" :key="s.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900" x-text="s.nama"></div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200" x-text="s.kategori_nama"></span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-semibold text-slate-700 text-[11px]" x-text="s.wilayah_nama"></div>
                                    <div class="text-slate-400 text-[11px]" x-text="'Kec. ' + s.kecamatan"></div>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600" x-text="s.telepon"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="font-bold text-slate-700" x-text="s.jumlah_kunjungan + 'x'"></span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="s.status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="s.status === 'Aktif' ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        <span x-text="s.status"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="selectedSkl = s; modalDetail = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer">Detail</button>
                                        <button @click="selectedSkl = s; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold cursor-pointer">Edit</button>
                                        <form :action="'/admin/sekolah/' + s.id + '/toggle-status'" method="POST" class="inline" @submit="if(!confirm(s.status === 'Aktif' ? 'Nonaktifkan sekolah ini?' : 'Aktifkan sekolah ini?')) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer"
                                                :class="s.status === 'Aktif' ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'"
                                                x-text="s.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'"></button>
                                        </form>
                                        <form :action="'/admin/sekolah/' + s.id" method="POST" class="inline" @submit="if(!confirm('Hapus sekolah ini?')) $event.preventDefault()">
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
                    </tbody>
                </table>
            </div>
            <x-table-pagination total="filtered.length" page="currentPage" perPage="perPage" totalPages="totalPages" color="purple" />
        </div>

        <!-- MODAL DETAIL SEKOLAH -->
        <div x-show="modalDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalDetail = false"></div>
                <div class="inline-block w-full max-w-lg bg-white shadow-2xl rounded-2xl relative z-10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-start">
                        <div>
                            <h3 class="text-base font-bold text-slate-900" x-text="selectedSkl ? selectedSkl.nama : ''"></h3>
                        </div>
                        <button @click="modalDetail = false" class="text-slate-400 hover:text-slate-600 cursor-pointer mt-1"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <template x-if="selectedSkl">
                        <div class="p-6 space-y-3 text-xs overflow-y-auto max-h-[60vh]">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Kategori</p><p class="font-bold text-slate-800" x-text="selectedSkl.kategori_nama"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Wilayah</p><p class="font-bold text-slate-800" x-text="selectedSkl.wilayah_nama"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Kecamatan</p><p class="font-bold text-slate-800" x-text="selectedSkl.kecamatan"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Status</p>
                                    <p class="font-bold" :class="selectedSkl.status === 'Aktif' ? 'text-emerald-600' : 'text-red-600'" x-text="selectedSkl.status"></p>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-slate-400 mb-0.5">Alamat</p><p class="font-semibold text-slate-800" x-text="selectedSkl.alamat"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                                    <p class="text-blue-500 mb-0.5">Telepon</p><p class="font-bold text-slate-800" x-text="selectedSkl.telepon || '-'"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                                    <p class="text-blue-500 mb-0.5">Email</p><p class="font-bold text-slate-800 truncate" x-text="selectedSkl.email || '-'"></p>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-between">
                                <span class="text-emerald-700 font-semibold">Total Kunjungan</span>
                                <span class="text-2xl font-extrabold text-emerald-700" x-text="selectedSkl.jumlah_kunjungan + 'x'"></span>
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

        <!-- MODAL TAMBAH SEKOLAH -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-3xl p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Sekolah Baru</h3>
                        <button @click="modalAdd = false" type="button" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.sekolah.store') }}" method="POST" class="mt-4 space-y-3 text-xs px-1">
                        @csrf

                        {{-- Baris 1: Nama | Kategori --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Sekolah *</label>
                                <input type="text" name="nama" required placeholder="SMA Negeri 5 Cirebon" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kategori *</label>
                                <select name="kategori_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Kategori</option>
                                    @foreach($kategoriList as $kat)<option value="{{ $kat->id }}">{{ $kat->nama }}</option>@endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Baris 2: Wilayah | Kecamatan | Alamat --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Wilayah *</label>
                                <select name="wilayah_id" required @change="onAddWilayahChange($event.target.value)" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Wilayah</option>
                                    @foreach($wilayahList as $w)<option value="{{ $w->id }}">{{ $w->nama }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kecamatan</label>
                                <template x-if="addKecList.length > 0">
                                    <select name="kecamatan" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                        <option value="">Pilih Kecamatan</option>
                                        <template x-for="kec in addKecList" :key="kec">
                                            <option :value="kec" x-text="kec"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="addKecList.length === 0">
                                    <input type="text" name="kecamatan" placeholder="Pilih wilayah dulu" disabled class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-400 outline-none cursor-not-allowed">
                                </template>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
                                <input type="text" name="alamat" placeholder="Jalan, RT/RW, dsb" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>

                        {{-- Baris 3: Telepon | Email | Website | Status --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Telepon</label>
                                <input type="text" name="telepon" placeholder="0231-XXXXXX" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" placeholder="email@sekolah.sch.id" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Website</label>
                                <input type="text" name="website" placeholder="www.sekolah.sch.id" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <div>
                                <label class="font-semibold text-slate-700 mr-2">Status</label>
                                <select name="status" class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white outline-none"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select>
                            </div>
                            <div class="flex gap-3">
                                <button type="button" @click="modalAdd = false" class="px-5 py-2.5 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                                <button type="submit" class="px-5 py-2.5 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white cursor-pointer shadow-sm">Simpan Data Sekolah</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT SEKOLAH -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-3xl p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Sekolah</h3>
                        <button @click="modalEdit = false" type="button" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <template x-if="selectedSkl">
                    <form :action="'/admin/sekolah/' + selectedSkl.id" method="POST" class="mt-4 space-y-3 text-xs px-1">
                        @csrf
                        @method('PUT')

                        {{-- Baris 1: Nama | Kategori --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Sekolah *</label>
                                <input type="text" name="nama" :value="selectedSkl.nama" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kategori *</label>
                                <select name="kategori_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Kategori</option>
                                    @foreach($kategoriList as $kat)
                                        <option value="{{ $kat->id }}" :selected="selectedSkl.kategori_id == {{ $kat->id }}">{{ $kat->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Baris 2: Wilayah | Kecamatan | Alamat --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Wilayah *</label>
                                <select name="wilayah_id" required
                                    x-init="onEditWilayahChange(selectedSkl.wilayah_id)"
                                    @change="onEditWilayahChange($event.target.value)"
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Wilayah</option>
                                    @foreach($wilayahList as $w)
                                        <option value="{{ $w->id }}" :selected="selectedSkl.wilayah_id == {{ $w->id }}">{{ $w->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kecamatan</label>
                                <template x-if="editKecList.length > 0">
                                    <select name="kecamatan" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                        <option value="">Pilih Kecamatan</option>
                                        <template x-for="kec in editKecList" :key="kec">
                                            <option :value="kec" :selected="kec === selectedSkl.kecamatan" x-text="kec"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="editKecList.length === 0">
                                    <input type="text" name="kecamatan" :value="selectedSkl.kecamatan" placeholder="Ketik kecamatan" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                                </template>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
                                <input type="text" name="alamat" :value="selectedSkl.alamat" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>

                        {{-- Baris 3: Telepon | Email | Website --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Telepon</label>
                                <input type="text" name="telepon" :value="selectedSkl.telepon" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" :value="selectedSkl.email" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Website</label>
                                <input type="text" name="website" :value="selectedSkl.website" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <div>
                                <label class="font-semibold text-slate-700 mr-2">Status</label>
                                <select name="status" class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white outline-none">
                                    <option value="Aktif" :selected="selectedSkl.status === 'Aktif'">Aktif</option>
                                    <option value="Nonaktif" :selected="selectedSkl.status === 'Nonaktif'">Nonaktif</option>
                                </select>
                            </div>
                            <div class="flex gap-3">
                                <button type="button" @click="modalEdit = false" class="px-5 py-2.5 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                                <button type="submit" class="px-5 py-2.5 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white cursor-pointer shadow-sm">Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>
                    </template>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
