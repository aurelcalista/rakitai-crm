@php
    $pageTitle = 'Pipeline Prospek Visual';
    $pageSubtitle = 'Kanban Board Tahapan Inbound Sales UCIC';
@endphp

<x-app-layout :title="'Pipeline - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Pipeline prospek kosong" 
            description="Belum ada prospek aktif di dalam board pipeline."
            actionLabel="Tambah Prospek"
            actionClick="modalTambahProspek = true"
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6" x-data="{
        prospectsList: {{ json_encode($prospects) }},
        stages: {!! json_encode(collect($pipelineStages)->map(function($stage, $index) {
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
        })->values()->toArray()) !!},
        
        getProspectsByStage(stageName) {
            return this.prospectsList.filter(p => p.status.toLowerCase() === stageName.toLowerCase());
        },

        get lostProspects() {
            return this.prospectsList.filter(p => p.status.toLowerCase() === 'lost' || p.status.toLowerCase() === 'ditolak/batal' || p.status.toLowerCase() === 'ditolak / batal');
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Board Pipeline Inbound</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau perpindahan prospek dari kontak awal hingga registrasi resmi.</p>
            </div>
            <div>
                <button 
                    type="button" 
                    @click="modalTambahProspek = true"
                    class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>+ Prospek Baru</span>
                </button>
            </div>
        </div>

        <!-- Visual Kanban Board (Horizontal Scroll on Mobile/Desktop) -->
        <div class="overflow-x-auto pb-6">
            <div class="flex gap-4 min-w-[1300px]">
                
                <!-- Main Stages Columns -->
                <template x-for="stage in stages" :key="stage.name">
                    <div class="w-64 shrink-0 flex flex-col bg-slate-100/70 p-3 rounded-2xl border border-slate-200/70 min-h-[500px]">
                        
                        <!-- Column Header -->
                        <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-200">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :class="'bg-' + stage.border.replace('border-', '')"></span>
                                <h3 class="text-xs font-bold text-slate-800" x-text="stage.name"></h3>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-white text-slate-700 shadow-xs" x-text="getProspectsByStage(stage.name).length"></span>
                        </div>

                        <!-- Cards in Column -->
                        <div class="flex-1 space-y-3 overflow-y-auto max-h-[600px] pr-1">
                            <template x-for="prospect in getProspectsByStage(stage.name)" :key="prospect.id">
                                <div class="crm-card bg-white p-3.5 space-y-2.5 hover:shadow-md hover:border-blue-300 transition group cursor-pointer" @click="window.location.href = '{{ auth()->user()->role === 'Sales' ? '/sales' : '' }}/prospek/' + prospect.id">
                                    <div class="flex items-start justify-between">
                                        <h4 class="font-bold text-xs text-slate-900 group-hover:text-blue-600 leading-snug line-clamp-1" x-text="prospect.name"></h4>
                                    </div>

                                    <div class="text-[11px] text-slate-500 font-medium">
                                        <span x-text="prospect.type"></span> &bull; <span x-text="prospect.pic"></span>
                                    </div>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-50 border border-slate-200 text-slate-600 font-semibold" x-text="prospect.active_takeover"></span>
                                        <span class="text-slate-400" x-text="prospect.last_activity"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="getProspectsByStage(stage.name).length === 0">
                                <div class="py-10 text-center text-slate-400 text-xs italic">
                                    Tidak ada prospek
                                </div>
                            </template>
                        </div>

                    </div>
                </template>

                <!-- Lost Column (Historical Archive) -->
                <div class="w-64 shrink-0 flex flex-col bg-rose-50/40 p-3 rounded-2xl border border-rose-200/70 min-h-[500px]">
                    <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-rose-200">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <h3 class="text-xs font-bold text-rose-900">Lost (Arsip)</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-white text-rose-700 shadow-xs" x-text="lostProspects.length"></span>
                    </div>

                    <div class="flex-1 space-y-3 overflow-y-auto max-h-[600px] pr-1">
                        <template x-for="prospect in lostProspects" :key="prospect.id">
                            <div class="crm-card bg-white p-3.5 space-y-2.5 border-rose-100 hover:border-rose-300 transition group cursor-pointer" @click="window.location.href = '{{ auth()->user()->role === 'Sales' ? '/sales' : '' }}/prospek/' + prospect.id">
                                <h4 class="font-bold text-xs text-slate-900 group-hover:text-rose-600 leading-snug" x-text="prospect.name"></h4>
                                <p class="text-[11px] text-slate-500 line-clamp-2 italic" x-text="prospect.notes"></p>
                                <div class="pt-2 border-t border-slate-100 text-[10px] text-slate-400 flex justify-between">
                                    <span>Arsip Historis</span>
                                    <span x-text="prospect.last_contact"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>

    </div>

</x-app-layout>
