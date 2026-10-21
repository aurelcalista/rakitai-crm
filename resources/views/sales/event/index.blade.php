@php $pageTitle = 'Jadwal Event'; @endphp

<x-app-layout :title="'Jadwal Event - CRM UCIC'">
    <div class="space-y-6" x-data="{
        confirmModal: false,
        selectedEvent: null,
        searchQuery: '',
        statusFilter: 'all',
        loading: false,
        lastUpdated: 'Baru saja',

        matchesFilter(status, kehadiran, name, lokasi, instansi, spv) {
            // Search text
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                const text = (name + ' ' + lokasi + ' ' + instansi + ' ' + spv).toLowerCase();
                if (!text.includes(q)) return false;
            }

            // Status filter tab
            if (this.statusFilter === 'all') return true;
            if (this.statusFilter === 'scheduled') return ['Scheduled', 'Rencana'].includes(status);
            if (this.statusFilter === 'running') return ['Sedang Berjalan'].includes(status);
            if (this.statusFilter === 'completed') return ['Completed', 'Selesai'].includes(status);
            if (this.statusFilter === 'unconfirmed') return !kehadiran;

            return true;
        },

        async refreshData() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('sales.event.index') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    window.location.reload();
                }
            } catch (e) {
                console.error('Failed refreshing events:', e);
            } finally {
                this.loading = false;
            }
        }
    }">
        <!-- Header Page with Live Summary & Refresh -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Jadwal Event</h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Daftar event operasional yang ditugaskan kepada Anda oleh Supervisor (SPV) / Event Organizer.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <button 
                    type="button" 
                    @click="refreshData()" 
                    :disabled="loading"
                    class="px-3.5 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 transition flex items-center gap-1.5 cursor-pointer shadow-2xs disabled:opacity-50"
                    title="Perbarui daftar event secara dinamis"
                >
                    <svg class="w-3.5 h-3.5 text-slate-500" :class="loading ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="loading ? 'Memuat...' : 'Segarkan Data'"></span>
                </button>
            </div>
        </div>

        @php
            $totalEvent = $events->count();
            $scheduledEvent = $events->filter(fn($e) => in_array($e->status, ['Scheduled', 'Rencana', 'Sedang Berjalan']))->count();
            $myConfirmedCount = 0;
            foreach ($events as $evt) {
                $p = $evt->sales->firstWhere('id', auth()->id())?->pivot;
                if ($p && $p->kehadiran) {
                    $myConfirmedCount++;
                }
            }
        @endphp

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4.5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Ditugaskan</div>
                    <div class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $totalEvent }} <span class="text-xs font-medium text-slate-400">Event</span></div>
                </div>
            </div>

            <div class="bg-white p-4.5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Event Aktif / Terjadwal</div>
                    <div class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $scheduledEvent }} <span class="text-xs font-medium text-slate-400">Event</span></div>
                </div>
            </div>

            <div class="bg-white p-4.5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kehadiran Dikonfirmasi</div>
                    <div class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $myConfirmedCount }} <span class="text-xs font-medium text-slate-400">dari {{ $totalEvent }}</span></div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Filter Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto pb-1 md:pb-0 text-xs font-semibold hide-scrollbar">
                <button 
                    type="button" 
                    @click="statusFilter = 'all'" 
                    class="px-3 py-1.5 rounded-xl transition shrink-0 cursor-pointer"
                    :class="statusFilter === 'all' ? 'bg-blue-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                >
                    Semua ({{ $totalEvent }})
                </button>
                <button 
                    type="button" 
                    @click="statusFilter = 'scheduled'" 
                    class="px-3 py-1.5 rounded-xl transition shrink-0 cursor-pointer"
                    :class="statusFilter === 'scheduled' ? 'bg-blue-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                >
                    Terjadwal ({{ $scheduledEvent }})
                </button>
                <button 
                    type="button" 
                    @click="statusFilter = 'unconfirmed'" 
                    class="px-3 py-1.5 rounded-xl transition shrink-0 cursor-pointer"
                    :class="statusFilter === 'unconfirmed' ? 'bg-blue-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                >
                    Perlu Konfirmasi ({{ max(0, $totalEvent - $myConfirmedCount) }})
                </button>
                <button 
                    type="button" 
                    @click="statusFilter = 'completed'" 
                    class="px-3 py-1.5 rounded-xl transition shrink-0 cursor-pointer"
                    :class="statusFilter === 'completed' ? 'bg-blue-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                >
                    Selesai ({{ $events->filter(fn($e) => in_array($e->status, ['Completed', 'Selesai']))->count() }})
                </button>
            </div>

            <!-- Search Input -->
            <div class="relative w-full md:w-72 shrink-0">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari nama event, lokasi, SPV..." 
                    class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 transition"
                >
            </div>
        </div>

        <!-- Event Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($events as $e)
                @php
                    $eventSales = $e->sales->firstWhere('id', auth()->id())?->pivot;
                    $spv = $e->spvs->first();
                    $eventName = $e->nama ?: $e->name;
                    $startDateTime = $e->tanggal_mulai ?: ($e->tanggal ? $e->tanggal->format('Y-m-d') . ' ' . ($e->waktu_mulai ? \Carbon\Carbon::parse($e->waktu_mulai)->format('H:i') : '08:00') : null);
                    $isStarted = $startDateTime ? \Carbon\Carbon::now('Asia/Jakarta')->greaterThanOrEqualTo(\Carbon\Carbon::parse($startDateTime, 'Asia/Jakarta')) : true;
                    $kehadiranStatus = $eventSales?->kehadiran;
                    $namaInstitusi = $e->nama_institusi ?: ($e->sekolah?->nama ?? $e->perusahaan?->nama ?? '-');
                    $spvName = $spv?->name ?? '-';
                @endphp

                <div 
                    x-show="matchesFilter('{{ $e->status }}', '{{ $kehadiranStatus }}', '{{ addslashes($eventName) }}', '{{ addslashes($e->lokasi) }}', '{{ addslashes($namaInstitusi) }}', '{{ addslashes($spvName) }}')"
                    x-transition
                    class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-md transition relative overflow-hidden flex flex-col justify-between"
                >
                    <div>
                        <!-- Status & Date Header -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border uppercase tracking-wider
                                @if($e->status === 'Selesai' || $e->status === 'Completed') bg-emerald-50 text-emerald-700 border-emerald-200
                                @elseif($e->status === 'Sedang Berjalan') bg-blue-50 text-blue-700 border-blue-200
                                @elseif($e->status === 'Rencana' || $e->status === 'Scheduled') bg-amber-50 text-amber-700 border-amber-200
                                @else bg-rose-50 text-rose-700 border-rose-200 @endif">
                                {{ $e->status }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $startDateTime ? \Carbon\Carbon::parse($startDateTime)->format('d M Y, H:i') : '-' }} WIB
                            </span>
                        </div>

                        <!-- Event Title -->
                        <h3 class="text-base font-bold text-slate-900 mb-2.5 leading-snug">{{ $eventName }}</h3>

                        <!-- Penugasan Info: SPV & EO Badges -->
                        <div class="mb-3.5 p-2.5 bg-slate-50/80 rounded-xl border border-slate-200/70 space-y-1.5">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-500 font-medium">Supervisor (SPV):</span>
                                <span class="inline-flex items-center gap-1 font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    {{ $spvName }}
                                </span>
                            </div>
                            @if($e->eo)
                            <div class="flex items-center justify-between text-[11px] pt-1 border-t border-slate-200/50">
                                <span class="text-slate-500 font-medium">Penyelenggara (EO):</span>
                                <span class="font-semibold text-slate-700">{{ $e->eo->name }}</span>
                            </div>
                            @endif
                        </div>

                        <!-- Detail Information -->
                        <div class="space-y-2 mb-4 text-xs text-slate-600">
                            {{-- Lokasi --}}
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="font-medium text-slate-700">{{ $e->lokasi ?: '-' }}</span>
                            </div>

                            {{-- Instansi --}}
                            @if($e->nama_institusi || $e->sekolah_id || $e->perusahaan_id)
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="font-medium text-slate-700">
                                    {{ $namaInstitusi }}
                                    @if($e->jenis_institusi)
                                        <span class="text-[10px] text-slate-400 font-normal">({{ $e->jenis_institusi }})</span>
                                    @endif
                                </span>
                            </div>
                            @endif

                            {{-- PIC --}}
                            @if($e->pic_name)
                            <div class="flex items-start gap-2 p-2 bg-emerald-50 rounded-xl border border-emerald-100">
                                <svg class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <div class="text-[11px] leading-tight">
                                    <span class="text-emerald-800 font-bold block">{{ $e->pic_name }}</span>
                                    @if($e->pic_whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $e->pic_whatsapp) }}" target="_blank" class="text-emerald-600 hover:underline inline-flex items-center gap-1 mt-0.5">
                                            <span>WhatsApp: {{ $e->pic_whatsapp }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card Footer: Attendance Confirmation / Visit Report -->
                    <div class="mt-4 pt-3.5 border-t border-slate-100 space-y-2.5">
                        @if($kehadiranStatus)
                            <div class="w-full text-center px-3 py-2 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200 flex items-center justify-center gap-1.5 shadow-2xs">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Kehadiran Terkonfirmasi: {{ $kehadiranStatus }}</span>
                            </div>

                            @if($kehadiranStatus === 'Hadir')
                                @if($isStarted)
                                    <a
                                        href="{{ route('sales.kunjungan.create', ['event_id' => $e->id]) }}"
                                        class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Laporan Kunjungan</span>
                                    </a>
                                @else
                                    <button
                                        type="button"
                                        disabled
                                        class="w-full px-4 py-2.5 bg-slate-100 text-slate-400 rounded-xl text-xs font-semibold cursor-not-allowed flex items-center justify-center gap-1.5 border border-slate-200"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Laporan Kunjungan</span>
                                    </button>
                                    <p class="text-[10px] text-slate-400 text-center">
                                        Laporan kunjungan dibuka saat event dimulai.
                                    </p>
                                @endif
                            @endif
                        @else
                            <button
                                @click="selectedEvent = {{ $e->id }}; confirmModal = true"
                                class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition cursor-pointer shadow-2xs flex items-center justify-center gap-1.5"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Konfirmasi Kehadiran Event</span>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 bg-white rounded-2xl border border-slate-200 border-dashed flex flex-col items-center justify-center text-center p-6">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center mb-3 border border-blue-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Belum Ada Event Ditugaskan</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">Event yang ditugaskan oleh Supervisor (SPV) ke Anda akan otomatis muncul di halaman ini beserta notifikasinya.</p>
                </div>
            @endforelse
        </div>

        {{-- Modal: Konfirmasi Kehadiran --}}
        <div x-show="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0" x-cloak>
            <div x-show="confirmModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="confirmModal = false"></div>

            <div x-show="confirmModal" class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg transform transition-all p-6 border border-slate-100">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Konfirmasi Kehadiran Event</h3>
                    </div>
                    <button @click="confirmModal = false" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form :action="`/sales/events/${selectedEvent}/confirm`" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Kehadiran <span class="text-rose-500">*</span></label>
                            <select name="kehadiran" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                                <option value="Hadir">Hadir</option>
                                <option value="Tidak Hadir">Tidak Hadir</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan (Opsional)</label>
                            <textarea name="catatan" rows="3" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition" placeholder="Tuliskan keterangan atau alasan jika tidak hadir..."></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2.5 mt-6 pt-4 border-t border-slate-100">
                        <button type="button" @click="confirmModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-2xs transition cursor-pointer">Simpan Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
