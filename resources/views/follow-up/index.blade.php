@php
    $pageTitle = 'Manajemen Follow Up';
    $pageSubtitle = 'Interaksi Terjadwal, Respon Inbound & Log Komunikasi';
@endphp

<x-app-layout :title="'Follow Up - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Tidak ada jadwal follow-up" 
            description="Semua follow-up pada tab ini sudah selesai diproses."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6" x-data="{
        activeTab: 'today',
        prospectsList: {{ json_encode($prospects) }},
        
        get tabProspects() {
            return this.prospectsList[this.activeTab] || [];
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Aktivitas Follow Up</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau dan tindak lanjuti prospek calon mahasiswa sesuai jadwal audiensi.</p>
            </div>
            @if(in_array(auth()->user()->role ?? '', ['Sales', 'CS']))
            <div>
                <button 
                    type="button" 
                    @click="selectedProspect = (prospectsList.today[0] || prospectsList.upcoming[0] || prospectsList.overdue[0] || prospectsList.done[0]); if(selectedProspect) { modalFollowUp = true }"
                    class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Catat Follow Up Baru</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Follow-up Status Tabs -->
        <div class="flex border-b border-slate-200 bg-white px-4 rounded-2xl border overflow-x-auto whitespace-nowrap">
            <button 
                type="button" 
                @click="activeTab = 'today'"
                :class="activeTab === 'today' ? 'border-blue-600 text-blue-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-4 text-xs sm:text-sm font-semibold transition flex items-center gap-2 cursor-pointer"
            >
                <span>Hari Ini</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-bold" x-text="prospectsList.today.length"></span>
            </button>
            <button 
                type="button" 
                @click="activeTab = 'upcoming'"
                :class="activeTab === 'upcoming' ? 'border-blue-600 text-blue-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-4 text-xs sm:text-sm font-semibold transition flex items-center gap-2 cursor-pointer"
            >
                <span>Upcoming</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-800 font-bold" x-text="prospectsList.upcoming.length"></span>
            </button>
            <button 
                type="button" 
                @click="activeTab = 'overdue'"
                :class="activeTab === 'overdue' ? 'border-blue-600 text-blue-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-4 text-xs sm:text-sm font-semibold transition flex items-center gap-2 cursor-pointer"
            >
                <span>Overdue</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-rose-100 text-rose-800 font-bold" x-text="prospectsList.overdue.length"></span>
            </button>
            <button 
                type="button" 
                @click="activeTab = 'done'"
                :class="activeTab === 'done' ? 'border-blue-600 text-blue-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-4 text-xs sm:text-sm font-semibold transition flex items-center gap-2 cursor-pointer"
            >
                <span>Selesai</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-bold" x-text="prospectsList.done.length"></span>
            </button>
        </div>

        <!-- Follow-up Cards List -->
        <div x-show="tabProspects.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <template x-for="prospect in tabProspects" :key="prospect.id">
                <div class="crm-card bg-white p-5 space-y-4 hover:border-blue-300 transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <a :href="'{{ auth()->user()->role === 'Sales' ? '/sales' : '' }}/prospek/' + prospect.id" class="font-bold text-sm text-slate-900 hover:text-blue-600 block" x-text="prospect.name"></a>
                                <span class="text-xs text-slate-500 font-medium" x-text="prospect.type + ' • PIC: ' + prospect.pic"></span>
                            </div>
                            <span 
                                :class="'badge-' + prospect.status.toLowerCase().replace(/ /g, '-')"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border"
                                x-text="prospect.status"
                            ></span>
                        </div>

                        <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-100 text-xs space-y-2 mt-3">
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="text-slate-400">Last Contact:</span>
                                <span class="font-semibold text-slate-800" x-text="prospect.last_contact"></span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="text-slate-400">Next Follow Up:</span>
                                <span class="font-bold text-blue-600" x-text="prospect.next_follow_up"></span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600 pt-1 border-t border-slate-200/60">
                                <span class="text-slate-400">Takeover Aktif:</span>
                                <span class="font-semibold text-slate-800 text-[11px]" x-text="prospect.active_takeover"></span>
                            </div>
                        </div>

                        <p class="text-xs text-slate-600 mt-3 line-clamp-2 italic" x-text="'Catatan: ' + prospect.notes"></p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center gap-2">
                        <a 
                            :href="'https://wa.me/' + prospect.whatsapp.replace(/[^0-9]/g, '')" 
                            target="_blank"
                            class="p-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-200"
                            title="Chat WhatsApp"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        </a>
                        <button 
                            type="button" 
                            @click="selectedProspect = prospect; modalFollowUp = true"
                            class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition text-center cursor-pointer"
                        >
                            Catat Follow Up
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="tabProspects.length === 0" x-cloak class="py-12 flex flex-col items-center justify-center bg-white rounded-2xl border border-slate-200 border-dashed">
            <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <h3 class="text-sm font-bold text-slate-900">Tidak ada jadwal follow-up</h3>
            <p class="text-xs text-slate-500 mt-1">Semua follow-up pada tab ini sudah diproses atau masih kosong.</p>
        </div>

    </div>

</x-app-layout>
