@php
    $pageTitle = 'Target & Performa Tim (SPV)';
    $pageSubtitle = 'Evaluasi Pencapaian, Performa Tim & Alokasi Target';
@endphp

<x-app-layout :title="'Target & Performa Tim - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        activeTab: 'semua',
        modalAlokasi: false,
        selectedSalesId: '',
        tipePeriode: 'Bulanan',
        tanggalMulai: '{{ now()->startOfMonth()->toDateString() }}',
        tanggalSelesai: '{{ now()->endOfMonth()->toDateString() }}',
        targetKontak: 30,
        targetFormulir: 15,
        targetLunas: 8
    }">

        <!-- Page Header & Academic Year Indicator -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Evaluasi Target & Performa Tim</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">TA {{ $ta }}</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Memonitor target mingguan dari HM, akumulasi realisasi, defisit target, alokasi target Sales/CS, dan performa tim.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    @click="modalAlokasi = true" 
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Alokasikan Target ke Tim</span>
                </button>
            </div>
        </div>

        <!-- DASHBOARD TARGET & PENCAPAIAN BERJENJANG (PRD 6.2) -->
        @if(isset($targetAchievementData))
            <x-target-achievement-table 
                :data="$targetAchievementData" 
                title="Dashboard Target & Pencapaian Tim SPV" 
                subtitle="Filter periode Harian, Mingguan, Bulanan, Tahunan, dan Realtime dengan rincian metrik per baris"
            />
        @endif

        <!-- 1. RINGKASAN TARGET HM KE SPV & ALOKASI TIM -->
        <div class="crm-card bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 rounded-2xl shadow-md space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-white/10">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-300">Target Wilayah dari Head Marketing (HM)</span>
                    <h3 class="text-lg sm:text-xl font-extrabold text-white mt-0.5">{{ $targetHm['wilayah'] }} &bull; Periode {{ $targetHm['tipe_periode'] }} (TA {{ $targetHm['tahun_akademik'] }})</h3>
                    <p class="text-xs text-slate-300 mt-1">Status Target HM: <span class="text-emerald-400 font-semibold font-mono">LOCKED BY HM (Read-Only SPV)</span></p>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <div class="text-right">
                        <span class="text-slate-400 block text-[10px] uppercase">Rasio Pencapaian Tim</span>
                        <span class="text-xl font-extrabold text-emerald-400">{{ $targetHm['achieve_pct'] }}%</span>
                    </div>
                </div>
            </div>

            <!-- Metrik Target vs Alokasi vs Realisasi -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white/5 border border-white/10 p-3.5 rounded-xl">
                    <span class="text-[10px] uppercase text-slate-400 font-semibold block">Target Kontak HM</span>
                    <span class="text-base font-bold text-white mt-1 block">{{ $targetHm['target_kontak'] }}</span>
                    <span class="text-[10px] text-slate-300">Dialokasi: {{ $targetHm['allocated_kontak'] }}</span>
                </div>
                <div class="bg-white/5 border border-white/10 p-3.5 rounded-xl">
                    <span class="text-[10px] uppercase text-slate-400 font-semibold block">Target Formulir HM</span>
                    <span class="text-base font-bold text-white mt-1 block">{{ $targetHm['target_formulir'] }}</span>
                    <span class="text-[10px] text-slate-300">Dialokasi: {{ $targetHm['allocated_formulir'] }}</span>
                </div>
                <div class="bg-white/5 border border-white/10 p-3.5 rounded-xl">
                    <span class="text-[10px] uppercase text-emerald-400 font-semibold block">Target Maba Lunas</span>
                    <span class="text-base font-bold text-emerald-300 mt-1 block">{{ $targetHm['target_lunas'] }}</span>
                    <span class="text-[10px] text-emerald-200">Dialokasi: {{ $targetHm['allocated_lunas'] }}</span>
                </div>
                <div class="bg-white/5 border border-white/10 p-3.5 rounded-xl">
                    <span class="text-[10px] uppercase text-amber-300 font-semibold block">Sisa Target Kontak</span>
                    <span class="text-base font-bold text-amber-200 mt-1 block">{{ $targetHm['sisa_kontak'] }}</span>
                    <span class="text-[10px] text-slate-300">Belum di-assign</span>
                </div>
                <div class="bg-white/5 border border-white/10 p-3.5 rounded-xl">
                    <span class="text-[10px] uppercase text-amber-300 font-semibold block">Sisa Target Lunas</span>
                    <span class="text-base font-bold text-amber-200 mt-1 block">{{ $targetHm['sisa_lunas'] }}</span>
                    <span class="text-[10px] text-slate-300">Belum di-assign</span>
                </div>
                <div class="bg-white/5 border border-white/10 p-3.5 rounded-xl">
                    <span class="text-[10px] uppercase text-blue-300 font-semibold block">Realisasi Lunas Tim</span>
                    <span class="text-base font-bold text-blue-200 mt-1 block">{{ $targetHm['realisasi_lunas'] }} / {{ $targetHm['target_lunas'] }}</span>
                    <span class="text-[10px] text-slate-300">Sisa Capaian: {{ max(0, $targetHm['target_lunas'] - $targetHm['realisasi_lunas']) }}</span>
                </div>
            </div>
        </div>

        <!-- 2. TABEL PERFORMA HARIAN / PERORANG -->
        <div class="crm-card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">PERORANG (PERFORMA HARIAN & UTANG ANGKA)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Evaluasi perorangan Sales: Target Normal, Capaian Kemarin, Otorisasi Kunci Defisit oleh SPV, Sasaran Hari Ini, dan Lokasi Penugasan.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="text-[11px] text-slate-500 font-medium bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                        Hari ini: <strong class="text-slate-800">{{ $performaHarian['tanggal_hari_ini'] }}</strong> (Kemarin: {{ $performaHarian['tanggal_kemarin'] }})
                    </div>
                    @if($performaHarian['any_deficit_unlocked'] ?? false)
                        <form action="{{ route('spv.performa.kunciDefisit') }}" method="POST">
                            @csrf
                            <input type="hidden" name="sales_id" value="all">
                            <input type="hidden" name="tanggal" value="{{ $performaHarian['tanggal_kemarin_raw'] }}">
                            <input type="hidden" name="action" value="lock">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Kunci Defisit Kemarin ({{ $performaHarian['total_utang_kontak'] }} Kontak)
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs border border-slate-200">
                    <thead>
                        <tr class="bg-slate-100/90 text-slate-800 font-bold uppercase tracking-wider border-b border-slate-300 text-center">
                            <th rowspan="2" class="py-3 px-4 border-r border-slate-200 text-left">NAMA TIM (SALES / CS)</th>
                            <th rowspan="2" class="py-3 px-3 border-r border-slate-200 bg-blue-50/60 text-blue-900">TARGET NORMAL</th>
                            <th colspan="3" class="py-2 px-3 border-r border-slate-200 bg-slate-200/70 text-slate-800">Capaian Kemarin (kontak/Form/Lunas)</th>
                            <th rowspan="2" class="py-3 px-3 border-r border-slate-200 bg-rose-50/70 text-rose-900">UTANG ANGKA<br><span class="text-[9px] font-normal lowercase">(wajib dibayar)</span></th>
                            <th rowspan="2" class="py-3 px-3 border-r border-slate-200 bg-indigo-50/70 text-indigo-900">SASARAN HARI INI<br><span class="text-[9px] font-normal lowercase">(target normal + utang)</span></th>
                            <th rowspan="2" class="py-3 px-4 text-left bg-slate-50">Lokasi / Penugasan Hari ini</th>
                        </tr>
                        <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200 text-center text-[10px]">
                            <th class="py-1.5 px-2.5 border-r border-slate-200">Kontak</th>
                            <th class="py-1.5 px-2.5 border-r border-slate-200">FORMULIR</th>
                            <th class="py-1.5 px-2.5 border-r border-slate-200">Lunas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium">
                        @forelse($performaHarian['rows'] as $salesRow)
                            <tr class="hover:bg-slate-50/80 transition {{ ($salesRow['is_cs'] ?? false) ? 'bg-violet-50/30' : '' }}">
                                <td class="py-3.5 px-4 border-r border-slate-200 font-bold text-slate-900">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full {{ ($salesRow['is_cs'] ?? false) ? 'bg-purple-600' : 'bg-blue-600' }} text-white font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ $salesRow['avatar'] }}
                                        </div>
                                        <div>
                                            <span>{{ $salesRow['nama_sales'] ?? $salesRow['nama'] }}</span>
                                            @if($salesRow['is_cs'] ?? false)
                                                <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-violet-100 text-violet-700">CS</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center border-r border-slate-200 font-semibold text-blue-900 bg-blue-50/20">
                                    {{ $salesRow['target_normal']['summary'] }}
                                </td>
                                <!-- Sub-kolom Capaian Kemarin -->
                                <td class="py-3.5 px-2.5 text-center border-r border-slate-200 font-bold text-slate-800">
                                    {{ $salesRow['capaian_kemarin']['kontak'] }}
                                </td>
                                <td class="py-3.5 px-2.5 text-center border-r border-slate-200 font-bold text-purple-700">
                                    {{ $salesRow['capaian_kemarin']['formulir'] }}
                                </td>
                                <td class="py-3.5 px-2.5 text-center border-r border-slate-200 font-bold text-emerald-700">
                                    {{ $salesRow['capaian_kemarin']['lunas'] }}
                                </td>
                                <!-- Utang Angka (Carry Over & Kunci SPV) -->
                                <td class="py-3.5 px-3 text-center border-r border-slate-200 {{ $salesRow['utang_angka']['has_utang'] ? 'bg-rose-50/60' : 'text-slate-400' }}">
                                    <div class="space-y-1">
                                        <span class="block font-bold text-xs {{ $salesRow['utang_angka']['has_utang'] ? 'text-rose-700' : 'text-slate-400' }}">
                                            {{ $salesRow['utang_angka']['summary'] }}
                                        </span>
                                        @if($salesRow['utang_angka']['has_utang'])
                                            @if($salesRow['defisit_lock']['is_locked'] ?? false)
                                                <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                    Terkunci SPV
                                                </div>
                                                <form action="{{ route('spv.performa.kunciDefisit') }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="sales_id" value="{{ $salesRow['member_id'] }}">
                                                    <input type="hidden" name="tanggal" value="{{ $salesRow['defisit_lock']['tanggal'] }}">
                                                    <input type="hidden" name="action" value="unlock">
                                                    <button type="submit" class="text-[9px] text-slate-400 hover:text-rose-600 underline block mx-auto mt-0.5" title="Buka Kuncian Defisit">buka</button>
                                                </form>
                                            @else
                                                <form action="{{ route('spv.performa.kunciDefisit') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="sales_id" value="{{ $salesRow['member_id'] }}">
                                                    <input type="hidden" name="tanggal" value="{{ $salesRow['defisit_lock']['tanggal'] }}">
                                                    <input type="hidden" name="action" value="lock">
                                                    <input type="hidden" name="defisit_kontak" value="{{ $salesRow['utang_angka']['kontak'] }}">
                                                    <input type="hidden" name="defisit_formulir" value="{{ $salesRow['utang_angka']['formulir'] }}">
                                                    <input type="hidden" name="defisit_lunas" value="{{ $salesRow['utang_angka']['lunas'] }}">
                                                    <button type="submit" class="px-2 py-0.5 rounded text-[9px] font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-2xs transition inline-flex items-center gap-0.5">
                                                        <span>Kunci Defisit</span>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <!-- Sasaran Hari Ini -->
                                <td class="py-3.5 px-3 text-center border-r border-slate-200 font-extrabold bg-indigo-50/30 text-indigo-950">
                                    {{ $salesRow['sasaran_hari_ini']['summary'] }}
                                </td>
                                <!-- Lokasi Penugasan -->
                                <td class="py-3.5 px-4 text-slate-700">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="font-medium text-xs">{{ $salesRow['lokasi_penugasan'] }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400 text-xs">Belum ada personil Sales dalam tim.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. TABEL TARGET TEAM PER WEEK -->
        <div class="crm-card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">TARGET TEAM PER WEEK</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Monitoring mingguan: Target Kontak, Realisasi Kontak, Pembayaran Formulir, dan Maba Lunas Kumulatif (Periode: {{ $teamPerWeek['periode_label'] }})</p>
                </div>
                <div class="text-[11px] text-slate-400 font-medium">
                    Syarat Maba Lunas: <strong class="text-emerald-700">Formulir + Termin 1 Lunas</strong>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs border border-slate-200">
                    <thead>
                        <tr class="bg-slate-100/80 text-slate-800 font-bold uppercase tracking-wider border-b border-slate-300">
                            <th class="py-3 px-4 border-r border-slate-200">HARI</th>
                            <th class="py-3 px-3 text-center border-r border-slate-200 bg-blue-50/50 text-blue-900">TARGET KONTAK</th>
                            <th class="py-3 px-3 text-center border-r border-slate-200">REALISASI KONTAK</th>
                            <th class="py-3 px-3 text-center border-r border-slate-200 bg-rose-50/50 text-rose-800">UTANG ANGKA<br><span class="text-[9px] font-normal lowercase">(carry-over)</span></th>
                            <th class="py-3 px-3 text-center border-r border-slate-200 bg-purple-50/50 text-purple-900">PEMBAYARAN FORMULIR</th>
                            <th class="py-3 px-3 text-center border-r border-slate-200 bg-emerald-50/50 text-emerald-900">MABA LUNAS</th>
                            <th class="py-3 px-4 text-center bg-indigo-50/50 text-indigo-900 font-extrabold">KUMULATIF LUNAS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium">
                        @foreach($teamPerWeek['rows'] as $row)
                            <tr class="hover:bg-slate-50/80 transition {{ $row['is_today'] ? 'bg-amber-50/60 font-bold border-l-4 border-amber-400' : ($row['is_future'] ? 'opacity-60' : '') }}">
                                <td class="py-3 px-4 border-r border-slate-200 text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold">{{ $row['hari'] }}</span>
                                        @if($row['is_today'])
                                            <span class="px-1.5 py-0.5 rounded text-[9px] bg-amber-300 text-amber-900 font-bold">HARI INI</span>
                                        @endif
                                        <span class="text-[10px] text-slate-400 font-normal">({{ $row['tanggal'] }})</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center border-r border-slate-200 text-blue-800 font-semibold">{{ $row['target_kontak'] }}</td>
                                <td class="py-3 px-3 text-center border-r border-slate-200 font-bold {{ $row['realisasi_kontak'] >= $row['sasaran_kontak'] && $row['sasaran_kontak'] > 0 ? 'text-emerald-600' : 'text-slate-800' }}">
                                    {{ $row['realisasi_kontak'] }}
                                    @if(!$row['is_future'] && $row['sasaran_kontak'] > $row['target_kontak'])
                                        <span class="text-[9px] text-rose-500 block">(Sasaran: {{ $row['sasaran_kontak'] }})</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center border-r border-slate-200">
                                    @if($row['has_utang'] && !$row['is_future'])
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
                                            <span class="text-rose-700 font-bold">{{ $row['utang_kontak'] }}</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                     @endif
                                </td>
                                <td class="py-3 px-3 text-center border-r border-slate-200 text-purple-700 font-bold">{{ $row['pembayaran_formulir'] }}</td>
                                <td class="py-3 px-3 text-center border-r border-slate-200 text-emerald-700 font-extrabold text-sm">{{ $row['maba_lunas'] }}</td>
                                <td class="py-3 px-4 text-center bg-indigo-50/20 text-indigo-900 font-black text-sm">{{ $row['kumulatif_lunas'] }}</td>
                            </tr>
                        @endforeach
                        <!-- Row TOTAL -->
                        <tr class="bg-slate-200/90 font-black text-slate-900 border-t-2 border-slate-300">
                            <td class="py-3.5 px-4 border-r border-slate-300 text-slate-900 font-extrabold text-sm tracking-wider">
                                {{ $teamPerWeek['total']['hari'] }}
                            </td>
                            <td class="py-3.5 px-3 text-center border-r border-slate-300 text-blue-900 font-extrabold">{{ $teamPerWeek['total']['target_kontak'] }}</td>
                            <td class="py-3.5 px-3 text-center border-r border-slate-300 text-slate-900 font-extrabold">{{ $teamPerWeek['total']['realisasi_kontak'] }}</td>
                            <td class="py-3.5 px-3 text-center border-r border-slate-300 text-rose-700 font-extrabold">{{ $teamPerWeek['total']['utang_kontak'] > 0 ? $teamPerWeek['total']['utang_kontak'] : '-' }}</td>
                            <td class="py-3.5 px-3 text-center border-r border-slate-300 text-purple-900 font-extrabold">{{ $teamPerWeek['total']['pembayaran_formulir'] }}</td>
                            <td class="py-3.5 px-3 text-center border-r border-slate-300 text-emerald-900 font-extrabold text-base">{{ $teamPerWeek['total']['maba_lunas'] }}</td>
                            <td class="py-3.5 px-4 text-center bg-indigo-100 text-indigo-950 font-black text-base">{{ $teamPerWeek['total']['kumulatif_lunas'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. DATA AKUMULASI TIM (TA 2027/2028) -->
        <div class="crm-card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Data Akumulasi & Pencapaian Kumulatif Tim</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Rekapitulasi total potensi mahasiswa, formulir, dan penutupan closing Maba Lunas dalam cakupan wilayah SPV.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">Tahun Akademik: <strong>{{ $akumulasi['tahun_akademik'] }}</strong></span>
                </div>
            </div>

            <!-- Big Stats Cards Data Akumulasi -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <x-stat-card 
                    title="Total Potensi Mahasiswa" 
                    :value="$akumulasi['total_prospek'] . ' Data'" 
                    subtitle="Prospek Terdata Tim" 
                    color="blue"
                />
                <x-stat-card 
                    title="Total Pembayaran Formulir" 
                    :value="$akumulasi['total_formulir'] . ' Siswa'" 
                    subtitle="Sudah Beli Form" 
                    color="indigo"
                />
                <x-stat-card 
                    title="Total Maba Lunas" 
                    :value="$akumulasi['total_closing_maba'] . ' Mhs'" 
                    subtitle="Formulir + Termin 1" 
                    color="emerald"
                />
                <x-stat-card 
                    title="Sisa Target HM" 
                    :value="$akumulasi['target_tim_hm']['sisa_lunas'] . ' Mhs'" 
                    subtitle="Dari Target HM ({{ $akumulasi['target_tim_hm']['target_lunas'] }})" 
                    color="amber"
                />
            </div>

            <!-- Breakdown per Personil Sales -->
            <div class="overflow-x-auto pt-2">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4">Personil Sales</th>
                            <th class="py-3 px-3 text-center">Potensi Mahasiswa</th>
                            <th class="py-3 px-3 text-center">Pembayaran Formulir</th>
                            <th class="py-3 px-3 text-center">Maba Lunas</th>
                            <th class="py-3 px-3 text-center">Target Lunas</th>
                            <th class="py-3 px-4 text-center w-52">Rasio Capaian (%)</th>
                            <th class="py-3 px-4 text-center w-40">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($akumulasi['sales_breakdown'] as $sb)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs">
                                            {{ $sb['avatar'] }}
                                        </div>
                                        <span>{{ $sb['name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center text-slate-700 font-medium">{{ $sb['prospek'] }}</td>
                                <td class="py-3 px-3 text-center text-purple-700 font-semibold">{{ $sb['formulir'] }}</td>
                                <td class="py-3 px-3 text-center text-emerald-700 font-bold text-sm">{{ $sb['lunas'] }}</td>
                                <td class="py-3 px-3 text-center font-bold text-slate-700">{{ $sb['target_lunas'] }}</td>
                                <td class="py-3 px-4">
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                            <span>{{ $sb['achievement_pct'] }}%</span>
                                            <span class="text-slate-400">{{ $sb['lunas'] }}/{{ $sb['target_lunas'] }}</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full {{ $sb['achievement_pct'] >= 100 ? 'bg-emerald-500' : ($sb['achievement_pct'] >= 50 ? 'bg-blue-500' : 'bg-amber-500') }}" style="width: {{ min(100, $sb['achievement_pct']) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap border {{ $sb['target_lunas'] <= 0 ? 'bg-slate-50 text-slate-600 border-slate-200' : ($sb['achievement_pct'] >= 100 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($sb['achievement_pct'] >= 50 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200')) }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $sb['target_lunas'] <= 0 ? 'bg-slate-400' : ($sb['achievement_pct'] >= 100 ? 'bg-emerald-500' : ($sb['achievement_pct'] >= 50 ? 'bg-blue-500' : 'bg-amber-500')) }}"></span>
                                        {{ $sb['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL ALOKASI TARGET KE SALES & CS -->
        <div x-show="modalAlokasi" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="modalAlokasi = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Alokasikan Target ke Tim Sales / CS</h3>
                        <p class="text-xs text-slate-500">Breakdown target yang diterima dari HM kepada personil tim.</p>
                    </div>
                    <button type="button" @click="modalAlokasi = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('spv.performa.alokasi') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Pilih Anggota Tim (Sales / CS)</label>
                        <select name="sales_id" x-model="selectedSalesId" required class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20">
                            <option value="">-- Pilih Anggota Tim --</option>
                            <optgroup label="Personil Sales">
                                @foreach($teamSales as $ts)
                                    <option value="{{ $ts->id }}">{{ $ts->name }} (Sales)</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Customer Service (CS)">
                                @foreach($teamCs as $tc)
                                    <option value="{{ $tc->id }}">{{ $tc->name }} (CS - Fokus Konversi Form ke Lunas)</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tipe Periode</label>
                            <select name="tipe_periode" x-model="tipePeriode" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium">
                                <option value="Harian">Harian</option>
                                <option value="Mingguan">Mingguan</option>
                                <option value="Bulanan">Bulanan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tahun Akademik</label>
                            <input type="text" name="tahun_akademik" value="{{ $ta }}" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium" readonly>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" x-model="tanggalMulai" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" x-model="tanggalSelesai" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 pt-1">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Target Kontak</label>
                            <input type="number" name="target_kontak" x-model="targetKontak" min="0" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold">
                            <span class="text-[10px] text-slate-400">Sisa Kuota: {{ $targetHm['sisa_kontak'] }}</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Target Formulir</label>
                            <input type="number" name="target_formulir" x-model="targetFormulir" min="0" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold">
                            <span class="text-[10px] text-slate-400">Sisa Kuota: {{ $targetHm['sisa_formulir'] }}</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Target Maba Lunas</label>
                            <input type="number" name="target_lunas" x-model="targetLunas" min="0" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold">
                            <span class="text-[10px] text-emerald-600">Sisa Kuota: {{ $targetHm['sisa_lunas'] }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="modalAlokasi = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-semibold cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-xs cursor-pointer">Simpan Alokasi Target</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</x-app-layout>
