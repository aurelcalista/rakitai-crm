@php
    $pageTitle = 'Pipeline Board Tim (SPV)';
    $pageSubtitle = 'Visualisasi 8 Tahapan Pipeline Prospek Tim Sales';
@endphp

<x-app-layout :title="'Pipeline Tim (8 Status) - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        searchQuery: '',
        filterType: 'all',
        selectedSales: 'all',
        prospectsList: {{ json_encode($prospects) }},
        stageConfigs: {
            'BARU':        { color: 'bg-slate-500', text: 'text-slate-800', border: 'border-slate-300', bg: 'bg-slate-100/70', dot: 'bg-slate-400' },
            'KONTAK':      { color: 'bg-sky-500',   text: 'text-sky-800',   border: 'border-sky-300',   bg: 'bg-sky-50/50',    dot: 'bg-sky-500' },
            'PROSPEK':     { color: 'bg-amber-500', text: 'text-amber-800', border: 'border-amber-300', bg: 'bg-amber-50/50',  dot: 'bg-amber-500' },
            'HOT PROSPEK': { color: 'bg-orange-500',text: 'text-orange-800',border: 'border-orange-300',bg: 'bg-orange-50/50', dot: 'bg-orange-500' },
            'FORMULIR':    { color: 'bg-purple-500',text: 'text-purple-800',border: 'border-purple-300',bg: 'bg-purple-50/50', dot: 'bg-purple-500' },
            'BERKAS':      { color: 'bg-indigo-500',text: 'text-indigo-800',border: 'border-indigo-300',bg: 'bg-indigo-50/50', dot: 'bg-indigo-500' },
            'LUNAS':       { color: 'bg-emerald-500',text: 'text-emerald-800',border: 'border-emerald-300',bg: 'bg-emerald-50/50',dot: 'bg-emerald-500' },
            'NO RESPON':   { color: 'bg-rose-500',  text: 'text-rose-800',  border: 'border-rose-300',  bg: 'bg-rose-50/50',   dot: 'bg-rose-500' },
            'HANGAT':      { color: 'bg-amber-500', text: 'text-amber-800', border: 'border-amber-300', bg: 'bg-amber-50/50',  dot: 'bg-amber-500' },
            'PANAS':       { color: 'bg-orange-500',text: 'text-orange-800',border: 'border-orange-300',bg: 'bg-orange-50/50', dot: 'bg-orange-500' },
            'DINGIN':      { color: 'bg-rose-500',  text: 'text-rose-800',  border: 'border-rose-300',  bg: 'bg-rose-50/50',   dot: 'bg-rose-500' }
        },
        pipelineList: {{ json_encode($pipelineStages) }},
        
        getStageConfig(stageName) {
            const normalized = (stageName || '').toUpperCase();
            if (this.stageConfigs[normalized]) {
                return this.stageConfigs[normalized];
            }
            const fallbackPalettes = [
                { color: 'bg-teal-500',   text: 'text-teal-800',   border: 'border-teal-300',   bg: 'bg-teal-50/50',   dot: 'bg-teal-500' },
                { color: 'bg-cyan-500',   text: 'text-cyan-800',   border: 'border-cyan-300',   bg: 'bg-cyan-50/50',   dot: 'bg-cyan-500' },
                { color: 'bg-violet-500', text: 'text-violet-800', border: 'border-violet-300', bg: 'bg-violet-50/50', dot: 'bg-violet-500' },
                { color: 'bg-pink-500',   text: 'text-pink-800',   border: 'border-pink-300',   bg: 'bg-pink-50/50',   dot: 'bg-pink-500' },
                { color: 'bg-yellow-500', text: 'text-yellow-800', border: 'border-yellow-300', bg: 'bg-yellow-50/50', dot: 'bg-yellow-500' },
                { color: 'bg-blue-500',   text: 'text-blue-800',   border: 'border-blue-300',   bg: 'bg-blue-50/50',   dot: 'bg-blue-500' },
            ];
            let hash = 0;
            for (let i = 0; i < normalized.length; i++) {
                hash = (hash + normalized.charCodeAt(i)) % fallbackPalettes.length;
            }
            return fallbackPalettes[hash];
        },
        
        get filteredProspects() {
            let list = this.prospectsList;
            if (this.selectedSales !== 'all') {
                list = list.filter(p => String(p.sales_id) === String(this.selectedSales));
            }
            if (this.filterType !== 'all') {
                list = list.filter(p => p.type === this.filterType);
            }
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(p => 
                    (p.name && p.name.toLowerCase().includes(q)) ||
                    (p.pic && p.pic.toLowerCase().includes(q)) ||
                    (p.whatsapp && String(p.whatsapp).includes(q)) ||
                    (p.takeover_sales && p.takeover_sales.toLowerCase().includes(q))
                );
            }
            return list;
        },

        getProspectsByStage(stageName) {
            return this.filteredProspects.filter(p => (p.status || '').toUpperCase() === stageName.toUpperCase());
        }
    }">

        <!-- Header -->
        <div class="flex flex-col gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Board Pipeline Tim</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="pipelineList.length + ' Status PMB'"></span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Monitoring dan evaluasi alur pergerakan prospek tim Sales.</p>
                </div>
                
                <div class="flex items-center gap-3 shrink-0">
                    <a 
                        href="{{ route('spv.prospek.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold shadow-xs transition whitespace-nowrap shrink-0 cursor-pointer"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Prospek</span>
                    </a>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-100">
                <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            x-model="searchQuery" 
                            placeholder="Cari prospek, sekolah, PIC, WA..." 
                            class="w-full pl-9 pr-8 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                        />
                        <button 
                            x-show="searchQuery.length > 0" 
                            @click="searchQuery = ''" 
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Filter Sales Personil -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-500 whitespace-nowrap">Sales:</span>
                        <select x-model="selectedSales" class="text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer">
                            <option value="all">Semua Personil</option>
                            @foreach($teamSales as $sales)
                                <option value="{{ $sales->id }}">{{ $sales->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Tipe -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-500 whitespace-nowrap">Tipe:</span>
                        <select x-model="filterType" class="text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer">
                            <option value="all">Semua Tipe</option>
                            <option value="Sekolah">Sekolah</option>
                            <option value="Corporate">Corporate</option>
                            <option value="Individu">Individu</option>
                        </select>
                    </div>
                </div>

                <!-- Match counter & Reset -->
                <div class="flex items-center gap-2 text-xs text-slate-500 shrink-0">
                    <span class="font-medium">Menampilkan <strong class="text-slate-800" x-text="filteredProspects.length"></strong> dari <span x-text="prospectsList.length"></span> prospek</span>
                    <button 
                        type="button" 
                        x-show="searchQuery !== '' || selectedSales !== 'all' || filterType !== 'all'" 
                        @click="searchQuery = ''; selectedSales = 'all'; filterType = 'all';" 
                        class="text-blue-600 hover:text-blue-700 underline text-xs font-medium ml-1 cursor-pointer"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Visual Kanban Board (Dynamic Columns) -->
        <div class="overflow-x-auto pb-6">
            <div class="flex gap-4 min-w-[1800px]">
                
                <template x-for="stageName in pipelineList" :key="stageName">
                    <div 
                        class="w-60 shrink-0 flex flex-col p-3 rounded-2xl border min-h-[520px] transition-all"
                        :class="getStageConfig(stageName).bg + ' ' + getStageConfig(stageName).border"
                    >
                        
                        <!-- Column Header -->
                        <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-200/80">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :class="getStageConfig(stageName).dot"></span>
                                <h3 class="text-xs font-extrabold text-slate-800 tracking-wide" x-text="stageName"></h3>
                            </div>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-white text-slate-700 shadow-xs border border-slate-200/60" 
                                x-text="getProspectsByStage(stageName).length"
                            ></span>
                        </div>

                        <!-- Cards in Column -->
                        <div class="flex-1 space-y-3 overflow-y-auto max-h-[620px] pr-1">
                            <template x-for="prospect in getProspectsByStage(stageName)" :key="prospect.id">
                                <div 
                                    class="crm-card bg-white p-3.5 space-y-2.5 hover:shadow-md hover:border-blue-300 transition group cursor-pointer border border-slate-200/80" 
                                    @click="window.location.href = '/spv/prospek/' + prospect.id"
                                >
                                    <div class="flex items-start justify-between gap-1">
                                        <h4 class="font-bold text-xs text-slate-900 group-hover:text-blue-600 leading-snug line-clamp-1" x-text="prospect.name"></h4>
                                    </div>

                                    <div class="text-[11px] text-slate-500 font-medium truncate">
                                        <span x-text="prospect.type"></span> &bull; <span x-text="prospect.pic"></span>
                                    </div>

                                    <div class="flex items-center justify-between text-[10px] pt-1">
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold truncate max-w-[140px]" x-text="'Sales: ' + (prospect.takeover_sales || 'Belum Ada')"></span>
                                        <template x-if="stageName === 'LUNAS'">
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">MABA LUNAS</span>
                                        </template>
                                    </div>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-medium">
                                        <span x-text="'Follow Up: ' + (prospect.last_contact || '-')"></span>
                                    </div>
                                </div>
                            </template>

                            <div x-show="getProspectsByStage(stageName).length === 0" class="py-10 text-center text-[11px] text-slate-400 italic">
                                Kosong
                            </div>
                        </div>

                    </div>
                </template>

            </div>
        </div>

    </div>

</x-app-layout>
