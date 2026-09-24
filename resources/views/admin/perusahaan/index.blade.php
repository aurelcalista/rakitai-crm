@php $pageTitle = 'Data Perusahaan'; @endphp

<x-app-layout :title="'Data Perusahaan - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: false, modalEdit: false, modalDetail: false,
        selectedPrs: null, searchQuery: '', filterWilayah: 'all', filterKategori: 'all', filterStatus: 'all',
        perusahaan: {{ json_encode($perusahaan) }},
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
            return this.perusahaan.filter(p => {
                const q = this.searchQuery.toLowerCase();
                const mQ = !q || (p.kode && p.kode.toLowerCase().includes(q)) || (p.nama && p.nama.toLowerCase().includes(q)) || (p.pic_name && p.pic_name.toLowerCase().includes(q));
                const mW = this.filterWilayah === 'all' || p.wilayah_nama === this.filterWilayah;
                const mK = this.filterKategori === 'all' || (p.kategori_nama && p.kategori_nama.toLowerCase() === this.filterKategori.toLowerCase());
                const mS = this.filterStatus === 'all' || p.status.toLowerCase() === this.filterStatus.toLowerCase();
                return mQ && mW && mK && mS;
            });
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Perusahaan</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola database perusahaan/korporasi mitra — untuk kelas karyawan, magang, dan kemitraan industri.</p>
            </div>
            <button type="button" @click="modalAdd = true" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Perusahaan</span>
            </button>
        </div>

        <!-- Filter -->
        <div class="crm-card bg-white p-4 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="searchQuery" placeholder="Cari nama perusahaan, PIC..." class="w-full text-xs pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-200">
            </div>
            <select x-model="filterKategori" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-44">
                <option value="all">Semua Kategori</option>
                @foreach($kategoriList as $k)<option value="{{ strtolower($k->nama) }}">{{ $k->nama }}</option>@endforeach
            </select>
            <select x-model="filterWilayah" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-52">
                <option value="all">Semua Wilayah</option>
                @foreach($wilayahList as $w)<option value="{{ $w->kode . ' ' . $w->nama }}">{{ $w->kode . ' ' . $w->nama }}</option>@endforeach
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
                            <th class="py-3.5 px-4">Nama Perusahaan</th>
                            <th class="py-3.5 px-3">Kategori</th>
                            <th class="py-3.5 px-3">Wilayah / Kecamatan</th>
                            <th class="py-3.5 px-3 text-center">Kunjungan</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="p in filtered" :key="p.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900" x-text="p.nama"></div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                        :class="{
                                            'bg-blue-50 text-blue-700 border-blue-200': p.kategori_nama === 'Teknologi',
                                            'bg-purple-50 text-purple-700 border-purple-200': p.kategori_nama === 'Pendidikan',
                                            'bg-amber-50 text-amber-700 border-amber-200': p.kategori_nama === 'Manufaktur',
                                            'bg-teal-50 text-teal-700 border-teal-200': p.kategori_nama === 'Jasa',
                                            'bg-red-50 text-red-700 border-red-200': p.kategori_nama === 'Kesehatan',
                                            'bg-slate-100 text-slate-700 border-slate-200': p.kategori_nama === 'Pemerintahan'
                                        }"
                                        x-text="p.kategori_nama"></span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-semibold text-slate-700 text-[11px]" x-text="p.wilayah_nama"></div>
                                    <div class="text-slate-400 text-[11px]" x-text="'Kec. ' + p.kecamatan"></div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="font-bold text-slate-700" x-text="p.jumlah_kunjungan + 'x'"></span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="p.status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="p.status === 'Aktif' ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        <span x-text="p.status"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="selectedPrs = p; modalDetail = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer">Detail</button>
                                        <button @click="selectedPrs = p; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold cursor-pointer">Edit</button>
                                        <form :action="'/admin/perusahaan/' + p.id + '/toggle-status'" method="POST" class="inline" @submit="if(!confirm(p.status === 'Aktif' ? 'Nonaktifkan perusahaan ini?' : 'Aktifkan perusahaan ini?')) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer"
                                                :class="p.status === 'Aktif' ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'"
                                                x-text="p.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'"></button>
                                        </form>
                                        <form :action="'/admin/perusahaan/' + p.id" method="POST" class="inline" @submit="if(!confirm('Hapus perusahaan ini?')) $event.preventDefault()">
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
        </div>

        <!-- MODAL DETAIL PERUSAHAAN -->
        <div x-show="modalDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalDetail = false"></div>
                <div class="inline-block w-full max-w-lg bg-white shadow-2xl rounded-2xl relative z-10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-start">
                        <div>
                            <h3 class="text-base font-bold text-slate-900" x-text="selectedPrs ? selectedPrs.nama : ''"></h3>
                        </div>
                        <button @click="modalDetail = false" class="text-slate-400 hover:text-slate-600 cursor-pointer mt-1"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <template x-if="selectedPrs">
                        <div class="p-6 space-y-3 text-xs overflow-y-auto max-h-[60vh]">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Kategori</p><p class="font-bold text-slate-800" x-text="selectedPrs.kategori_nama"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Status</p>
                                    <p class="font-bold" :class="selectedPrs.status === 'Aktif' ? 'text-emerald-600' : 'text-red-600'" x-text="selectedPrs.status"></p>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-slate-400 mb-0.5">Alamat</p><p class="font-semibold text-slate-800" x-text="selectedPrs.alamat"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Wilayah</p><p class="font-bold text-slate-800 text-[11px]" x-text="selectedPrs.wilayah_nama"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Kecamatan</p><p class="font-bold text-slate-800" x-text="selectedPrs.kecamatan"></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                                    <p class="text-blue-500 mb-0.5">Telepon</p><p class="font-bold text-slate-800" x-text="selectedPrs.telepon || '-'"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                                    <p class="text-blue-500 mb-0.5">Email</p><p class="font-bold text-slate-800 truncate" x-text="selectedPrs.email || '-'"></p>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-between">
                                <span class="text-emerald-700 font-semibold">Total Kunjungan</span>
                                <span class="text-2xl font-extrabold text-emerald-700" x-text="selectedPrs.jumlah_kunjungan + 'x'"></span>
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

        <!-- MODAL TAMBAH PERUSAHAAN -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-4xl p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Perusahaan Baru</h3>
                        <button @click="modalAdd = false" type="button" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.perusahaan.store') }}" method="POST" class="mt-4 space-y-3 text-xs px-1">
                        @csrf

                        {{-- Baris 1: Nama | Kategori | Wilayah --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Perusahaan *</label>
                                <input type="text" name="nama" required placeholder="PT Bina Karya Nusantara" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kategori *</label>
                                <select name="kategori_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Kategori</option>
                                    @foreach($kategoriList as $k)<option value="{{ $k->id }}">{{ $k->nama }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Wilayah *</label>
                                <select name="wilayah_id" required @change="onAddWilayahChange($event.target.value)" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Wilayah</option>
                                    @foreach($wilayahList as $w)<option value="{{ $w->id }}">{{ $w->kode . ' ' . $w->nama }}</option>@endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Baris 2: Kecamatan | Alamat | Telepon | Email --}}
                        <div class="grid grid-cols-4 gap-3">
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
                                <input type="text" name="alamat" placeholder="Jalan, No, dsb" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Telepon</label>
                                <input type="text" name="telepon" placeholder="0231-XXXXXX" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" placeholder="hrd@perusahaan.co.id" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>

                        {{-- Baris 3: Website --}}
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Website</label>
                                <input type="text" name="website" placeholder="www.perusahaan.co.id" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <div>
                                <label class="font-semibold text-slate-700 mr-2">Status</label>
                                <select name="status" class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white outline-none"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select>
                            </div>
                            <div class="flex gap-3">
                                <button type="button" @click="modalAdd = false" class="px-5 py-2.5 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                                <button type="submit" class="px-5 py-2.5 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white cursor-pointer shadow-sm">Simpan Perusahaan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT PERUSAHAAN -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-4xl p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Perusahaan</h3>
                        <button @click="modalEdit = false" type="button" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <template x-if="selectedPrs">
                    <form :action="'/admin/perusahaan/' + selectedPrs.id" method="POST" class="mt-4 space-y-3 text-xs px-1">
                        @csrf
                        @method('PUT')

                        {{-- Baris 1: Nama | Kategori | Wilayah --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Perusahaan *</label>
                                <input type="text" name="nama" required :value="selectedPrs.nama" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kategori *</label>
                                <select name="kategori_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Kategori</option>
                                    @foreach($kategoriList as $k)
                                        <option value="{{ $k->id }}" :selected="selectedPrs.kategori_id == {{ $k->id }}">{{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Wilayah *</label>
                                <select name="wilayah_id" required
                                    x-init="onEditWilayahChange(selectedPrs.wilayah_id)"
                                    @change="onEditWilayahChange($event.target.value)"
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="">Pilih Wilayah</option>
                                    @foreach($wilayahList as $w)
                                        <option value="{{ $w->id }}" :selected="selectedPrs.wilayah_id == {{ $w->id }}">{{ $w->kode . ' ' . $w->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Baris 2: Kecamatan | Alamat | Telepon | Email --}}
                        <div class="grid grid-cols-4 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kecamatan</label>
                                <template x-if="editKecList.length > 0">
                                    <select name="kecamatan" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                        <option value="">Pilih Kecamatan</option>
                                        <template x-for="kec in editKecList" :key="kec">
                                            <option :value="kec" :selected="kec === selectedPrs.kecamatan" x-text="kec"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="editKecList.length === 0">
                                    <input type="text" name="kecamatan" :value="selectedPrs.kecamatan" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                                </template>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
                                <input type="text" name="alamat" :value="selectedPrs.alamat" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Telepon</label>
                                <input type="text" name="telepon" :value="selectedPrs.telepon" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" :value="selectedPrs.email" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>

                        {{-- Baris 3: Website --}}
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Website</label>
                                <input type="text" name="website" :value="selectedPrs.website" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <div>
                                <label class="font-semibold text-slate-700 mr-2">Status</label>
                                <select name="status" class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white outline-none">
                                    <option value="Aktif" :selected="selectedPrs.status === 'Aktif'">Aktif</option>
                                    <option value="Nonaktif" :selected="selectedPrs.status === 'Nonaktif'">Nonaktif</option>
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
