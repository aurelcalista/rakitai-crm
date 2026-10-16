@php
    $pageTitle = 'Detail Kunjungan';
    $pageSubtitle = $visit['nomor'];

    $userRole = auth()->user()->role ?? '';
    $backRoute = match($userRole) {
        'Sales' => route('sales.kunjungan.index'),
        'SPV'   => route('spv.kunjungan.index'),
        default => route('kunjungan.index'),
    };
@endphp

<x-app-layout :title="'Detail Kunjungan - CRM UCIC'">

    <div class="space-y-6" x-data="{ modalEdit: false, confirmDelete: false }">

        <!-- Navigation Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ $backRoute }}" class="hover:text-blue-600 flex items-center gap-1 font-semibold transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Tabel Kunjungan</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-semibold truncate">{{ $visit['name'] }}</span>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <a href="{{ $backRoute }}" class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:text-slate-900 hover:bg-slate-200 transition" title="Kembali ke Tabel Kunjungan">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </a>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $visit['name'] }}</h2>
                </div>
                <div class="flex items-center gap-2 mt-2 ml-10">
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">{{ $visit['type'] }}</span>
                    <span class="text-xs text-slate-500">{{ $visit['nomor'] }}</span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl text-xs font-bold {{ $visit['status'] === 'Selesai' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-700 border-slate-200' }} border">
                    {{ $visit['status'] }}
                </span>
                @if($visit['event_id'])
                    <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Dari Event
                    </span>
                @else
                    <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-50 text-slate-500 border border-slate-200">
                        Kunjungan Mandiri
                    </span>
                @endif
                <button 
                    type="button" 
                    @click="modalEdit = true" 
                    class="px-3.5 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>Edit Kunjungan</span>
                </button>
                <button 
                    type="button" 
                    @click="confirmDelete = true" 
                    class="px-3.5 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Hapus</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-6">
                <div class="crm-card bg-white p-5 sm:p-6">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-3">Informasi Kunjungan</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                        <div>
                            <span class="block text-xs font-medium text-slate-400 mb-1">Tanggal & Waktu</span>
                            <span class="font-bold text-slate-800">{{ $visit['date'] }}, {{ $visit['time'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-400 mb-1">Sales / PIC Internal</span>
                            <span class="font-bold text-slate-800">{{ $visit['sales'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-400 mb-1">Nama PIC Institusi</span>
                            <span class="font-bold text-slate-800">{{ $visit['pic'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-400 mb-1">WhatsApp PIC</span>
                            <span class="font-bold text-emerald-600">{{ $visit['whatsapp'] }}</span>
                        </div>
                        <div class="md:col-span-2">
                            <span class="block text-xs font-medium text-slate-400 mb-1">Alamat Institusi</span>
                            <span class="font-medium text-slate-700">{{ $visit['address'] }}</span>
                        </div>
                        {{-- Program Studi Dipromosikan --}}
                        <div class="md:col-span-2">
                            <span class="block text-xs font-medium text-slate-400 mb-1">Program Studi yang Dipromosikan</span>
                            @if(!empty($visit['prodi']) && $visit['prodi'] !== '-')
                                <div class="flex flex-wrap gap-1.5 mt-0.5">
                                    @foreach(explode(', ', $visit['prodi']) as $pName)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 text-xs font-bold">
                                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                                            {{ trim($pName) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-slate-500 italic">Semua Jurusan / Umum</span>
                            @endif
                        </div>

                        {{-- Event Terkait --}}
                        <div class="md:col-span-2">
                            <span class="block text-xs font-medium text-slate-400 mb-1">Event Terkait</span>
                            @if($visit['event_id'])
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-800 border border-indigo-200 text-xs font-bold">
                                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ $visit['event_name'] ?? 'Event #' . $visit['event_id'] }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-500 border border-slate-200 text-xs font-semibold italic">
                                    Kunjungan Mandiri (tidak terkait Event)
                                </span>
                            @endif
                        </div>

                        {{-- Dosen Pemateri --}}
                        <div class="md:col-span-2">
                            <span class="block text-xs font-medium text-slate-400 mb-1">Dosen Pemateri</span>
                            @if(!empty($visit['dosen_pemateri']) && $visit['dosen_pemateri'] !== '-')
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 text-purple-900 border border-purple-200 text-xs font-bold shadow-2xs">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                    </svg>
                                    <span>{{ $visit['dosen_pemateri'] }}</span>
                                </div>
                            @elseif(!empty($visit['dosen']) && $visit['dosen'] !== '-')
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 text-purple-900 border border-purple-200 text-xs font-bold shadow-2xs">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                    </svg>
                                    <span>{{ $visit['dosen'] }}</span>
                                </div>
                            @else
                                <span class="text-xs text-slate-400 italic">Tidak ada penugasan dosen pemateri</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="crm-card bg-white p-5 sm:p-6">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-3">Hasil & Potensi</h3>
                    
                    <div class="space-y-4 text-sm">
                        @if($visit['type'] === 'Sekolah')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100">
                                    <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi Mahasiswa</span>
                                    <span class="font-bold text-slate-800">{{ $visit['potensi_mahasiswa'] }}</span>
                                </div>
                                @if($visit['detail_potensi_mahasiswa'] && $visit['detail_potensi_mahasiswa'] !== '-')
                                <div class="md:col-span-2 p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-slate-400 block font-semibold text-[10px] uppercase">Detail Potensi Mahasiswa</span>
                                    <span class="font-medium text-slate-700">{{ $visit['detail_potensi_mahasiswa'] }}</span>
                                </div>
                                @endif
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Potensi Kuliah S1</span>
                                    <span class="font-bold text-slate-800">{{ $visit['potensi_s1'] }}</span>
                                </div>
                                <div class="p-4 rounded-xl bg-indigo-50/50 border border-indigo-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Potensi Kuliah S2</span>
                                    <span class="font-bold text-slate-800">{{ $visit['potensi_s2'] }}</span>
                                </div>
                                <div class="p-4 rounded-xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Kerjasama CSR</span>
                                    <span class="font-bold text-slate-800">{{ $visit['potensi_csr'] }}</span>
                                </div>
                                <div class="md:col-span-3 p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Bidang Usaha</span>
                                    <span class="font-medium text-slate-700">{{ $visit['bidang_usaha'] }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6">
                            <span class="block text-xs font-medium text-slate-400 mb-2">Catatan Kunjungan</span>
                            <div class="p-4 bg-amber-50/50 border border-amber-100 rounded-xl text-amber-900 leading-relaxed italic whitespace-pre-wrap">
                                {{ $visit['notes'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Photo -->
            <div class="space-y-6">
                <div class="crm-card bg-white p-5">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-3">Dokumentasi Foto</h3>
                    
                    @if($visit['photo'])
                        <div class="rounded-xl overflow-hidden border border-slate-200">
                            <a href="{{ $visit['photo'] }}" target="_blank" class="block group relative">
                                <img src="{{ $visit['photo'] }}" alt="Foto Kunjungan" class="w-full object-cover group-hover:opacity-90 transition">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-slate-900/20 transition">
                                    <span class="px-3 py-1.5 bg-white text-slate-900 text-xs font-bold rounded-lg shadow-lg">Buka Foto Penuh</span>
                                </div>
                            </a>
                        </div>
                    @else
                        <div class="w-full h-48 rounded-xl bg-slate-50 border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400">
                            <svg class="w-8 h-8 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2-2v12a2 2 0 002 2z" /></svg>
                            <span class="text-xs font-medium">Tidak ada dokumentasi foto</span>
                        </div>
                    @endif
                    
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <span class="block text-xs font-medium text-slate-400 mb-1">Dibuat pada</span>
                        <span class="text-xs font-bold text-slate-700">{{ $visit['created_at'] }}</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- MODAL EDIT KUNJUNGAN -->
        <div 
            x-show="modalEdit" 
            class="fixed inset-0 z-50 overflow-y-auto" 
            x-cloak
            @keydown.escape.window="modalEdit = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalEdit = false"></div>

                <div class="relative z-10 bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg p-6 overflow-hidden">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-200">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Edit Laporan Kunjungan</h3>
                                <p class="text-xs text-slate-500">Perbarui data laporan hasil kunjungan ini.</p>
                            </div>
                        </div>
                        <button type="button" @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form 
                        action="{{ route((auth()->user()->role === 'Sales' ? 'sales.' : (auth()->user()->role === 'SPV' ? 'spv.' : '')) . 'kunjungan.update', $visit['id']) }}" 
                        method="POST" 
                        enctype="multipart/form-data" 
                        class="space-y-4 text-xs"
                    >
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Kunjungan</label>
                                <input type="date" name="tanggal" required value="{{ $visit['raw_date'] ?? '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Waktu (WIB)</label>
                                <input type="time" name="waktu" value="{{ $visit['raw_time'] ?? $visit['time'] ?? '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Institusi / Tujuan</label>
                            <input type="text" name="nama_institusi" required value="{{ $visit['name'] }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama PIC</label>
                                <input type="text" name="pic_name" value="{{ $visit['pic'] !== '-' ? $visit['pic'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">WhatsApp PIC</label>
                                <input type="text" name="pic_whatsapp" value="{{ $visit['whatsapp'] !== '-' ? $visit['whatsapp'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Alamat Institusi</label>
                            <input type="text" name="alamat" value="{{ $visit['address'] !== '-' ? $visit['address'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Dosen / Pemateri <span class="text-slate-400 font-normal">(Opsional)</span></label>
                            <input type="text" name="dosen_pemateri" value="{{ $visit['dosen_pemateri'] ?? ($visit['dosen'] !== '-' ? $visit['dosen'] : '') }}" placeholder="Contoh: Dr. Ir. H. Ahmad, M.T." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <p class="text-[11px] text-slate-400 mt-1">Tambahkan nama dosen pemateri / narasumber jika saat awal input belum tercatat.</p>
                        </div>

                        @if($visit['type'] === 'Sekolah')
                            <div class="space-y-3 p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Potensi Mahasiswa</label>
                                    <input type="text" name="potensi_mahasiswa" value="{{ $visit['potensi_mahasiswa'] !== '-' ? $visit['potensi_mahasiswa'] : '' }}" placeholder="Contoh: 50 Siswa Kelas XII" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Detail Potensi</label>
                                    <input type="text" name="detail_potensi_mahasiswa" value="{{ $visit['detail_potensi_mahasiswa'] !== '-' ? $visit['detail_potensi_mahasiswa'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                            </div>
                        @else
                            <div class="space-y-3 p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Bidang Usaha</label>
                                    <input type="text" name="bidang_usaha" value="{{ $visit['bidang_usaha'] !== '-' ? $visit['bidang_usaha'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Potensi S1</label>
                                        <input type="text" name="potensi_s1" value="{{ $visit['potensi_s1'] !== '-' ? $visit['potensi_s1'] : '' }}" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Potensi S2</label>
                                        <input type="text" name="potensi_s2" value="{{ $visit['potensi_s2'] !== '-' ? $visit['potensi_s2'] : '' }}" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">CSR</label>
                                        <input type="text" name="potensi_csr" value="{{ $visit['potensi_csr'] !== '-' ? $visit['potensi_csr'] : '' }}" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan & Hasil Kunjungan</label>
                            <textarea name="catatan" rows="3" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none">{{ $visit['notes'] !== '-' ? $visit['notes'] : '' }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 items-center">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Status Kunjungan</label>
                                <select name="status" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                    <option value="Selesai" {{ $visit['status'] === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                    <option value="Proses" {{ $visit['status'] === 'Proses' ? 'selected' : '' }}>Proses</option>
                                    <option value="Menunggu" {{ $visit['status'] === 'Menunggu' ? 'selected' : '' }}>Menunggu</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Ganti Foto (Opsional)</label>
                                <input type="file" name="foto" accept="image/*" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold cursor-pointer shadow-xs">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS -->
        <div 
            x-show="confirmDelete" 
            class="fixed inset-0 z-50 overflow-y-auto"
            x-cloak
            @keydown.escape.window="confirmDelete = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="confirmDelete = false"></div>

                <div class="relative z-10 bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-sm p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Hapus Kunjungan Ini?</h3>
                    <p class="text-sm text-slate-500 mb-6">Data kunjungan ini akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
                    <div class="flex items-center gap-3 justify-center">
                        <button
                            type="button"
                            @click="confirmDelete = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <form
                            action="{{ route((auth()->user()->role === 'Sales' ? 'sales.' : (auth()->user()->role === 'SPV' ? 'spv.' : '')) . 'kunjungan.destroy', $visit['id']) }}"
                            method="POST"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition cursor-pointer shadow-xs"
                            >
                                Ya, Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
