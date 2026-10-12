@php
    $pageTitle = 'Dashboard Customer Service (CS)';
    $pageSubtitle = 'Follow-up Inbound, Verifikasi Transaksi & Serah Terima';
@endphp

<x-app-layout :title="'Dashboard CS - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
        <div class="mt-6">
            <x-loading-skeleton type="table" />
        </div>
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Tidak ada antrian follow-up CS" 
            description="Semua follow-up hari ini telah selesai diproses. Periksa tab takeover untuk prospek baru."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Header Greeting -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Halo, {{ auth()->user()->name }} 👋</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">Customer Service Lead</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Pusat kendali operasional: Validasi pembayaran Termin 1, tindak lanjut lead serah terima, dan follow-up harian.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a 
                    href="{{ route('cs.verifikasi.index') }}"
                    class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Verifikasi Bayar ({{ $pendingPaymentCount ?? 0 }})</span>
                </a>
                <a 
                    href="{{ route('follow-up.index') }}"
                    class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span>Buka Follow Up</span>
                </a>
            </div>
        </div>

        {{-- ALERT BANNER: Notifikasi Pembayaran / SLA Overdue --}}
        @if(($pendingPaymentCount ?? 0) > 0 || ($slaOverdueCount ?? 0) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(($pendingPaymentCount ?? 0) > 0)
                    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-start justify-between gap-3 shadow-xs">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 font-bold text-sm shadow-xs">
                                💳
                            </div>
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold text-amber-950">Ada {{ $pendingPaymentCount }} Pembayaran Perlu Diverifikasi</h4>
                                <p class="text-[11px] text-amber-800 mt-0.5">Total nominal <strong>Rp {{ number_format($pendingPaymentSum ?? 0, 0, ',', '.') }}</strong> menunggu validasi CS agar prospek sah Closing.</p>
                            </div>
                        </div>
                        <a href="{{ route('cs.verifikasi.index') }}" class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold shrink-0 transition shadow-xs">
                            Periksa
                        </a>
                    </div>
                @endif

                @if(($slaOverdueCount ?? 0) > 0)
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-start justify-between gap-3 shadow-xs">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 font-bold text-sm shadow-xs">
                                🚨
                            </div>
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold text-rose-950">{{ $slaOverdueCount }} Prospek Lewat Batas SLA 2 Jam</h4>
                                <p class="text-[11px] text-rose-800 mt-0.5">Prospek formulir serah terima belum di-follow up sejak handover. Alarm terkirim ke Supervisor.</p>
                            </div>
                        </div>
                        <a href="#section-handover" class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold shrink-0 transition shadow-xs">
                            Tindak Lanjut
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- DASHBOARD TARGET & PENCAPAIAN BERJENJANG (PRD 6.2) -->
        @if(isset($targetAchievementData))
            <x-target-achievement-table 
                :data="$targetAchievementData" 
                title="Target & Pencapaian Customer Service" 
                subtitle="Monitoring target harian, mingguan, bulanan, tahunan, kekurangan, dan target harian berjalan"
            />
        @endif

        <!-- Statistic Cards for CS -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            {{-- Card Verifikasi Pembayaran (Baru) --}}
            <a href="{{ route('cs.verifikasi.index') }}" class="block">
                <x-stat-card 
                    title="Verifikasi Bayar" 
                    :value="$stats['pending_payment_count'] ?? 0" 
                    subtitle="Menunggu CS" 
                    color="amber"
                />
            </a>
            <x-stat-card 
                title="Total Prospek" 
                :value="$stats['total_prospek']" 
                subtitle="Inbound data" 
                color="blue"
            />
            <x-stat-card 
                title="Takeover CS" 
                :value="$stats['takeover_cs']" 
                subtitle="Dikelola CS" 
                color="indigo"
            />
            <x-stat-card 
                title="Follow Up Hari Ini" 
                :value="$stats['follow_up_today']" 
                subtitle="Perlu respon" 
                color="teal"
            />
            <x-stat-card 
                title="Closing (Lunas)" 
                :value="$stats['closing'] ?? $stats['Closing (Lunas)'] ?? 0" 
                subtitle="Formulir lunas" 
                color="emerald"
            />
            <x-stat-card 
                title="Lost" 
                :value="$stats['lost']" 
                subtitle="Historis arsip" 
                color="slate"
            />
        </div>

        {{-- ═════════════════════════════════════════════════════════════════ --}}
        {{-- WIDGET: ANTREAN VERIFIKASI PEMBAYARAN TERMIN 1                  --}}
        {{-- ═════════════════════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs">
                        💳
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Antrean Verifikasi Pembayaran (Termin 1)</h3>
                        <p class="text-[11px] text-slate-500">Pembayaran dari Sales yang menunggu pengesahan CS agar sah Closing</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ ($pendingPaymentCount ?? 0) > 0 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-600' }}">
                        {{ $pendingPaymentCount ?? 0 }} Menunggu
                    </span>
                    <a href="{{ route('cs.verifikasi.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-bold ml-2">
                        Buka Semua & Riwayat →
                    </a>
                </div>
            </div>

            @if(isset($pendingPayments) && $pendingPayments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50/80 border-b border-slate-100 text-slate-500 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="px-4 py-3 text-left">Calon Mahasiswa</th>
                                <th class="px-4 py-3 text-left">Sales Lapangan</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3 text-left">Metode & Info Bank</th>
                                <th class="px-4 py-3 text-left">Tanggal</th>
                                <th class="px-4 py-3 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($pendingPayments as $trx)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-4 py-3.5">
                                        <a href="{{ route('prospek.show', $trx->prospek_id) }}" class="font-bold text-slate-900 hover:text-blue-600 block text-xs">
                                            {{ $trx->prospek?->name ?? 'Prospek #' . $trx->prospek_id }}
                                        </a>
                                        <span class="text-slate-400 text-[10px]">{{ $trx->prospek?->type ?? 'Individu' }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700 font-medium">
                                        {{ $trx->user?->name ?? $trx->prospek?->sales?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-black text-slate-900 text-xs">
                                        Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-semibold text-[10px]">
                                            {{ \App\Models\Transaksi::METODE_PEMBAYARAN[$trx->metode_pembayaran] ?? ($trx->metode_pembayaran ?: 'Transfer Bank') }}
                                        </span>
                                        @if($trx->notes)
                                            <div class="text-[10px] text-slate-500 truncate max-w-xs mt-0.5">{{ $trx->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-600">
                                        {{ $trx->tanggal?->format('d/m/Y') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <form action="{{ route('cs.verifikasi.verify', $trx->id) }}" method="POST"
                                                  onsubmit="return confirm('Konfirmasi verifikasi pembayaran untuk calon mahasiswa ini? Prospek akan dinyatakan LUNAS.')">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold shadow-xs transition cursor-pointer">
                                                    ✓ Verifikasi
                                                </button>
                                            </form>
                                            <a href="{{ route('cs.verifikasi.index') }}" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition">
                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-10 text-center text-slate-400 text-xs">
                    <p class="font-semibold text-slate-600">Tidak ada antrean pembayaran Termin 1 yang pending</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Semua transaksi yang diinput Sales sudah berhasil diverifikasi.</p>
                </div>
            @endif
        </div>

        {{-- ═════════════════════════════════════════════════════════════════ --}}
        {{-- WIDGET: PROSPEK SERAH TERIMA BARU & PEMANTAUAN SLA 2 JAM          --}}
        {{-- ═════════════════════════════════════════════════════════════════ --}}
        <div id="section-handover" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">
                        ⏱️
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Prospek Masuk Tahap Formulir (SLA Handover)</h3>
                        <p class="text-[11px] text-slate-500">SOP CRM: CS wajib melakukan follow-up pertama maksimal 2 jam sejak serah terima dari Sales</p>
                    </div>
                </div>
                <span class="text-xs text-slate-500">
                    Batas Toleransi: <strong class="text-slate-800">2 Jam</strong>
                </span>
            </div>

            @if(isset($handoverProspects) && $handoverProspects->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50/80 border-b border-slate-100 text-slate-500 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="px-4 py-3 text-left">Calon Mahasiswa</th>
                                <th class="px-4 py-3 text-left">Sales Lapangan</th>
                                <th class="px-4 py-3 text-left">Waktu Serah Terima</th>
                                <th class="px-4 py-3 text-center">Status SLA 2 Jam</th>
                                <th class="px-4 py-3 text-center">Aksi CS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($handoverProspects as $hp)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-4 py-3.5">
                                        <a href="{{ route('prospek.show', $hp['id']) }}" class="font-bold text-slate-900 hover:text-blue-600 block text-xs">
                                            {{ $hp['name'] }}
                                        </a>
                                        <span class="text-slate-400 text-[10px]">{{ $hp['type'] }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700 font-medium">
                                        {{ $hp['sales_name'] }}
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-600">
                                        {{ $hp['handover_time'] }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @if($hp['has_follow_up'])
                                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                                ✓ Sudah Direspons CS
                                            </span>
                                        @elseif($hp['is_overdue'])
                                            <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[10px] border border-rose-200">
                                                🚨 Overdue (+{{ $hp['elapsed_minutes'] - 120 }}m)
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px] border border-amber-200">
                                                ⏳ Sisa {{ $hp['remaining_minutes'] }} menit
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            @if($hp['whatsapp'])
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $hp['whatsapp']) }}" target="_blank"
                                                   class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-[10px] font-bold inline-flex items-center gap-1">
                                                    WhatsApp
                                                </a>
                                            @endif
                                            <a href="{{ route('prospek.show', $hp['id']) }}"
                                               class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-semibold">
                                                Buka
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-slate-400 text-xs">
                    <p class="font-semibold text-slate-600">Tidak ada antrean serah terima formulir baru</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Semua prospek formulir yang di-handover telah direspons tepat waktu.</p>
                </div>
            @endif
        </div>

        <!-- Section: FOLLOW UP HARI INI -->
        <div class="crm-card bg-white p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Follow Up Hari Ini</h3>
                    <p class="text-xs text-slate-500">Prospek yang memiliki jadwal interaksi dan respon aktif hari ini</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    {{ count($followUpsToday) }} Menunggu Tindak Lanjut
                </span>
            </div>

            <!-- Follow-up Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($followUpsToday as $prospect)
                    <div class="p-5 rounded-2xl border border-slate-200/80 bg-slate-50/40 hover:bg-white hover:border-blue-300 hover:shadow-xs transition space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <a href="{{ route('prospek.show', $prospect['id']) }}" class="text-sm font-bold text-slate-900 hover:text-blue-600 block">
                                    {{ $prospect['name'] }}
                                </a>
                                <span class="text-xs text-slate-500 font-medium">{{ $prospect['type'] }} &bull; PIC: {{ $prospect['pic'] }}</span>
                            </div>
                            <x-status-badge :status="$prospect['status']" />
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/60 text-slate-600">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Last Contact</span>
                                <span class="font-medium text-slate-800">{{ $prospect['last_contact'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Next Follow Up</span>
                                <span class="font-semibold text-blue-600">{{ $prospect['next_follow_up'] }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <x-takeover-badge :type="str_contains($prospect['active_takeover'], 'CS') ? 'cs' : 'sales'" :name="explode('—', $prospect['active_takeover'])[1] ?? ''" />
                            
                            <div class="flex items-center gap-2">
                                <a 
                                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $prospect['whatsapp']) }}" 
                                    target="_blank"
                                    class="p-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-200"
                                    title="WhatsApp Langsung"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                    </svg>
                                </a>
                                <a 
                                    href="{{ route('prospek.show', $prospect['id']) }}"
                                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition"
                                >
                                    Follow Up
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-slate-400 text-xs">
                        Tidak ada follow up yang dijadwalkan untuk hari ini.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</x-app-layout>
