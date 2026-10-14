@php
    $pageTitle = 'Target & Performa';
    $pageSubtitle = 'Capaian Registrasi & Evaluasi Penjualan Inbound';
@endphp

<x-app-layout :title="'Target & Performa - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Data target belum diatur" 
            description="Target periode ini belum dikonfigurasi oleh Head Marketing."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Header with Month Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs"
             x-data="{
                selectedPeriode: '{{ $selectedPeriode ?? request('periode', 'bulan-ini') }}',
                tsInstance: null,
                initTomSelect() {
                    this.$nextTick(() => {
                        if (typeof TomSelect !== 'undefined' && this.$refs.periodeSelect && !this.tsInstance) {
                            this.tsInstance = new TomSelect(this.$refs.periodeSelect, {
                                create: false,
                                maxItems: 1,
                                allowEmptyOption: false,
                                placeholder: 'Ketik untuk cari periode...',
                                plugins: ['dropdown_input'],
                                sortField: [{ field: '$order' }],
                                onChange: (value) => {
                                    if (value && value !== this.selectedPeriode) {
                                        this.selectedPeriode = value;
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('periode', value);
                                        window.location.href = url.toString();
                                    }
                                }
                            });
                            this.tsInstance.setValue(this.selectedPeriode, true);
                        }
                    });
                }
             }"
             x-init="initTomSelect()">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Evaluasi Target & Performa</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau target institusi dan efektivitas closing per individu tim marketing.</p>
            </div>
            
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 shrink-0">Pilih Periode:</span>
                <div class="relative w-full sm:w-[280px]">
                    <select x-ref="periodeSelect" class="tom-select-periode">
                        <option value="semua">Semua Periode (Semua Waktu)</option>

                        <optgroup label="── Per Hari (Harian) ──">
                            <option value="hari-ini">Hari Ini</option>
                            <option value="kemarin">Kemarin</option>
                        </optgroup>

                        <optgroup label="── Per Minggu (Mingguan) ──">
                            <option value="minggu-ini">Minggu Ini</option>
                            <option value="minggu-lalu">Minggu Lalu</option>
                        </optgroup>

                        <optgroup label="── Per Bulan (Bulanan) ──">
                            <option value="bulan-ini">Bulan Ini ({{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM YYYY') }})</option>
                            <option value="bulan-lalu">Bulan Lalu ({{ \Carbon\Carbon::now()->subMonth()->locale('id')->isoFormat('MMMM YYYY') }})</option>
                            <option value="jan">Januari</option>
                            <option value="feb">Februari</option>
                            <option value="mar">Maret</option>
                            <option value="apr">April</option>
                            <option value="mei">Mei</option>
                            <option value="jun">Juni</option>
                            <option value="jul">Juli</option>
                            <option value="agu">Agustus</option>
                            <option value="sep">September</option>
                            <option value="okt">Oktober</option>
                            <option value="nov">November</option>
                            <option value="des">Desember</option>
                        </optgroup>

                        <optgroup label="── Per Tahun (Tahunan) ──">
                            <option value="tahun-ini">Tahun {{ \Carbon\Carbon::now()->year }} (Tahun Ini)</option>
                            <option value="tahun-lalu">Tahun {{ \Carbon\Carbon::now()->year - 1 }} (Tahun Lalu)</option>
                            <option value="ta-2026-2027">Tahun Akademik {{ $ta ?? '2026/2027' }}</option>
                        </optgroup>
                    </select>
                </div>
            </div>
        </div>

        <!-- DASHBOARD TARGET & PENCAPAIAN BERJENJANG (PRD 6.2) -->
        @if(isset($targetAchievementData))
            <x-target-achievement-table 
                :data="$targetAchievementData" 
                title="Target & Pencapaian Sales Pribadi" 
                subtitle="Monitoring target harian, mingguan, bulanan, tahunan, kekurangan, dan target harian berjalan"
            />
        @endif

        <!-- Big Progress Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-stat-card 
                title="Target Kuota Mahasiswa" 
                :value="$summary['target'] . ' Prospek'" 
                subtitle="Target Total {{ $summary['periode_label'] }}" 
                color="blue"
            />
            <x-stat-card 
                title="Realisasi Closing" 
                :value="$summary['realisasi'] . ' Mhs'" 
                subtitle="Terdaftar & Bayar" 
                trend="+0%"
                :trendUp="true"
                color="emerald"
            />
            <x-stat-card 
                title="Pencapaian (Achievement)" 
                :value="$summary['achievement'] . '%'" 
                subtitle="Progress Terhadap Target" 
                color="indigo"
            />
            <x-stat-card 
                title="Sisa Target" 
                :value="$summary['sisa_target'] . ' Prospek'" 
                subtitle="Perlu Dicapai" 
                color="amber"
            />
        </div>

        <!-- Big Progress Visualization Bar -->
        <div class="crm-card bg-white p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Pencapaian Target Kampus ({{ $summary['periode_label'] }})</h3>
                    <p class="text-xs text-slate-500">Realisasi {{ $summary['realisasi'] }} dari total target {{ $summary['target'] }} mahasiswa baru jalur inbound</p>
                </div>
                <span class="text-sm font-extrabold text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-100">
                    {{ $summary['achievement'] }}% Tercapai
                </span>
            </div>

            <div class="w-full bg-slate-100 rounded-full h-4 overflow-hidden p-0.5 border border-slate-200/60">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ $summary['achievement'] }}%"></div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 text-xs text-slate-500 border-t border-slate-100">
                <div><span class="text-slate-400">Jalur PMDK Sekolah:</span> <strong class="text-slate-800">{{ $summary['breakdown']['Sekolah'] ?? 0 }} Siswa</strong></div>
                <div><span class="text-slate-400">Kelas Corporate:</span> <strong class="text-slate-800">{{ $summary['breakdown']['Corporate'] ?? 0 }} Peserta</strong></div>
                <div><span class="text-slate-400">Reguler Individu:</span> <strong class="text-slate-800">{{ $summary['breakdown']['Individu'] ?? 0 }} Siswa</strong></div>
            </div>
        </div>

        <!-- Table Performance Per Sales -->
        <div class="crm-card bg-white p-6">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                Rincian Performa
            </h3>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-3 w-10 text-center">No</th>
                            <th class="py-3 px-4">Sales Representative</th>
                            <th class="py-3 px-3 text-center">Target</th>
                            <th class="py-3 px-3 text-center">Total Prospek</th>
                            <th class="py-3 px-3 text-center">Follow Up</th>
                            <th class="py-3 px-3 text-center">Closing</th>
                            <th class="py-3 px-3 text-center">Lost</th>
                            <th class="py-3 px-4 text-center w-40">Pencapaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($team as $member)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400">{{ $loop->iteration }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900 flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs">
                                        {{ $member['avatar'] }}
                                    </div>
                                    <div>
                                        <div>{{ $member['name'] }}</div>
                                        <div class="text-[11px] text-slate-400 font-normal">{{ $member['role'] }}</div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800">{{ $member['target'] }}</td>
                                <td class="py-3.5 px-3 text-center text-slate-700 font-medium">{{ $member['prospects'] }}</td>
                                <td class="py-3.5 px-3 text-center text-amber-700 font-semibold">{{ $member['follow_up'] ?? 0 }}</td>
                                <td class="py-3.5 px-3 text-center text-emerald-700 font-bold text-sm">{{ $member['closing'] ?? $member['LUNAS'] ?? 0 }}</td>
                                <td class="py-3.5 px-3 text-center text-rose-600 font-medium">{{ $member['lost'] ?? $member['DINGIN'] ?? 0 }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $member['achievement'] >= 70 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                        {{ $member['achievement'] }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-slate-100">
                @foreach($team as $member)
                    <div class="py-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-xs text-slate-900">{{ $member['name'] }}</h4>
                            <span class="text-xs font-bold text-blue-600">{{ $member['achievement'] }}% Target</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-600">
                            <span>Target: <strong>{{ $member['target'] }}</strong></span>
                            <span>Closing: <strong class="text-emerald-600">{{ $member['closing'] ?? $member['LUNAS'] ?? 0 }}</strong></span>
                            <span>Lost: <strong class="text-rose-600">{{ $member['lost'] ?? $member['DINGIN'] ?? 0 }}</strong></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <!-- Custom Styling for TomSelect Searchable Periode Dropdown -->
    <style>
        .ts-wrapper.tom-select-periode {
            min-width: 260px;
            width: 100%;
            position: relative;
        }
        .ts-wrapper.tom-select-periode .ts-control {
            border-radius: 0.75rem !important;
            padding: 0.5rem 0.875rem !important;
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            min-height: 38px !important;
            box-shadow: none !important;
            display: flex !important;
            align-items: center !important;
            cursor: pointer !important;
            transition: all 0.15s ease-in-out !important;
        }
        .ts-wrapper.tom-select-periode.focus .ts-control,
        .ts-wrapper.tom-select-periode.dropdown-active .ts-control {
            border-color: #3b82f6 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }
        .ts-wrapper.tom-select-periode .ts-dropdown {
            border-radius: 0.75rem !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            font-size: 0.75rem !important;
            margin-top: 4px !important;
            padding: 4px !important;
            z-index: 60 !important;
            background: #ffffff !important;
            max-height: 280px !important;
        }
        .ts-wrapper.tom-select-periode .dropdown-input-wrap {
            padding: 6px 8px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            position: relative !important;
        }
        .ts-wrapper.tom-select-periode .dropdown-input {
            border-radius: 0.5rem !important;
            border: 1px solid #cbd5e1 !important;
            padding: 0.45rem 0.75rem 0.45rem 2rem !important;
            font-size: 0.75rem !important;
            width: 100% !important;
            outline: none !important;
            box-sizing: border-box !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: 8px center !important;
            background-size: 14px 14px !important;
        }
        .ts-wrapper.tom-select-periode .dropdown-input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
        }
        .ts-wrapper.tom-select-periode .optgroup-header {
            font-size: 0.65rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: #94a3b8 !important;
            padding: 6px 10px 2px !important;
            background: #f8fafc !important;
            border-radius: 0.375rem !important;
            margin-top: 4px !important;
        }
        .ts-wrapper.tom-select-periode .option {
            padding: 6px 10px !important;
            border-radius: 0.375rem !important;
            font-weight: 500 !important;
            color: #334155 !important;
            cursor: pointer !important;
        }
        .ts-wrapper.tom-select-periode .option.active {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
            font-weight: 600 !important;
        }
        .ts-wrapper.tom-select-periode .option.selected {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }
    </style>

</x-app-layout>
