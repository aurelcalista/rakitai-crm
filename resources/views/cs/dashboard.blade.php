@php
    $pageTitle = 'Dashboard Customer Service (CS)';
    $pageSubtitle = 'Follow-up Inbound, Respon Cepat & Takeover';
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
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Halo, {{ auth()->user()->name }} </h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">Customer Service Lead</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Fokus hari ini: Follow-up cepat prospek inbound dan koordinasi takeover dengan tim Sales.</p>
            </div>
            <div class="flex items-center gap-2">
                <a 
                    href="{{ route('follow-up.index') }}"
                    class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span>Buka Semua Follow Up</span>
                </a>
            </div>
        </div>

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
                color="amber"
            />
            <x-stat-card 
                title="Follow Up Tertunda" 
                :value="$stats['follow_up_pending']" 
                subtitle="Perlu eskalasi" 
                color="rose"
            />
            <x-stat-card 
                title="Closing" 
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
                @foreach($followUpsToday as $prospect)
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
                                <button 
                                    @click='selectedProspect = @json($prospect); modalFollowUp = true'
                                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition cursor-pointer"
                                >
                                    Follow Up
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</x-app-layout>
