@php $pageTitle = 'Dashboard Administrator'; @endphp

<x-app-layout :title="'Dashboard Admin - CRM UCIC'">

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Dashboard Administrator</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">Super Administrator</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Ringkasan sistem, pengguna, target tim, dan aktivitas CRM UCIC.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer hover:-translate-y-0.5 duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span>+ Tambah User</span>
                </a>
                <a href="{{ route('admin.master-data.index') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center gap-1.5 bg-white cursor-pointer hover:-translate-y-0.5 duration-200">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 01-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Data Master</span>
                </a>
            </div>
        </div>

        <!-- TOP METRIC CARDS (4 Balanced Summary Cards) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <x-stat-card 
                title="Total Pengguna" 
                :value="$stats['total_users']" 
                subtitle="{{ $stats['total_sales'] }} Sales, {{ $stats['total_cs'] }} CS, {{ $stats['total_spv'] }} SPV, {{ $stats['total_hm'] }} HM" 
                color="purple"
            />
            <x-stat-card 
                title="Total Kunjungan" 
                :value="$stats['total_kunjungan']" 
                subtitle="Audiensi dan sosialisasi kampus" 
                color="blue"
            />
            <x-stat-card 
                title="Sekolah Mitra" 
                :value="$stats['total_sekolah']" 
                subtitle="Database SMA / SMK / MA rekanan" 
                color="indigo"
            />
            <x-stat-card 
                title="Perusahaan Mitra" 
                :value="$stats['total_perusahaan']" 
                subtitle="Database corporate & industri" 
                color="emerald"
            />
        </div>

        <!-- DASHBOARD TARGET & PENCAPAIAN BERJENJANG (PRD 6.2) -->
        @if(isset($targetAchievementData))
            <x-target-achievement-table 
                :data="$targetAchievementData" 
                title="Dashboard Target & Pencapaian Global (Admin)" 
                subtitle="Monitoring capaian seluruh wilayah teritori, kekurangan, sisa hari, dan target harian berjalan"
            />
        @endif

        <!-- DISTRIBUSI ROLE & DATA MASTER RINGKAS -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Distribusi Role Pengguna -->
            <div class="lg:col-span-7 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Distribusi Role Pengguna</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Proporsi tim operasional CRM UCIC</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                        {{ $stats['total_users'] }} Total User
                    </span>
                </div>

                <div class="space-y-3.5 pt-1">
                    @foreach($userSlides as $item)
                        @php
                            $barBg = match($item['color']) {
                                'blue'   => 'bg-blue-500',
                                'teal'   => 'bg-teal-500',
                                'indigo' => 'bg-indigo-500',
                                'purple' => 'bg-purple-500',
                                default  => 'bg-slate-500',
                            };
                            $badgeCls = match($item['color']) {
                                'blue'   => 'bg-blue-50 text-blue-700 border-blue-200',
                                'teal'   => 'bg-teal-50 text-teal-700 border-teal-200',
                                'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'purple' => 'bg-purple-50 text-purple-700 border-purple-200',
                                default  => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/60 hover:bg-slate-50 transition">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold border {{ $badgeCls }}">
                                        {{ $item['role'] }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-800">{{ $item['title'] }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="font-bold text-slate-900">{{ $item['count'] }} User</span>
                                    <span class="text-slate-400 font-medium">({{ $item['pct'] }}%)</span>
                                </div>
                            </div>
                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                <div class="{{ $barBg }} h-2 rounded-full transition-all duration-700 ease-out" style="width: {{ $item['pct'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Master Data & Mitra Quick Links -->
            <div class="lg:col-span-5 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Data Master & Wilayah</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pintasan manajemen basis data master</p>
                    </div>
                    <a href="{{ route('admin.master-data.index') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700 hover:underline">Kelola &rarr;</a>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <a href="{{ route('admin.wilayah.index') }}" class="p-3.5 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-1 block hover:-translate-y-0.5 duration-200">
                        <div class="font-black text-slate-900 text-xl">{{ $stats['total_wilayah'] }}</div>
                        <div class="font-bold text-slate-700 text-xs">Wilayah</div>
                        <div class="text-[10px] text-slate-400">Cakupan Teritori</div>
                    </a>
                    <a href="{{ route('admin.prodi.index') }}" class="p-3.5 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-1 block hover:-translate-y-0.5 duration-200">
                        <div class="font-black text-slate-900 text-xl">{{ $stats['total_prodi'] }}</div>
                        <div class="font-bold text-slate-700 text-xs">Prodi UCIC</div>
                        <div class="text-[10px] text-slate-400">Program Studi Aktif</div>
                    </a>
                    <a href="{{ route('admin.sekolah.index') }}" class="p-3.5 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-1 block hover:-translate-y-0.5 duration-200">
                        <div class="font-black text-slate-900 text-xl">{{ $stats['total_sekolah'] }}</div>
                        <div class="font-bold text-slate-700 text-xs">Sekolah Mitra</div>
                        <div class="text-[10px] text-slate-400">SMA/SMK/MA</div>
                    </a>
                    <a href="{{ route('admin.perusahaan.index') }}" class="p-3.5 rounded-xl bg-slate-50/80 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-300 transition text-center space-y-1 block hover:-translate-y-0.5 duration-200">
                        <div class="font-black text-slate-900 text-xl">{{ $stats['total_perusahaan'] }}</div>
                        <div class="font-bold text-slate-700 text-xs">Perusahaan</div>
                        <div class="text-[10px] text-slate-400">Mitra Corporate</div>
                    </a>
                </div>

                <div class="pt-2">
                    <a href="{{ route('admin.kunjungan.index') }}" class="p-3.5 rounded-xl bg-purple-50/60 hover:bg-purple-100/60 border border-purple-200/70 transition flex items-center justify-between text-xs block">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">{{ $stats['total_kunjungan'] }} Total Kunjungan</div>
                                <div class="text-[11px] text-slate-500">Kelola & pantau riwayat audiensi sales</div>
                            </div>
                        </div>
                        <span class="font-bold text-purple-700">&rarr;</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- MAIN OPERATIONAL SECTION (2 BALANCED COLUMNS) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <!-- Target Aktif Sales -->
            <div class="bg-white p-5 sm:p-6 shadow-xs rounded-2xl border border-slate-200/80 flex flex-col space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Target Aktif Sales</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Penugasan target berjalan tim sales inbound</p>
                    </div>
                    <a href="{{ route('admin.target.index') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700 hover:underline">Kelola &rarr;</a>
                </div>

                <div class="space-y-3">
                    @forelse($activeTargets as $t)
                        @php
                            $salesName = $t->sales->name ?? 'Sales Staff';
                            $words = array_values(array_filter(explode(' ', trim($salesName))));
                            $init = strtoupper(substr($words[0] ?? 'S', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                        @endphp
                        <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:shadow-xs transition duration-200">
                            <div class="flex items-center justify-between mb-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                                        {{ $init }}
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 block leading-tight">{{ $salesName }}</span>
                                        <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($t->tanggal_mulai)->translatedFormat('M Y') }}</span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                    {{ $t->status }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="p-2 bg-white rounded-lg border border-slate-200/70">
                                    <div class="text-slate-400 text-[10px]">Target Kontak</div>
                                    <div class="font-bold text-slate-800 mt-0.5">{{ $t->target_kontak }}</div>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-slate-200/70">
                                    <div class="text-slate-400 text-[10px]">Follow Up</div>
                                    <div class="font-bold text-slate-800 mt-0.5">{{ $t->target_followup }}</div>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-slate-200/70">
                                    <div class="text-slate-400 text-[10px]">Kunjungan</div>
                                    <div class="font-bold text-slate-800 mt-0.5">{{ $t->target_kunjungan }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 space-y-2">
                            <svg class="w-8 h-8 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p class="text-xs font-semibold text-slate-600">Belum Ada Target Aktif</p>
                            <a href="{{ route('admin.target.index') }}" class="text-xs font-bold text-purple-600 hover:text-purple-700 hover:underline">+ Buat Target Sales Baru</a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Pengguna Terdaftar Terbaru -->
            <div class="bg-white p-5 sm:p-6 shadow-xs rounded-2xl border border-slate-200/80 flex flex-col space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Pengguna Terbaru</h3>
                        <p class="text-xs text-slate-500 mt-0.5">User yang baru terdaftar dalam database CRM</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700 hover:underline">Semua &rarr;</a>
                </div>

                <div class="space-y-2.5">
                    @forelse($recentUsers as $usr)
                        @php
                            $words = array_values(array_filter(explode(' ', trim($usr->name))));
                            $avatarInit = strtoupper(substr($words[0] ?? 'U', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                            $roleCls = match($usr->role) {
                                'Sales' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'CS'    => 'bg-teal-50 text-teal-800 border-teal-200',
                                'SPV'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'HM'    => 'bg-purple-50 text-purple-700 border-purple-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                            $isAktif = strtolower($usr->status ?? 'aktif') === 'aktif';
                        @endphp
                        <div class="p-3 rounded-xl bg-slate-50/60 border border-slate-200/70 hover:bg-white hover:shadow-xs transition duration-200 flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                @if($usr->avatar_url)
                                    <img src="{{ $usr->avatar_url }}" alt="{{ $usr->name }}" class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xs shrink-0\'>{{ $avatarInit }}</div>';">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-xs">
                                        {{ $avatarInit }}
                                    </div>
                                @endif
                                <div class="truncate">
                                    <div class="font-bold text-slate-900 truncate text-xs">{{ $usr->name }}</div>
                                    <div class="text-[11px] text-slate-400 truncate">{{ $usr->email }}</div>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $roleCls }} inline-block">
                                    {{ $usr->role }}
                                </span>
                                <div class="text-[10px] font-semibold mt-1 {{ $isAktif ? 'text-emerald-600' : 'text-rose-600' }}">
                                    ● {{ ucfirst($usr->status ?? 'Aktif') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                            <p class="text-xs">Belum ada data pengguna</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- AKTIVITAS TERBARU SISTEM -->
        <div class="bg-white p-5 sm:p-6 space-y-4 shadow-xs rounded-2xl border border-slate-200/80">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Aktivitas Terbaru Sistem</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Seluruh aktivitas pengguna dalam sistem hari ini</p>
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
                            @php
                                $actWords = array_values(array_filter(explode(' ', trim($act['user']))));
                                $actInit = strtoupper(substr($actWords[0] ?? 'U', 0, 1) . (isset($actWords[1]) ? substr($actWords[1], 0, 1) : ''));
                                $actRoleCls = match($act['role']) {
                                    'Sales' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'CS'    => 'bg-teal-50 text-teal-800 border-teal-200',
                                    'SPV'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'HM'    => 'bg-purple-50 text-purple-700 border-purple-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-[10px] shrink-0">
                                            {{ $actInit }}
                                        </div>
                                        <span class="font-bold text-slate-900">{{ $act['user'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $actRoleCls }}">{{ $act['role'] }}</span>
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
