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

        <!-- Filter Bar Card -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-xs space-y-4 transition-all duration-300 hover:shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#0b3d82]"></span>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 font-display">Filter & Pencarian Calon Mahasiswa</h3>
                </div>
                <span class="text-xs text-slate-400">Total Terdaftar: <strong class="text-slate-700">{{ $pendaftarList->total() }}</strong></span>
            </div>

            <form action="{{ route('pmb.pendaftar.index') }}" method="GET" id="filterForm">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- 1. Semua Status Formulir -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status Formulir</label>
                        <select name="status_formulir" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-slate-700 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                            <option value="all" {{ request('status_formulir', 'all') === 'all' ? 'selected' : '' }}>Semua Status Formulir</option>
                            <option value="Belum Isi" {{ request('status_formulir') === 'Belum Isi' ? 'selected' : '' }}>Belum Isi</option>
                            <option value="Sudah Isi" {{ request('status_formulir') === 'Sudah Isi' ? 'selected' : '' }}>Sudah Isi</option>
                        </select>
                    </div>

                    <!-- 2. Semua Status Ujian -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status Ujian</label>
                        <select name="status_ujian" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-slate-700 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                            <option value="all" {{ request('status_ujian', 'all') === 'all' ? 'selected' : '' }}>Semua Status Ujian</option>
                            <option value="Belum Mengerjakan" {{ request('status_ujian') === 'Belum Mengerjakan' ? 'selected' : '' }}>Belum Mengerjakan</option>
                            <option value="Sudah Mengerjakan" {{ request('status_ujian') === 'Sudah Mengerjakan' ? 'selected' : '' }}>Sudah Mengerjakan</option>
                        </select>
                    </div>

                    <!-- 3. Semua Gelombang -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Gelombang Pendaftaran</label>
                        <select name="gelombang" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-slate-700 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                            <option value="all" {{ request('gelombang', 'all') === 'all' ? 'selected' : '' }}>Semua Gelombang</option>
                            @foreach($gelombangList as $gel)
                                <option value="{{ $gel }}" {{ request('gelombang') === $gel ? 'selected' : '' }}>{{ $gel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 4. Semua Program Studi -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Program Studi</label>
                        <select name="prodi_id" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-slate-700 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                            <option value="all" {{ request('prodi_id', 'all') === 'all' ? 'selected' : '' }}>Semua Program Studi</option>
                            @foreach($prodis as $pr)
                                <option value="{{ $pr->id }}" {{ (string)request('prodi_id') === (string)$pr->id ? 'selected' : '' }}>{{ $pr->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 pt-3">
                    <a 
                        href="{{ route('pmb.pendaftar.export', request()->query()) }}" 
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs hover:shadow-md active:scale-[0.98] transition-all cursor-pointer"
                        title="Unduh data dalam format CSV / Excel"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Export Data</span>
                    </a>
                    
                    <a 
                        href="{{ route('pmb.pendaftar.index') }}" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition border border-slate-200"
                    >
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset Filter</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- 4 Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total User -->
            <div class="group bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-slate-900 tracking-tight font-display">{{ number_format($totalUser) }}</div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        TOTAL USER
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#0b3d82] flex items-center justify-center shrink-0 border border-blue-100 group-hover:bg-[#0b3d82] group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>

            <!-- 2. Belum Isi Formulir -->
            <div class="group bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md hover:-translate-y-1 hover:border-rose-300 transition-all duration-300 flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-rose-600 tracking-tight font-display">{{ number_format($belumIsiFormulir) }}</div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        BELUM ISI FORMULIR
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100 group-hover:bg-rose-600 group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <!-- 3. Belum Upload -->
            <div class="group bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md hover:-translate-y-1 hover:border-amber-300 transition-all duration-300 flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-amber-600 tracking-tight font-display">{{ number_format($belumUploadBerkas) }}</div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        BELUM UPLOAD
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100 group-hover:bg-amber-500 group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
            </div>

            <!-- 4. Sudah Upload -->
            <div class="group bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md hover:-translate-y-1 hover:border-emerald-300 transition-all duration-300 flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-emerald-600 tracking-tight font-display">{{ number_format($sudahUploadBerkas) }}</div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        SUDAH UPLOAD
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100 group-hover:bg-emerald-600 group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Table Card: Data Pendaftar PMB -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden transition-all duration-300">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#0b3d82]"></span>
                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 font-display">Data Pendaftar PMB</h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar calon mahasiswa baru terdaftar resmi di sistem penerimaan mahasiswa</p>
                </div>

                <!-- Search Form -->
                <form action="{{ route('pmb.pendaftar.index') }}" method="GET" class="flex items-center gap-2 w-full md:w-auto">
                    <!-- Preserve existing filters -->
                    <input type="hidden" name="status_formulir" value="{{ request('status_formulir') }}">
                    <input type="hidden" name="status_ujian" value="{{ request('status_ujian') }}">
                    <input type="hidden" name="gelombang" value="{{ request('gelombang') }}">
                    <input type="hidden" name="prodi_id" value="{{ request('prodi_id') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">

                    <div class="relative w-full md:w-80">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Cari nama, email, no. WhatsApp..." 
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 pl-3.5 pr-9 py-2.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition shadow-2xs"
                        >
                        @if(request('search'))
                            <a href="{{ route('pmb.pendaftar.index', request()->except('search')) }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </a>
                        @endif
                    </div>
                    <button type="submit" class="p-2.5 rounded-xl bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs hover:shadow-md transition cursor-pointer shrink-0 active:scale-95">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Total Indicator & Rows Per Page -->
            <div class="px-6 py-3 bg-slate-50/60 border-b border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-2">
                <div>
                    Menampilkan <span class="font-bold text-slate-800">{{ $pendaftarList->firstItem() ?? 0 }} - {{ $pendaftarList->lastItem() ?? 0 }}</span> dari <span class="font-bold text-slate-800">{{ $pendaftarList->total() }}</span> pendaftar
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-[11px]">Tampilkan per halaman:</span>
                    <select onchange="window.location.href = this.value" class="text-xs font-bold rounded-lg border border-slate-200 bg-white py-1 px-2.5 text-slate-700 cursor-pointer shadow-2xs">
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
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-100">
                            <th class="py-3.5 px-3 text-center w-12">NO</th>
                            <th class="py-3.5 px-4">NAMA LENGKAP</th>
                            <th class="py-3.5 px-3">NO. WHATSAPP</th>
                            <th class="py-3.5 px-3">PROGRAM STUDI</th>
                            <th class="py-3.5 px-3">GELOMBANG</th>
                            <th class="py-3.5 px-3 text-center">STATUS FORMULIR</th>
                            <th class="py-3.5 px-3 text-center">STATUS BERKAS</th>
                            <th class="py-3.5 px-3 text-center">STATUS UJIAN</th>
                            <th class="py-3.5 px-2 text-center">MINAT KULIAH</th>
                            <th class="py-3.5 px-2 text-center">MINAT MINICLASS</th>
                            <th class="py-3.5 px-3 text-center">TGL DAFTAR</th>
                            <th class="py-3.5 px-3 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($pendaftarList as $index => $p)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <!-- No -->
                                <td class="py-4 px-3 text-center font-bold text-slate-400">
                                    {{ $pendaftarList->firstItem() + $index }}
                                </td>

                                <!-- Nama Lengkap + Email -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 font-bold flex items-center justify-center shrink-0 text-xs border border-blue-200">
                                            {{ strtoupper(substr($p->name, 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('pmb.pendaftar.show', $p->id) }}" class="font-bold text-slate-900 hover:text-blue-600 transition truncate block leading-snug">
                                                {{ $p->name }}
                                            </a>
                                            <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                                                {{ $p->email ?: '-' }}
                                            </div>
                                            @if($p->cs)
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200" title="Petugas CS yang menangani">
                                                        <svg class="w-2.5 h-2.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                        <span>CS: {{ $p->cs->name }}</span>
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- No HP -->
                                <td class="py-4 px-3 whitespace-nowrap">
                                    @php
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $p->whatsapp);
                                    @endphp
                                    <a 
                                        href="https://wa.me/{{ $cleanPhone }}" 
                                        target="_blank" 
                                        class="inline-flex items-center gap-1 font-semibold text-[#0b3d82] hover:text-blue-800 hover:underline"
                                    >
                                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        <span>{{ $p->whatsapp }}</span>
                                    </a>
                                </td>

                                <!-- Program Studi (Pills) -->
                                <td class="py-4 px-3">
                                    <div class="flex flex-col gap-1 items-start">
                                        @if($p->prodi)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0b3d82] border border-blue-200">
                                                {{ $p->prodi->nama }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                                - Belum Pilih
                                            </span>
                                        @endif

                                        @if($p->prodiPilihan2)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                {{ $p->prodiPilihan2->nama }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Gelombang -->
                                <td class="py-4 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $p->gelombang ?: 'Gelombang 1' }}
                                    </span>
                                </td>

                                <!-- Status Formulir -->
                                <td class="py-4 px-3 text-center whitespace-nowrap">
                                    @if($p->status_formulir === 'Sudah Isi')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Sudah Isi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Belum Isi
                                        </span>
                                    @endif
                                </td>

                                <!-- Status Berkas -->
                                <td class="py-4 px-3 text-center whitespace-nowrap">
                                    @if($p->status_berkas === 'Sudah Upload')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Sudah Upload
                                        </span>
                                    @elseif($p->status_berkas === 'Pending')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            Belum Upload
                                        </span>
                                    @endif
                                </td>

                                <!-- Status Ujian -->
                                <td class="py-4 px-3 text-center whitespace-nowrap">
                                    @if($p->status_ujian === 'Sudah Mengerjakan')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Sudah Mengerjakan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            Belum Mengerjakan
                                        </span>
                                    @endif
                                </td>

                                <!-- Minat Kuliah -->
                                <td class="py-4 px-2 text-center text-slate-700 font-semibold lowercase">
                                    {{ $p->minat_kuliah ?: 'ya' }}
                                </td>

                                <!-- Minat Miniclass -->
                                <td class="py-4 px-2 text-center text-slate-700 font-semibold lowercase">
                                    {{ $p->minat_miniclass ?: 'tidak' }}
                                </td>

                                <!-- Tanggal Daftar -->
                                <td class="py-4 px-3 text-center text-slate-500 whitespace-nowrap font-medium text-[11px]">
                                    {{ $p->created_at ? $p->created_at->format('d M Y') : '-' }}
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Lihat Detail -->
                                        <a 
                                            href="{{ route('pmb.pendaftar.show', $p->id) }}" 
                                            class="w-7 h-7 rounded-lg bg-blue-50 text-[#0b3d82] hover:bg-[#0b3d82] hover:text-white flex items-center justify-center transition border border-blue-200 shadow-2xs cursor-pointer"
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
                                            class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-500 hover:text-white flex items-center justify-center transition border border-amber-200 cursor-pointer shadow-2xs"
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
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white flex items-center justify-center transition border border-rose-200 cursor-pointer shadow-2xs"
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
                <div class="p-4 sm:p-5 border-t border-slate-100 flex items-center justify-between">
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
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div 
                @click.away="editModalOpen = false" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden"
            >
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0b3d82] flex items-center justify-center font-bold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm font-display">Edit Data Pendaftar PMB</h4>
                            <p class="text-xs text-slate-500" x-text="selectedPendaftar.name"></p>
                        </div>
                    </div>
                    <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form :action="'{{ url('pmb/pendaftar') }}/' + selectedPendaftar.id" method="POST" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="name" x-model="selectedPendaftar.name" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" x-model="selectedPendaftar.email" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp / HP *</label>
                            <input type="text" name="whatsapp" x-model="selectedPendaftar.whatsapp" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Program Studi Pilihan 1</label>
                            <select name="prodi_id" x-model="selectedPendaftar.prodi_id" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                <option value="">- Belum Pilih -</option>
                                @foreach($prodis as $pr)
                                    <option value="{{ $pr->id }}">{{ $pr->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Program Studi Pilihan 2</label>
                            <select name="prodi_pilihan_2_id" x-model="selectedPendaftar.prodi_pilihan_2_id" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
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
                            <select name="status_formulir" x-model="selectedPendaftar.status_formulir" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                <option value="Belum Isi">Belum Isi</option>
                                <option value="Sudah Isi">Sudah Isi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Berkas</label>
                            <select name="status_berkas" x-model="selectedPendaftar.status_berkas" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                <option value="Belum Upload">Belum Upload</option>
                                <option value="Pending">Pending</option>
                                <option value="Sudah Upload">Sudah Upload</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Ujian</label>
                            <select name="status_ujian" x-model="selectedPendaftar.status_ujian" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                <option value="Belum Mengerjakan">Belum Mengerjakan</option>
                                <option value="Sudah Mengerjakan">Sudah Mengerjakan</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nilai Ujian (0-100)</label>
                            <input type="number" step="0.01" name="nilai_ujian" x-model="selectedPendaftar.nilai_ujian" placeholder="misal: 85.50" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Minat Kuliah</label>
                            <select name="minat_kuliah" x-model="selectedPendaftar.minat_kuliah" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                <option value="ya">ya</option>
                                <option value="tidak">tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Minat Miniclass</label>
                            <select name="minat_miniclass" x-model="selectedPendaftar.minat_miniclass" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                <option value="ya">ya</option>
                                <option value="tidak">tidak</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs hover:shadow-md transition">
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
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div 
                @click.away="deleteModalOpen = false" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full p-6 text-center"
            >
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 mx-auto flex items-center justify-center mb-3 border border-rose-100 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h4 class="font-extrabold text-slate-900 text-base font-display">Hapus Data Pendaftar?</h4>
                <p class="text-xs text-slate-500 mt-1">
                    Anda yakin ingin menghapus data calon mahasiswa <strong class="text-slate-800" x-text="selectedPendaftar.name"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>

                <form :action="deleteUrl" method="POST" class="mt-5 flex items-center justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs hover:shadow-md transition cursor-pointer">
                        Ya, Hapus Data
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
