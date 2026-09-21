@php
    $pageTitle = 'Pipeline Board Tim (SPV)';
    $pageSubtitle = 'Visualisasi Tahapan Prospek Tim Sales';
@endphp

<x-app-layout :title="'Pipeline Tim - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        selectedSales: 'all',
        prospectsList: {{ json_encode($prospects) }},
        stages: {{ json_encode(collect($pipelineStages)->map(function($stage, $index) {
            $colors = [
                ['color' => 'badge-cold-lead', 'border' => 'border-slate-300'],
                ['color' => 'badge-interested', 'border' => 'border-blue-400'],
                ['color' => 'badge-follow-up', 'border' => 'border-amber-400'],
                ['color' => 'badge-beli-formulir', 'border' => 'border-purple-400'],
                ['color' => 'badge-pembayaran-termin-1', 'border' => 'border-indigo-400'],
                ['color' => 'badge-closing', 'border' => 'border-emerald-400']
            ];
            return [
                'name' => $stage,
                'color' => $colors[$index % count($colors)]['color'],
                'border' => $colors[$index % count($colors)]['border']
            ];
        })->values()->toArray()) }},
        
        get filteredProspects() {
            if (this.selectedSales === 'all') {
                return this.prospectsList;
            }
            return this.prospectsList.filter(p => String(p.sales_id) === String(this.selectedSales));
        },

        getProspectsByStage(stageName) {
            return this.filteredProspects.filter(p => p.status.toLowerCase() === stageName.toLowerCase());
        },

        get lostProspects() {
            return this.filteredProspects.filter(p => p.status.toLowerCase() === 'lost' || p.status.toLowerCase() === 'ditolak/batal' || p.status.toLowerCase() === 'ditolak / batal');
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Board Pipeline Tim</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau pergerakan prospek seluruh anggota tim Sales dari kontak awal hingga closing.</p>
            </div>
            
            <!-- Filter by Sales Personil -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500 whitespace-nowrap">Filter Sales:</span>
                    <select x-model="selectedSales" class="text-xs px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20">
                        <option value="all">Semua Personil Sales ({{ count($prospects) }} Prospek)</option>
                        @foreach($teamSales as $sales)
                            <option value="{{ $sales->id }}">{{ $sales->name }}</option>
                        @endforeach
                    </select>
                </div>
                <a 
                    href="{{ route('spv.prospek.create') }}"
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Prospek Baru</span>
                </a>
            </div>
        </div>

        <!-- Visual Kanban Board -->
        <div class="overflow-x-auto pb-6">
            <div class="flex gap-4 min-w-[1300px]">
                
                <!-- Main Stages Columns -->
                <template x-for="stage in stages" :key="stage.name">
                    <div class="w-64 shrink-0 flex flex-col bg-slate-100/70 p-3 rounded-2xl border border-slate-200/70 min-h-[500px]">
                        
                        <!-- Column Header -->
                        <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-200">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <h3 class="text-xs font-bold text-slate-800" x-text="stage.name"></h3>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-white text-slate-700 shadow-xs" x-text="getProspectsByStage(stage.name).length"></span>
                        </div>

                        <!-- Cards in Column -->
                        <div class="flex-1 space-y-3 overflow-y-auto max-h-[600px] pr-1">
                            <template x-for="prospect in getProspectsByStage(stage.name)" :key="prospect.id">
                                <div class="crm-card bg-white p-3.5 space-y-2.5 hover:shadow-md hover:border-blue-300 transition group cursor-pointer" @click="window.location.href = '/spv/prospek/' + prospect.id">
                                    <div class="flex items-start justify-between">
                                        <h4 class="font-bold text-xs text-slate-900 group-hover:text-blue-600 leading-snug line-clamp-1" x-text="prospect.name"></h4>
                                    </div>

                                    <div class="text-[11px] text-slate-500 font-medium">
                                        <span x-text="prospect.type"></span> &bull; <span x-text="prospect.pic"></span>
                                    </div>

                                    <div class="text-[10px] px-2 py-1 rounded bg-indigo-50 text-indigo-700 font-semibold" x-text="'Sales: ' + (prospect.takeover_sales || 'Belum Ada')"></div>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-medium">
                                        <span x-text="'Follow Up: ' + prospect.last_contact"></span>
                                    </div>
                                </div>
                            </template>

                            <div x-show="getProspectsByStage(stage.name).length === 0" class="py-8 text-center text-[11px] text-slate-400 italic">
                                Belum ada prospek
                            </div>
                        </div>

                    </div>
                </template>

                <!-- Lost Column -->
                <div class="w-64 shrink-0 flex flex-col bg-rose-50/50 p-3 rounded-2xl border border-rose-200/60 min-h-[500px]">
                    <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-rose-200/60">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <h3 class="text-xs font-bold text-rose-900">Lost / Arsip</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-white text-rose-700 shadow-xs" x-text="lostProspects.length"></span>
                    </div>

                    <div class="flex-1 space-y-3 overflow-y-auto max-h-[600px] pr-1">
                        <template x-for="prospect in lostProspects" :key="prospect.id">
                            <div class="crm-card bg-white p-3.5 space-y-2 border-rose-100 opacity-75 hover:opacity-100 cursor-pointer" @click="window.location.href = '/spv/prospek/' + prospect.id">
                                <h4 class="font-bold text-xs text-slate-800 line-clamp-1" x-text="prospect.name"></h4>
                                <div class="text-[10px] text-rose-600 font-medium" x-text="'Sales: ' + (prospect.takeover_sales || 'Belum Ada')"></div>
                            </div>
                        </template>

                        <div x-show="lostProspects.length === 0" class="py-8 text-center text-[11px] text-slate-400 italic">
                            Tidak ada prospek lost
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

</x-app-layout>
