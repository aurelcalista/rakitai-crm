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
        activeTab: 'uncontacted',
        searchQuery: '',
        selectedScope: 'all',
        authUserId: {{ auth()->id() ?? 0 }},
        userRole: '{{ auth()->user()->role ?? '' }}',
        prospectsList: {{ json_encode($prospects) }},

        init() {
            if (this.prospectsList.today && this.prospectsList.today.length > 0) {
                this.activeTab = 'today';
            } else if (this.prospectsList.uncontacted && this.prospectsList.uncontacted.length > 0) {
                this.activeTab = 'uncontacted';
            } else if (this.prospectsList.overdue && this.prospectsList.overdue.length > 0) {
                this.activeTab = 'overdue';
            } else if (this.prospectsList.unscheduled && this.prospectsList.unscheduled.length > 0) {
                this.activeTab = 'unscheduled';
            } else if (this.prospectsList.others && this.prospectsList.others.length > 0) {
                this.activeTab = 'others';
            } else {
                this.activeTab = 'today';
            }
        },
        
        isOwnProspect(p) {
            return Boolean(p.is_own === true || (this.authUserId && (p.sales_id == this.authUserId || p.owner_id == this.authUserId)));
        },
        isOtherProspect(p) {
            const hasSalesOwner = Boolean(p.sales_id || (p.owner_id && p.owner_id !== '-'));
            return Boolean(p.is_other === true || (!this.isOwnProspect(p) && hasSalesOwner));
        },
        
        get tabProspects() {
            const list = this.prospectsList[this.activeTab] || [];
            const q = this.searchQuery.toLowerCase().trim();
            return list.filter(p => {
                const matchSearch = !q ||
                    (p.name && p.name.toLowerCase().includes(q)) ||
                    (p.pic && p.pic.toLowerCase().includes(q)) ||
                    (p.whatsapp && p.whatsapp.includes(q)) ||
                    (p.sales_name && p.sales_name.toLowerCase().includes(q)) ||
                    (p.notes && p.notes.toLowerCase().includes(q));

                let matchScope = true;
                if (this.selectedScope === 'own') {
                    matchScope = this.isOwnProspect(p);
                } else if (this.selectedScope === 'others') {
                    matchScope = this.isOtherProspect(p);
                }

                return matchSearch && matchScope;
            });
        },
        getProspectUrl(prospect) {
            const role = '{{ strtolower(auth()->user()->role ?? '') }}';
            if (role === 'sales') {
                return '/sales/prospek/' + prospect.id;
            }
            if (role === 'spv') {
                return '/spv/prospek/' + prospect.id;
            }
            return '/prospek/' + prospect.id;
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Antrean Follow Up</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau dan tindak lanjuti prospek aktif secara mandiri maupun kolaboratif lintas Sales.</p>
            </div>
            @if(in_array(strtolower(auth()->user()->role ?? ''), ['sales', 'cs']))
            <div>
                <button 
                    type="button" 
                    @click="selectedProspect = (prospectsList.today[0] || prospectsList.uncontacted[0] || prospectsList.overdue[0] || prospectsList.others[0] || prospectsList.unscheduled[0] || prospectsList.upcoming[0] || prospectsList.done[0]); if(selectedProspect) { modalFollowUp = true }"
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

        <!-- Follow-up Status Tabs (Antrean Follow-Up Otomatis & Sales Lain) -->
        <div class="flex border-b border-slate-200 bg-white px-3 rounded-2xl border overflow-x-auto whitespace-nowrap scrollbar-thin">
            <!-- 1. Belum Dihubungi -->
            <button 
                type="button" 
                @click="activeTab = 'uncontacted'"
                :class="activeTab === 'uncontacted' ? 'border-sky-600 text-sky-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-3.5 text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 cursor-pointer"
            >
                <span>Belum Dihubungi</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-sky-100 text-sky-800 font-bold" x-text="(prospectsList.uncontacted || []).length"></span>
            </button>

            <!-- 2. Jadwal Hari Ini -->
            <button 
                type="button" 
                @click="activeTab = 'today'"
                :class="activeTab === 'today' ? 'border-amber-600 text-amber-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-3.5 text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 cursor-pointer"
            >
                <span>Jadwal Hari Ini</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-bold" x-text="(prospectsList.today || []).length"></span>
            </button>

            <!-- 3. Terlambat -->
            <button 
                type="button" 
                @click="activeTab = 'overdue'"
                :class="activeTab === 'overdue' ? 'border-rose-600 text-rose-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-3.5 text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 cursor-pointer"
            >
                <span>Terlambat</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-rose-100 text-rose-800 font-bold" x-text="(prospectsList.overdue || []).length"></span>
            </button>

            <!-- 4. Belum Dijadwalkan -->
            <button 
                type="button" 
                @click="activeTab = 'unscheduled'"
                :class="activeTab === 'unscheduled' ? 'border-purple-600 text-purple-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-3.5 text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 cursor-pointer"
            >
                <span>Belum Dijadwalkan</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-100 text-purple-800 font-bold" x-text="(prospectsList.unscheduled || []).length"></span>
            </button>

            <!-- 5. Follow-Up Sales Lain -->
            <button 
                type="button" 
                @click="activeTab = 'others'"
                :class="activeTab === 'others' ? 'border-amber-600 text-amber-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-3.5 text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 cursor-pointer"
            >
                <span>Follow-Up Sales Lain</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-bold" x-text="(prospectsList.others || []).length"></span>
            </button>

            <!-- 6. Mendatang -->
            <button 
                type="button" 
                @click="activeTab = 'upcoming'"
                :class="activeTab === 'upcoming' ? 'border-blue-600 text-blue-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-3.5 text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 cursor-pointer"
            >
                <span>Mendatang</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-800 font-bold" x-text="(prospectsList.upcoming || []).length"></span>
            </button>

            <!-- 7. Selesai (Lunas / Dingin) -->
            <button 
                type="button" 
                @click="activeTab = 'done'"
                :class="activeTab === 'done' ? 'border-emerald-600 text-emerald-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-3.5 text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 cursor-pointer"
            >
                <span>Selesai</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-bold" x-text="(prospectsList.done || []).length"></span>
            </button>
        </div>

        <!-- Search & Filter Controls within Active Tab -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="relative flex-1">
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari prospek, PIC, no WhatsApp, catatan, atau nama Sales..." 
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 text-xs sm:text-sm transition bg-slate-50/50 focus:bg-white"
                >
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <button 
                    x-show="searchQuery" 
                    @click="searchQuery = ''"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Scope Filter Buttons (Khusus Sales) -->
            @if(in_array(strtolower(auth()->user()->role ?? ''), ['sales']))
            <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200/60 shrink-0 text-xs">
                <button 
                    type="button" 
                    @click="selectedScope = 'all'" 
                    class="px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                    :class="selectedScope === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                >
                    Semua
                </button>
                <button 
                    type="button" 
                    @click="selectedScope = 'own'" 
                    class="px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                    :class="selectedScope === 'own' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                >
                    Prospek Saya
                </button>
                <button 
                    type="button" 
                    @click="selectedScope = 'others'" 
                    class="px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                    :class="selectedScope === 'others' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                >
                    Sales Lain
                </button>
            </div>
            @endif
        </div>

        <!-- Follow-up Cards List -->
        <div x-show="tabProspects.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <template x-for="prospect in tabProspects" :key="prospect.id">
                <div class="crm-card bg-white p-5 space-y-4 hover:border-blue-300 transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <a :href="getProspectUrl(prospect)" class="font-bold text-sm text-slate-900 hover:text-blue-600 block" x-text="(prospect.pic && prospect.pic !== '-') ? prospect.pic : prospect.name"></a>
                                <span class="text-xs text-slate-500 font-medium" x-text="(prospect.pic && prospect.pic !== '-') ? ('PIC • ' + prospect.name) : (prospect.type + ' • PIC: ' + prospect.pic)"></span>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span 
                                    :class="'badge-' + prospect.status.toLowerCase().replace(/ /g, '-')"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border"
                                    x-text="prospect.status"
                                ></span>
                                <span x-show="isOtherProspect(prospect)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="Prospek milik Sales lain, dapat dibantu follow-up">
                                    Bantuan
                                </span>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-100 text-xs space-y-2 mt-3">
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="text-slate-400">Sales Pemilik:</span>
                                <span class="font-bold text-slate-800 text-[11px]" x-text="prospect.sales_name || prospect.owner || '-'"></span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="text-slate-400">Riwayat FU:</span>
                                <span class="font-semibold text-slate-700 text-[11px]" x-text="(prospect.follow_up_count > 0 || prospect.has_follow_up_history) ? ((prospect.follow_up_count || 1) + ' kali') : 'Belum pernah'"></span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="text-slate-400">Last Contact:</span>
                                <span class="font-semibold text-slate-800" x-text="prospect.last_contact || '-'"></span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="text-slate-400">Next Follow Up:</span>
                                <span :class="prospect.next_follow_up && prospect.next_follow_up !== '-' ? 'text-blue-600 font-bold' : 'text-slate-400 italic'" x-text="prospect.next_follow_up && prospect.next_follow_up !== '-' ? prospect.next_follow_up : 'Belum dijadwalkan'"></span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600 pt-1 border-t border-slate-200/60" x-show="prospect.active_takeover">
                                <span class="text-slate-400">Takeover Aktif:</span>
                                <span class="font-semibold text-slate-800 text-[11px]" x-text="prospect.active_takeover"></span>
                            </div>
                        </div>

                        <p class="text-xs text-slate-600 mt-3 line-clamp-2 italic" x-text="'Catatan: ' + (prospect.notes || 'Belum ada catatan.')"></p>
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
                            :class="isOtherProspect(prospect) ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                            class="flex-1 py-2 px-3 rounded-xl text-xs font-semibold shadow-xs transition text-center cursor-pointer flex items-center justify-center gap-1.5"
                        >
                            <svg x-show="isOtherProspect(prospect)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            <span x-text="isOtherProspect(prospect) ? 'Bantu Follow Up' : 'Catat Follow Up'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="tabProspects.length === 0" x-cloak class="py-12 flex flex-col items-center justify-center bg-white rounded-2xl border border-slate-200 border-dashed">
            <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <h3 class="text-sm font-bold text-slate-900">Tidak ada prospek pada antrean ini</h3>
            <p class="text-xs text-slate-500 mt-1">Semua follow-up pada kategori ini sudah diproses atau belum ada prospek yang sesuai kriteria.</p>
        </div>

    </div>

</x-app-layout>
