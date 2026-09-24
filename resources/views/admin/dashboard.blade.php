@php $pageTitle = 'Dashboard Administrator'; @endphp

<x-app-layout :title="'Dashboard Admin - CRM UCIC'">

    <div class="space-y-6" x-data="{
        show: false,
        activeTab: 'roles',
        activeSlide: 0,
        slides: {{ json_encode($userSlides) }},
        timer: null,
        next() {
            this.activeSlide = (this.activeSlide + 1) % this.slides.length;
        },
        prev() {
            this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length;
        },
        startAutoPlay() {
            this.timer = setInterval(() => this.next(), 4000);
        },
        stopAutoPlay() {
            if (this.timer) clearInterval(this.timer);
        }
    }"
    x-init="setTimeout(() => show = true, 100); startAutoPlay()"
    >

        <!-- Header -->
        <div x-show="show" x-transition:enter="transition ease-out duration-500 transform" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Dashboard Administrator </h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">Super Administrator</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Ringkasan sistem, pengguna, target tim, dan aktivitas CRM UCIC.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer hover:-translate-y-0.5 duration-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span>+ Tambah User</span>
                </a>
                <a href="{{ route('admin.master-data.index') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center gap-1.5 bg-white cursor-pointer hover:-translate-y-0.5 duration-300">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 01-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Data Master</span>
                </a>
            </div>
        </div>

        <!-- DASHBOARD TARGET & PENCAPAIAN BERJENJANG (PRD 6.2) -->
        @if(isset($targetAchievementData))
            <x-target-achievement-table 
                :data="$targetAchievementData" 
                title="Dashboard Target & Pencapaian Global (Admin)" 
                subtitle="Monitoring capaian seluruh wilayah teritori, kekurangan, sisa hari, dan target harian berjalan"
            />
        @endif

        <!-- COMBINED METRICS & ANIMATED ROLES SLIDER (COMPACT & SIMPLE) -->
        <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-150 transform" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="crm-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden space-y-4">
            
            <!-- Tab Switcher Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <button 
                        @click="activeTab = 'roles'" 
                        :class="activeTab === 'roles' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" 
                        class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 hover:scale-105"
                    >
                        <span> Distribution Role (Slide)</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'roles' ? 'bg-purple-700 text-white' : 'bg-slate-200 text-slate-700'">{{ $stats['total_users'] }}</span>
                    </button>

                    <button 
                        @click="activeTab = 'master'" 
                        :class="activeTab === 'master' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" 
                        class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 hover:scale-105"
                    >
                        <span> Data Master & Mitra</span>
                    </button>
                </div>

            </div>

            <!-- TAB 1: User Roles Vertical Bar Chart -->
            <div x-show="activeTab === 'roles'" x-transition:enter="transition ease-out duration-250" class="pt-4 pb-2 w-full">
                <div class="flex items-end justify-around h-48 gap-4 border-b border-slate-100 pb-3 px-2">
                    @foreach($userSlides as $item)
                        <div class="flex flex-col items-center gap-2 group w-full h-full justify-end relative">
                            <!-- Tooltip/Count -->
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 text-[10px] font-bold text-white bg-slate-800 px-2 py-1 rounded-md shadow-md absolute top-0 mt-2 z-10 whitespace-nowrap pointer-events-none">
                                {{ $item['count'] }} User
                            </div>
                            
                            <!-- Bar Container -->
                            <div class="w-full max-w-[50px] bg-slate-50/80 border border-slate-100 rounded-t-xl relative overflow-hidden h-[130px] flex flex-col justify-end items-center group-hover:bg-slate-100 transition-colors">
                                <div class="w-full bg-{{ $item['color'] }}-500 rounded-t-xl transition-all duration-1000 ease-out group-hover:brightness-110 shadow-sm"
                                     style="height: 0%"
                                     x-init="setTimeout(() => { $el.style.height = '{{ $item['pct'] }}%' }, 400)"
                                ></div>
                            </div>
                            
                            <!-- Label & Info -->
                            <div class="text-center mt-1">
                                <div class="text-[11px] font-extrabold text-slate-700 uppercase tracking-wider">
                                    {{ $item['role'] }}
                                </div>
                                <div class="text-[10px] font-bold text-slate-400">
                                    {{ $item['pct'] }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- TAB 2: Master Data Grid Summary -->
            <div x-show="activeTab === 'master'" x-transition:enter="transition ease-out duration-250" class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                <a href="{{ route('admin.kunjungan.index') }}" class="p-3 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-0.5 block hover:-translate-y-1 hover:shadow-sm duration-300">
                    <div class="font-extrabold text-slate-900 text-base">{{ $stats['total_kunjungan'] }}</div>
                    <div class="font-bold text-slate-700 text-[11px]">Kunjungan</div>
                    <div class="text-[10px] text-slate-400">Total Kunjungan</div>
                </a>
                <a href="{{ route('admin.sekolah.index') }}" class="p-3 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-0.5 block hover:-translate-y-1 hover:shadow-sm duration-300">
                    <div class="font-extrabold text-slate-900 text-base">{{ $stats['total_sekolah'] }}</div>
                    <div class="font-bold text-slate-700 text-[11px]">Sekolah Mitra</div>
                    <div class="text-[10px] text-slate-400">SMA/SMK/MA</div>
                </a>
                <a href="{{ route('admin.perusahaan.index') }}" class="p-3 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-0.5 block hover:-translate-y-1 hover:shadow-sm duration-300">
                    <div class="font-extrabold text-slate-900 text-base">{{ $stats['total_perusahaan'] }}</div>
                    <div class="font-bold text-slate-700 text-[11px]">Perusahaan</div>
                    <div class="text-[10px] text-slate-400">Data Corporate</div>
                </a>
                <a href="{{ route('admin.prodi.index') }}" class="p-3 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-0.5 block hover:-translate-y-1 hover:shadow-sm duration-300">
                    <div class="font-extrabold text-slate-900 text-base">{{ $stats['total_prodi'] }}</div>
                    <div class="font-bold text-slate-700 text-[11px]">Prodi UCIC</div>
                    <div class="text-[10px] text-slate-400">Prodi Aktif</div>
                </a>
                <a href="{{ route('admin.wilayah.index') }}" class="p-3 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-0.5 block hover:-translate-y-1 hover:shadow-sm duration-300">
                    <div class="font-extrabold text-slate-900 text-base">{{ $stats['total_wilayah'] }}</div>
                    <div class="font-bold text-slate-700 text-[11px]">Wilayah</div>
                    <div class="text-[10px] text-slate-400">Cakupan Area</div>
                </a>
            </div>

        </div>

        <!-- MAIN SECTION (2 BALANCED COLUMNS) -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

            <!-- Target Aktif Sales (Realtime) -->
            <div x-show="show" 
                 x-transition:enter="transition ease-out duration-500 delay-300 transform" 
                 x-transition:enter-start="opacity-0 translate-y-4" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="lg:col-span-3 crm-card bg-white p-6 shadow-xs rounded-2xl border border-slate-200/80 flex flex-col"
                 x-data="{ 
                    slide: 0, 
                    max: {{ count($activeTargets) }},
                    timer: null,
                    next() { this.slide = (this.slide + 1) % this.max; },
                    prev() { this.slide = (this.slide - 1 + this.max) % this.max; }
                 }"
                 x-init="if(max > 1) { timer = setInterval(() => next(), 5000); }"
            >
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Target Aktif Sales — Realtime</h3>
                        <p class="text-[11px] text-slate-500">Realisasi & penugasan target tim sales inbound</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(count($activeTargets) > 1)
                            <div class="flex items-center gap-1 mr-2">
                                <button @click="prev()" @mouseenter="if(timer) clearInterval(timer)" @mouseleave="if(max > 1) timer = setInterval(() => next(), 5000)" class="w-6 h-6 rounded-md bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-500 flex items-center justify-center transition hover:scale-105">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <button @click="next()" @mouseenter="if(timer) clearInterval(timer)" @mouseleave="if(max > 1) timer = setInterval(() => next(), 5000)" class="w-6 h-6 rounded-md bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-500 flex items-center justify-center transition hover:scale-105">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </div>
                        @endif
                        <a href="{{ route('admin.target.index') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700 hover:underline">Kelola &rarr;</a>
                    </div>
                </div>

                <div class="relative min-h-[130px] flex-1 overflow-hidden rounded-xl" @mouseenter="if(timer) clearInterval(timer)" @mouseleave="if(max > 1) timer = setInterval(() => next(), 5000)">
                    @forelse($activeTargets as $idx => $t)
                        <div x-show="slide === {{ $idx }}"
                             x-transition:enter="transition ease-out duration-300 transform"
                             x-transition:enter-start="opacity-0 translate-x-8"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-200 transform absolute inset-0"
                             x-transition:leave-start="opacity-100 translate-x-0"
                             x-transition:leave-end="opacity-0 -translate-x-8"
                             class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/40 w-full h-full flex flex-col justify-center"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                                        {{ substr($t->sales->name ?? 'S', 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 block leading-tight">{{ $t->sales->name ?? 'Sales Staff' }}</span>
                                        <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($t->tanggal_mulai)->translatedFormat('M Y') }}</span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                    {{ $t->status }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-xs pt-1">
                                <div class="p-2.5 bg-white rounded-xl border border-slate-200/70 hover:shadow-xs transition duration-300">
                                    <div class="text-slate-400 text-[10px]">Target Kontak</div>
                                    <div class="font-bold text-slate-800 mt-0.5">{{ $t->target_kontak }} Kontak</div>
                                </div>
                                <div class="p-2.5 bg-white rounded-xl border border-slate-200/70 hover:shadow-xs transition duration-300">
                                    <div class="text-slate-400 text-[10px]">Target Follow Up</div>
                                    <div class="font-bold text-slate-800 mt-0.5">{{ $t->target_followup }} FU</div>
                                </div>
                                <div class="p-2.5 bg-white rounded-xl border border-slate-200/70 hover:shadow-xs transition duration-300">
                                    <div class="text-slate-400 text-[10px]">Target Kunjungan</div>
                                    <div class="font-bold text-slate-800 mt-0.5">{{ $t->target_kunjungan }} Audiensi</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 h-full flex flex-col justify-center text-center text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200 space-y-2">
                            <svg class="w-8 h-8 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p class="text-xs font-semibold text-slate-600">Belum Ada Target Aktif Terdaftar</p>
                            <a href="{{ route('admin.target.index') }}" class="text-xs font-bold text-purple-600 hover:text-purple-700 inline-block hover:underline">+ Buat Target Sales Baru</a>
                        </div>
                    @endforelse
                </div>
                
                <!-- Dot Indicators -->
                @if(count($activeTargets) > 1)
                    <div class="flex justify-center gap-1.5 pt-3 mt-auto">
                        @foreach($activeTargets as $idx => $t)
                            <button @click="slide = {{ $idx }}" :class="slide === {{ $idx }} ? 'w-4 bg-purple-600' : 'w-1.5 bg-slate-200 hover:bg-slate-300'" class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"></button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Pengguna Terdaftar Terbaru -->
            <div x-show="show" 
                 x-transition:enter="transition ease-out duration-500 delay-450 transform" 
                 x-transition:enter-start="opacity-0 translate-y-4" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="lg:col-span-2 crm-card bg-white p-6 shadow-xs rounded-2xl border border-slate-200/80 flex flex-col"
                 x-data="{ 
                    slide: 0, 
                    max: {{ count($recentUsers) }},
                    timer: null,
                    next() { this.slide = (this.slide + 1) % this.max; },
                    prev() { this.slide = (this.slide - 1 + this.max) % this.max; }
                 }"
                 x-init="if(max > 1) { timer = setInterval(() => next(), 4500); }"
            >
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Pengguna Terbaru</h3>
                        <p class="text-[11px] text-slate-500">User terbaru dalam database CRM</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(count($recentUsers) > 1)
                            <div class="flex items-center gap-1 mr-1">
                                <button @click="prev()" @mouseenter="if(timer) clearInterval(timer)" @mouseleave="if(max > 1) timer = setInterval(() => next(), 4500)" class="w-6 h-6 rounded-md bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-500 flex items-center justify-center transition hover:scale-105">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <button @click="next()" @mouseenter="if(timer) clearInterval(timer)" @mouseleave="if(max > 1) timer = setInterval(() => next(), 4500)" class="w-6 h-6 rounded-md bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-500 flex items-center justify-center transition hover:scale-105">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </div>
                        @endif
                        <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700 hover:underline">Semua &rarr;</a>
                    </div>
                </div>

                <div class="relative min-h-[70px] flex-1 overflow-hidden rounded-xl flex flex-col justify-center" @mouseenter="if(timer) clearInterval(timer)" @mouseleave="if(max > 1) timer = setInterval(() => next(), 4500)">
                    @foreach($recentUsers as $idx => $usr)
                        @php
                            $words = array_values(array_filter(explode(' ', trim($usr->name))));
                            $avatarInit = strtoupper(substr($words[0] ?? 'U', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                        @endphp
                        <div x-show="slide === {{ $idx }}"
                             x-transition:enter="transition ease-out duration-300 transform"
                             x-transition:enter-start="opacity-0 translate-x-8"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-200 transform absolute inset-0"
                             x-transition:leave-start="opacity-100 translate-x-0"
                             x-transition:leave-end="opacity-0 -translate-x-8"
                             class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/80 flex items-center justify-between w-full h-full"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-xs">
                                    {{ $avatarInit }}
                                </div>
                                <div class="truncate">
                                    <div class="font-bold text-slate-900 truncate text-sm">{{ $usr->name }}</div>
                                    <div class="text-[11px] text-slate-400 truncate mt-0.5">{{ $usr->email }}</div>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border block mb-1.5
                                    @if($usr->role === 'Sales') bg-blue-50 text-blue-700 border-blue-200
                                    @elseif($usr->role === 'CS') bg-teal-50 text-teal-800 border-teal-200
                                    @elseif($usr->role === 'SPV') bg-indigo-50 text-indigo-700 border-indigo-200
                                    @elseif($usr->role === 'HM') bg-purple-50 text-purple-700 border-purple-200
                                    @else bg-slate-100 text-slate-700 border-slate-200
                                    @endif">
                                    {{ $usr->role }}
                                </span>
                                <span class="text-[10px] font-semibold {{ strtolower($usr->status ?? 'aktif') === 'aktif' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    ● {{ ucfirst($usr->status ?? 'Aktif') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Dot Indicators -->
                @if(count($recentUsers) > 1)
                    <div class="flex justify-center gap-1.5 pt-3 mt-auto">
                        @foreach($recentUsers as $idx => $usr)
                            <button @click="slide = {{ $idx }}" :class="slide === {{ $idx }} ? 'w-4 bg-purple-600' : 'w-1.5 bg-slate-200 hover:bg-slate-300'" class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"></button>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <!-- Aktivitas Terbaru Sistem -->
        <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-500 transform" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="crm-card bg-white p-6 space-y-4 shadow-xs rounded-2xl border border-slate-200/80">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Aktivitas Terbaru Sistem</h3>
                    <p class="text-xs text-slate-500">Seluruh aktivitas pengguna dalam sistem hari ini</p>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700 hover:underline">Lihat Semua Audit Logs &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-3">Role</th>
                            <th class="py-3 px-3">Aktivitas</th>
                            <th class="py-3 px-3">Target</th>
                            <th class="py-3 px-4 text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentActivities as $act)
                            <tr class="hover:bg-slate-50/80 transition duration-300">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-[11px] shrink-0">{{ substr($act['user'],0,1) }}</div>
                                        <span class="font-bold text-slate-900">{{ $act['user'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold
                                        @if($act['role']==='Sales') bg-blue-50 text-blue-700 border-blue-200
                                        @elseif($act['role']==='CS') bg-teal-50 text-teal-800 border-teal-200
                                        @elseif($act['role']==='SPV') bg-indigo-50 text-indigo-700 border-indigo-200
                                        @elseif($act['role']==='HM') bg-purple-50 text-purple-700 border-purple-200
                                        @else bg-slate-100 text-slate-700 border-slate-200
                                        @endif border">{{ $act['role'] }}</span>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-purple-700">{{ $act['action'] }}</td>
                                <td class="py-3.5 px-3 text-slate-600">{{ $act['target'] }}</td>
                                <td class="py-3.5 px-4 text-right text-slate-400 text-[11px]">{{ $act['time'] }} WIB</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-app-layout>
