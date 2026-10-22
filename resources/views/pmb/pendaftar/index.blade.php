@php
    $pageTitle = 'Data Pendaftar';
    $pageSubtitle = 'Database Calon Mahasiswa Baru PMB UCIC';
    $userRole = strtolower(auth()->user()->role ?? '');
@endphp

<x-app-layout :title="'Data Pendaftar PMB - CRM UCIC'">
    <div class="space-y-6" x-data="{
        editModalOpen: false,
        deleteModalOpen: false,
        selectedPendaftar: {},
        deleteUrl: '',
        openEdit(p) {
            this.selectedPendaftar = Object.assign({}, p);
            this.editModalOpen = true;
        },
        openDelete(id, name) {
            this.selectedPendaftar = { id: id, name: name };
            this.deleteUrl = '{{ url('pmb/pendaftar') }}/' + id;
            this.deleteModalOpen = true;
        }
    }">

        <!-- Filter Bar Card (Matching PMB Screenshot) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <form action="{{ route('pmb.pendaftar.index') }}" method="GET" id="filterForm">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- 1. Semua Status Formulir -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status Formulir</label>
                        <select name="status_formulir" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-medium rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="all" {{ request('status_formulir', 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                            <option value="Belum Isi" {{ request('status_formulir') === 'Belum Isi' ? 'selected' : '' }}>Belum Isi</option>
                            <option value="Sudah Isi" {{ request('status_formulir') === 'Sudah Isi' ? 'selected' : '' }}>Sudah Isi</option>
                        </select>
                    </div>

                    <!-- 2. Semua Status Ujian -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status Ujian</label>
                        <select name="status_ujian" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-medium rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="all" {{ request('status_ujian', 'all') === 'all' ? 'selected' : '' }}>Semua Status Ujian</option>
                            <option value="Belum Mengerjakan" {{ request('status_ujian') === 'Belum Mengerjakan' ? 'selected' : '' }}>Belum Mengerjakan</option>
                            <option value="Sudah Mengerjakan" {{ request('status_ujian') === 'Sudah Mengerjakan' ? 'selected' : '' }}>Sudah Mengerjakan</option>
                        </select>
                    </div>

                    <!-- 3. Semua Gelombang -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Gelombang Pendaftaran</label>
                        <select name="gelombang" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-medium rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="all" {{ request('gelombang', 'all') === 'all' ? 'selected' : '' }}>Semua Gelombang</option>
                            @foreach($gelombangList as $gel)
                                <option value="{{ $gel }}" {{ request('gelombang') === $gel ? 'selected' : '' }}>{{ $gel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 4. Semua Program Studi -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Program Studi</label>
                        <select name="prodi_id" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-medium rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="all" {{ request('prodi_id', 'all') === 'all' ? 'selected' : '' }}>Semua Program Studi</option>
                            @foreach($prodis as $pr)
                                <option value="{{ $pr->id }}" {{ (string)request('prodi_id') === (string)$pr->id ? 'selected' : '' }}>{{ $pr->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 pt-2">
                    <a 
                        href="{{ route('pmb.pendaftar.export', request()->query()) }}" 
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition"
                        title="Unduh data dalam format CSV / Excel"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Export</span>
                    </a>
                    
                    <a 
                        href="{{ route('pmb.pendaftar.index') }}" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-blue-600 hover:text-blue-800 hover:bg-blue-50 transition border border-blue-200"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset Filter</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- 4 Stat Cards (Matching PMB Screenshot) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total User -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-slate-900">{{ number_format($totalUser) }}</div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">TOTAL USER</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>

            <!-- 2. Belum Isi Formulir -->
            <div class="bg-white p-5 rounded-2xl border border-rose-100/90 shadow-xs flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-rose-600">{{ number_format($belumIsiFormulir) }}</div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">BELUM ISI FORMULIR</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <!-- 3. Belum Upload -->
            <div class="bg-white p-5 rounded-2xl border border-amber-100/90 shadow-xs flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-amber-600">{{ number_format($belumUploadBerkas) }}</div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">BELUM UPLOAD</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
            </div>

            <!-- 4. Sudah Upload -->
            <div class="bg-white p-5 rounded-2xl border border-emerald-100/90 shadow-xs flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-emerald-600">{{ number_format($sudahUploadBerkas) }}</div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">SUDAH UPLOAD</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Table Card: Data Pendaftar PMB -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Data Pendaftar PMB</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar calon mahasiswa baru terdaftar di sistem admisi</p>
                </div>

                <!-- Search Form -->
                <form action="{{ route('pmb.pendaftar.index') }}" method="GET" class="flex items-center gap-2 w-full md:w-auto">
                    <!-- Preserve existing filters -->
                    <input type="hidden" name="status_formulir" value="{{ request('status_formulir') }}">
                    <input type="hidden" name="status_ujian" value="{{ request('status_ujian') }}">
                    <input type="hidden" name="gelombang" value="{{ request('gelombang') }}">
                    <input type="hidden" name="prodi_id" value="{{ request('prodi_id') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">

                    <div class="relative w-full md:w-72">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Cari nama, email, HP..." 
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 pl-3 pr-9 py-2.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                        >
                        @if(request('search'))
                            <a href="{{ route('pmb.pendaftar.index', request()->except('search')) }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </a>
                        @endif
                    </div>
                    <button type="submit" class="p-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition cursor-pointer shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Total Indicator & Rows Per Page -->
            <div class="px-5 py-3 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <div>
                    Menampilkan <span class="font-bold text-slate-800">{{ $pendaftarList->firstItem() ?? 0 }} - {{ $pendaftarList->lastItem() ?? 0 }}</span> dari <span class="font-bold text-slate-800">{{ $pendaftarList->total() }}</span> data
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-slate-400">Tampilkan</span>
                    <select onchange="window.location.href = this.value" class="text-xs font-semibold rounded-lg border border-slate-200 bg-white py-1 px-2 text-slate-700">
                        @foreach([10, 25, 50, 100] as $sz)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $sz]) }}" {{ (int)request('per_page', 10) === $sz ? 'selected' : '' }}>{{ $sz }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="bg-blue-900 text-white text-[11px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-3 text-center w-12">NO</th>
                            <th class="py-3 px-4">NAMA LENGKAP</th>
                            <th class="py-3 px-3">NO. HP</th>
                            <th class="py-3 px-3">PROGRAM STUDI</th>
                            <th class="py-3 px-3">GELOMBANG</th>
                            <th class="py-3 px-3 text-center">STATUS FORMULIR</th>
                            <th class="py-3 px-3 text-center">STATUS BERKAS</th>
                            <th class="py-3 px-3 text-center">STATUS UJIAN</th>
                            <th class="py-3 px-2 text-center">MINAT KULIAH</th>
                            <th class="py-3 px-2 text-center">MINAT MINICLASS</th>
                            <th class="py-3 px-3 text-center">TANGGAL DAFTAR</th>
                            <th class="py-3 px-3 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($pendaftarList as $index => $p)
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- No -->
                                <td class="py-3.5 px-3 text-center font-semibold text-slate-400">
                                    {{ $pendaftarList->firstItem() + $index }}
                                </td>

                                <!-- Nama Lengkap + Email -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 leading-tight">
                                        <a href="{{ route('pmb.pendaftar.show', $p->id) }}" class="hover:text-blue-600 transition">
                                            {{ $p->name }}
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $p->email ?: '-' }}
                                    </div>
                                </td>

                                <!-- No HP -->
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    @php
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $p->whatsapp);
                                    @endphp
                                    <a 
                                        href="https://wa.me/{{ $cleanPhone }}" 
                                        target="_blank" 
                                        class="font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                                    >
                                        {{ $p->whatsapp }}
                                    </a>
                                </td>

                                <!-- Program Studi (Pills) -->
                                <td class="py-3.5 px-3">
                                    <div class="flex flex-col gap-1 items-start">
                                        @if($p->prodi)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                {{ $p->prodi->nama }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                - Belum Pilih
                                            </span>
                                        @endif

                                        @if($p->prodiPilihan2)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-cyan-50 text-cyan-800 border border-cyan-200">
                                                {{ $p->prodiPilihan2->nama }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Gelombang -->
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $p->gelombang ?: '- Data Lama' }}
                                    </span>
                                </td>

                                <!-- Status Formulir -->
                                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                    @if($p->status_formulir === 'Sudah Isi')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500 text-white">
                                            Sudah Isi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white">
                                            Belum Isi
                                        </span>
                                    @endif
                                </td>

                                <!-- Status Berkas -->
                                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                    @if($p->status_berkas === 'Sudah Upload')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Sudah Upload
                                        </span>
                                    @elseif($p->status_berkas === 'Pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-amber-950">
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-600 text-white">
                                            Belum Upload
                                        </span>
                                    @endif
                                </td>

                                <!-- Status Ujian -->
                                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                    @if($p->status_ujian === 'Sudah Mengerjakan')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">
                                            Sudah Mengerjakan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-600 text-white">
                                            Belum Mengerjakan
                                        </span>
                                    @endif
                                </td>

                                <!-- Minat Kuliah -->
                                <td class="py-3.5 px-2 text-center text-slate-700 font-medium lowercase">
                                    {{ $p->minat_kuliah ?: 'ya' }}
                                </td>

                                <!-- Minat Miniclass -->
                                <td class="py-3.5 px-2 text-center text-slate-700 font-medium lowercase">
                                    {{ $p->minat_miniclass ?: 'tidak' }}
                                </td>

                                <!-- Tanggal Daftar -->
                                <td class="py-3.5 px-3 text-center text-slate-600 whitespace-nowrap font-medium text-[11px]">
                                    {{ $p->created_at ? $p->created_at->format('d M Y') : '-' }}
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Lihat Detail -->
                                        <a 
                                            href="{{ route('pmb.pendaftar.show', $p->id) }}" 
                                            class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition border border-blue-200"
                                            title="Lihat Detail Pendaftar"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <!-- Edit Data -->
                                        <button 
                                            type="button"
                                            @click="openEdit({
                                                id: {{ $p->id }},
                                                name: '{{ addslashes($p->name) }}',
                                                email: '{{ addslashes($p->email ?? '') }}',
                                                whatsapp: '{{ $p->whatsapp }}',
                                                gelombang: '{{ addslashes($p->gelombang ?? '') }}',
                                                prodi_id: '{{ $p->prodi_id ?? '' }}',
                                                prodi_pilihan_2_id: '{{ $p->prodi_pilihan_2_id ?? '' }}',
                                                status_formulir: '{{ $p->status_formulir ?? 'Belum Isi' }}',
                                                status_berkas: '{{ $p->status_berkas ?? 'Belum Upload' }}',
                                                status_ujian: '{{ $p->status_ujian ?? 'Belum Mengerjakan' }}',
                                                nilai_ujian: '{{ $p->nilai_ujian ?? '' }}',
                                                minat_kuliah: '{{ $p->minat_kuliah ?? 'ya' }}',
                                                minat_miniclass: '{{ $p->minat_miniclass ?? 'tidak' }}'
                                            })"
                                            class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition border border-amber-200 cursor-pointer"
                                            title="Edit Data Pendaftar"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Hapus (Admin & HM only) -->
                                        @if(in_array($userRole, ['admin', 'hm']))
                                        <button 
                                            type="button"
                                            @click="openDelete({{ $p->id }}, '{{ addslashes($p->name) }}')"
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition border border-rose-200 cursor-pointer"
                                            title="Hapus Pendaftar"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-12 text-center text-slate-400">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <div class="font-bold text-slate-700 text-sm">Tidak Ada Data Pendaftar</div>
                                        <p class="text-xs text-slate-400">Tidak ada pendaftar yang cocok dengan filter atau kata kunci pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($pendaftarList->hasPages())
                <div class="p-4 border-t border-slate-100 flex items-center justify-between">
                    {{ $pendaftarList->links() }}
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- MODAL EDIT DATA PENDAFTAR                                       -->
        <!-- ============================================================== -->
        <div 
            x-show="editModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div 
                @click.away="editModalOpen = false" 
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden"
            >
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Edit Data Pendaftar PMB</h4>
                        <p class="text-xs text-slate-500" x-text="selectedPendaftar.name"></p>
                    </div>
                    <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form :action="'{{ url('pmb/pendaftar') }}/' + selectedPendaftar.id" method="POST" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="name" x-model="selectedPendaftar.name" required class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" x-model="selectedPendaftar.email" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp / HP *</label>
                            <input type="text" name="whatsapp" x-model="selectedPendaftar.whatsapp" required class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Program Studi Pilihan 1</label>
                            <select name="prodi_id" x-model="selectedPendaftar.prodi_id" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="">- Belum Pilih -</option>
                                @foreach($prodis as $pr)
                                    <option value="{{ $pr->id }}">{{ $pr->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Program Studi Pilihan 2</label>
                            <select name="prodi_pilihan_2_id" x-model="selectedPendaftar.prodi_pilihan_2_id" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="">- Tidak Ada -</option>
                                @foreach($prodis as $pr)
                                    <option value="{{ $pr->id }}">{{ $pr->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Formulir</label>
                            <select name="status_formulir" x-model="selectedPendaftar.status_formulir" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="Belum Isi">Belum Isi</option>
                                <option value="Sudah Isi">Sudah Isi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Berkas</label>
                            <select name="status_berkas" x-model="selectedPendaftar.status_berkas" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="Belum Upload">Belum Upload</option>
                                <option value="Pending">Pending</option>
                                <option value="Sudah Upload">Sudah Upload</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Ujian</label>
                            <select name="status_ujian" x-model="selectedPendaftar.status_ujian" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="Belum Mengerjakan">Belum Mengerjakan</option>
                                <option value="Sudah Mengerjakan">Sudah Mengerjakan</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nilai Ujian (0-100)</label>
                            <input type="number" step="0.01" name="nilai_ujian" x-model="selectedPendaftar.nilai_ujian" placeholder="misal: 85.50" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Minat Kuliah</label>
                            <select name="minat_kuliah" x-model="selectedPendaftar.minat_kuliah" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="ya">ya</option>
                                <option value="tidak">tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Minat Miniclass</label>
                            <select name="minat_miniclass" x-model="selectedPendaftar.minat_miniclass" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="ya">ya</option>
                                <option value="tidak">tidak</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL KONFIRMASI HAPUS                                         -->
        <!-- ============================================================== -->
        <div 
            x-show="deleteModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div 
                @click.away="deleteModalOpen = false" 
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-md w-full p-6 text-center"
            >
                <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h4 class="font-extrabold text-slate-900 text-base">Hapus Data Pendaftar?</h4>
                <p class="text-xs text-slate-500 mt-1">
                    Anda yakin ingin menghapus data calon mahasiswa <strong x-text="selectedPendaftar.name"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>

                <form :action="deleteUrl" method="POST" class="mt-5 flex items-center justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
