@php
    $pageTitle = 'Pipeline Board Tim (SPV)';
    $pageSubtitle = 'Visualisasi 8 Tahapan Pipeline Prospek Tim Sales';
@endphp

<x-app-layout :title="'Pipeline Tim (8 Status) - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        selectedSales: 'all',
        prospectsList: {{ json_encode($prospects) }},
        stageConfigs: {
            'BARU':     { color: 'bg-slate-500', text: 'text-slate-800', border: 'border-slate-300', bg: 'bg-slate-100/70', dot: 'bg-slate-400' },
            'KONTAK':   { color: 'bg-sky-500',   text: 'text-sky-800',   border: 'border-sky-300',   bg: 'bg-sky-50/50',    dot: 'bg-sky-500' },
            'HANGAT':   { color: 'bg-amber-500', text: 'text-amber-800', border: 'border-amber-300', bg: 'bg-amber-50/50',  dot: 'bg-amber-500' },
            'PANAS':    { color: 'bg-orange-500',text: 'text-orange-800',border: 'border-orange-300',bg: 'bg-orange-50/50', dot: 'bg-orange-500' },
            'FORMULIR': { color: 'bg-purple-500',text: 'text-purple-800',border: 'border-purple-300',bg: 'bg-purple-50/50', dot: 'bg-purple-500' },
            'BERKAS':   { color: 'bg-indigo-500',text: 'text-indigo-800',border: 'border-indigo-300',bg: 'bg-indigo-50/50', dot: 'bg-indigo-500' },
            'LUNAS':    { color: 'bg-emerald-500',text: 'text-emerald-800',border: 'border-emerald-300',bg: 'bg-emerald-50/50',dot: 'bg-emerald-500' },
            'DINGIN':   { color: 'bg-rose-500',  text: 'text-rose-800',  border: 'border-rose-300',  bg: 'bg-rose-50/50',   dot: 'bg-rose-500' }
        },
        pipelineList: {{ json_encode($pipelineStages) }},
        
        get filteredProspects() {
            if (this.selectedSales === 'all') {
                return this.prospectsList;
            }
            return this.prospectsList.filter(p => String(p.sales_id) === String(this.selectedSales));
        },

        getProspectsByStage(stageName) {
            return this.filteredProspects.filter(p => (p.status || '').toUpperCase() === stageName.toUpperCase());
        }
    }">

        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Board Pipeline Tim</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">8 Status PMB</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Monitoring dan evaluasi alur pergerakan prospek tim Sales.</p>
            </div>
            
            <!-- Filter by Sales Personil & Action -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500 whitespace-nowrap">Filter Sales:</span>
                    <select x-model="selectedSales" class="text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer">
                        <option value="all">Semua Personil Sales ({{ count($prospects) }} Prospek)</option>
                        @foreach($teamSales as $sales)
                            <option value="{{ $sales->id }}">{{ $sales->name }}</option>
                        @endforeach
                    </select>
                </div>
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

        <!-- Visual Kanban Board (8 Columns) -->
        <div class="overflow-x-auto pb-6">
            <div class="flex gap-4 min-w-[1800px]">
                
                <template x-for="stageName in pipelineList" :key="stageName">
                    <div 
                        class="w-60 shrink-0 flex flex-col p-3 rounded-2xl border min-h-[520px] transition-all"
                        :class="stageConfigs[stageName] ? (stageConfigs[stageName].bg + ' ' + stageConfigs[stageName].border) : 'bg-slate-100 border-slate-200'"
                    >
                        
                        <!-- Column Header -->
                        <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-200/80">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :class="stageConfigs[stageName] ? stageConfigs[stageName].dot : 'bg-blue-600'"></span>
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
