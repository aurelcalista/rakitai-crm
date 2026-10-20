<x-app-layout title="Kalender Internal">
<div x-data="calendarApp()" x-init="init()" class="h-full flex flex-col">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kalender Internal</h1>
            <p class="text-sm text-slate-500 mt-1">Jadwal event dan kegiatan operasional tim.</p>
        </div>
        <div class="flex items-center gap-3">
            <select x-model="agendaFilter" class="text-xs sm:text-sm border border-slate-300 rounded-xl bg-white text-slate-700 px-3 py-1.5 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition shadow-2xs font-medium">
                <option value="Semua">Semua</option>
                <option value="Agenda">Agenda Internal</option>
                <option value="Event">Event EO</option>
                <option value="Pribadi">Agenda Pribadi</option>
            </select>
            @if(strtolower(auth()->user()->role ?? '') !== 'sales')
            <button @click="openAddAgenda()" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                <svg class="w-4 h-4 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Tambah Agenda</span>
            </button>
            @endif
            <!-- Legend -->
            <div class="hidden sm:flex items-center gap-3 text-[10px] font-semibold text-slate-500">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>Agenda</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>Event EO</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>Dibatalkan</span>
            </div>
            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
            <button @click="prevMonth()" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition shadow-sm text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <h2 class="text-sm sm:text-lg font-bold text-slate-800 w-28 sm:w-40 text-center" x-text="monthNames[month] + ' ' + year"></h2>
            <button @click="nextMonth()" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition shadow-sm text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex-1 flex flex-col overflow-hidden relative">
        
        <!-- Loading Overlay -->
        <div x-show="loading" class="absolute inset-0 z-10 bg-white/50 backdrop-blur-sm flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Days Header -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            <template x-for="day in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']">
                <div class="py-2.5 text-center text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider" x-text="day"></div>
            </template>
        </div>

        <!-- Days Grid -->
        <div class="flex-1 grid grid-cols-7 grid-rows-6 bg-slate-200 gap-px overflow-hidden">
            <template x-for="(dateObj, index) in calendarDays" :key="index">
                <div 
                    class="bg-white p-1 sm:p-2 flex flex-col min-h-[80px] transition group hover:bg-slate-50 min-w-0"
                    :class="{'opacity-50': !dateObj.isCurrentMonth, 'bg-blue-50/20': isToday(dateObj.date)}"
                >
                    <div class="flex justify-between items-center mb-1">
                        <span 
                            class="text-xs sm:text-sm font-semibold w-6 h-6 sm:w-7 sm:h-7 flex items-center justify-center rounded-full shrink-0"
                            :class="isToday(dateObj.date) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-700 group-hover:text-blue-600'"
                            x-text="dateObj.day"
                        ></span>
                    </div>

                    <!-- Events Container -->
                    <div class="flex-1 overflow-y-auto space-y-1 hide-scrollbar">
                        <template x-for="evt in getEventsForDate(dateObj.date)" :key="evt.id">
                            <div 
                                @click="openEventDetails(evt)"
                                class="flex flex-col gap-0.5 text-[10px] sm:text-xs p-1.5 sm:p-2 rounded-lg border cursor-pointer transition hover:opacity-80"
                                :class="getEventColor(evt)"
                                :title="evt.title"
                            >
                                <div class="font-bold truncate flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="getEventDotColor(evt)"></span>
                                    <span class="truncate" x-text="evt.title"></span>
                                </div>
                                <div class="text-[9px] sm:text-[10px] opacity-80 hidden sm:block" x-text="evt.waktu_mulai + ' - ' + evt.waktu_selesai"></div>
                                <template x-if="evt.is_eo_event">
                                    <div class="flex items-center justify-between text-[8px] sm:text-[9px] gap-1 mt-0.5">
                                        <span class="font-bold uppercase tracking-wider opacity-70">Event EO</span>
                                        <template x-if="evt.takeover_by">
                                            <span class="font-semibold text-emerald-700 bg-emerald-100/90 px-1 py-0.2 rounded truncate" :title="'PJ: ' + evt.takeover_by.name" x-text="'PJ: ' + evt.takeover_by.name"></span>
                                        </template>
                                        <template x-if="!evt.takeover_by">
                                            <span class="font-medium text-amber-700 bg-amber-100/90 px-1 py-0.2 rounded truncate">Belum Ada PJ</span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Event Detail Modal (Professional Landscape Grid) -->
    <div x-show="modalEventDetail" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-6">
        <div x-show="modalEventDetail" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="modalEventDetail = false"></div>

        <div x-show="modalEventDetail" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col transform transition-all overflow-hidden border border-slate-100">
            
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border shadow-2xs"
                         :class="selectedEvent?.is_eo_event ? 'bg-violet-100 text-violet-600 border-violet-200/60' : 'bg-blue-100 text-blue-600 border-blue-200/60'">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-tight" x-text="selectedEvent?.is_eo_event ? 'Detail Event' : 'Detail Agenda'"></h3>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="selectedEvent?.is_eo_event ? 'Informasi event operasional, penanggung jawab, & penugasan personil' : 'Informasi jadwal dan kegiatan agenda internal'"></p>
                    </div>
                </div>
                <button type="button" @click="modalEventDetail = false" class="text-slate-400 hover:text-slate-600 transition p-2 rounded-xl hover:bg-slate-200/60 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <!-- Body: Landscape 2-Column Responsive Layout -->
            <div class="p-6 md:p-7 overflow-y-auto" x-show="selectedEvent">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-7">
                    
                    <!-- Left Column (Span 7): Title, Badges, Key Info Grid, Description -->
                    <div class="lg:col-span-7 space-y-5">
                        <!-- Title & Badges -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    <span x-text="selectedEvent?.jenis"></span>
                                </span>
                                <template x-if="selectedEvent?.is_eo_event">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-violet-50 text-violet-700 border border-violet-200/80">
                                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span>Event EO</span>
                                    </span>
                                </template>
                                <template x-if="!selectedEvent?.is_eo_event">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Agenda Internal</span>
                                    </span>
                                </template>
                                <template x-if="selectedEvent?.status">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200" x-text="selectedEvent.status"></span>
                                </template>
                            </div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 leading-snug tracking-tight" x-text="selectedEvent?.title"></h2>
                        </div>

                        <!-- 2x2 Info Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <!-- Tanggal -->
                            <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3.5 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Tanggal</div>
                                    <div class="text-sm font-bold text-slate-800 truncate" x-text="selectedEvent ? formatDate(selectedEvent.tanggal) : '-'"></div>
                                </div>
                            </div>

                            <!-- Waktu -->
                            <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3.5 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Waktu Pelaksanaan</div>
                                    <div class="text-sm font-bold text-slate-800" x-text="(selectedEvent?.waktu_mulai || '-') + ' - ' + (selectedEvent?.waktu_selesai || '-')"></div>
                                </div>
                            </div>

                            <!-- Lokasi -->
                            <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3.5 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Lokasi / Instansi</div>
                                    <div class="text-sm font-bold text-slate-800 truncate" x-text="selectedEvent?.lokasi || '-'"></div>
                                </div>
                            </div>

                            <!-- Dibuat Oleh -->
                            <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3.5 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Dibuat Oleh</div>
                                    <div class="text-sm font-bold text-slate-800 truncate" x-text="selectedEvent?.eo_name || '-'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div class="bg-slate-50/60 border border-slate-200/70 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider" x-text="selectedEvent?.is_eo_event ? 'Catatan Event' : 'Catatan Agenda'"></h4>
                            </div>
                            <p class="text-xs md:text-sm text-slate-700 leading-relaxed whitespace-pre-line" x-text="selectedEvent?.deskripsi ? selectedEvent.deskripsi : (selectedEvent?.is_eo_event ? 'Tidak ada catatan tambahan untuk event ini.' : 'Tidak ada catatan tambahan untuk agenda ini.')"></p>
                        </div>
                    </div>

                    <!-- Right Column (Span 5): Penanggung Jawab & Penugasan Personil -->
                    <div class="lg:col-span-5 space-y-4 flex flex-col justify-between">
                        <div class="space-y-4">
                            <!-- Takeover / Penanggung Jawab Section (khusus Event EO) -->
                            <template x-if="selectedEvent?.is_eo_event">
                                <div class="rounded-xl overflow-hidden border border-slate-200/70 shadow-2xs">
                                    <!-- Case 1: Sudah di-Takeover -->
                                    <template x-if="selectedEvent?.takeover_by">
                                        <div class="bg-emerald-50/90 border-emerald-200 p-4">
                                            <div class="flex items-center gap-2 mb-2.5">
                                                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs shrink-0 shadow-2xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </span>
                                                <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Penanggung Jawab</span>
                                            </div>
                                            <div class="bg-white/90 rounded-lg p-3 border border-emerald-100 shadow-2xs">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="font-bold text-slate-900 text-sm truncate" x-text="selectedEvent.takeover_by.name"></div>
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 uppercase tracking-wider shrink-0" x-text="selectedEvent.takeover_by.role"></span>
                                                </div>
                                                <p class="text-[11px] text-emerald-700 mt-1 flex items-center gap-1">
                                                    <span>Telah diambil alih (Takeover)</span>
                                                    <span x-show="selectedEvent.takeover_at" x-text="'pada ' + selectedEvent.takeover_at"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Case 2: Belum di-Takeover -->
                                    <template x-if="!selectedEvent?.takeover_by">
                                        <div class="bg-amber-50/90 border-amber-200 p-4">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0 font-bold text-xs shadow-xs">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-xs font-bold text-amber-900 mb-0.5">Belum Ada Penanggung Jawab</div>
                                                    <p class="text-[11px] text-amber-700 leading-snug">Event ini belum memiliki penanggung jawab operasional.</p>
                                                </div>
                                            </div>

                                            <!-- Tombol Takeover -->
                                            <template x-if="canTakeover(selectedEvent)">
                                                <div class="mt-3 pt-3 border-t border-amber-200/80">
                                                    <button 
                                                        type="button" 
                                                        @click="doTakeover(selectedEvent)" 
                                                        :disabled="takeoverLoading"
                                                        class="w-full py-2 px-3.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-2"
                                                    >
                                                        <svg x-show="!takeoverLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        <svg x-show="takeoverLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                        <span x-text="takeoverLoading ? 'Memproses...' : 'Ambil Alih Event (Takeover)'"></span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Penugasan Personil Section (Khusus Event EO) -->
                            <template x-if="selectedEvent?.is_eo_event">
                                <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Penugasan Personil</h4>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500 bg-white border border-slate-200 rounded-md px-1.5 py-0.5" x-text="((selectedEvent?.spvs?.length || 0) + (selectedEvent?.sales?.length || 0)) + ' Personil'"></span>
                                </div>
                                
                                <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                                    <template x-for="spv in selectedEvent?.spvs" :key="spv.id">
                                        <div class="bg-white rounded-lg p-2.5 border border-slate-200/80 shadow-2xs">
                                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                                <div class="font-bold text-slate-800 text-xs truncate" x-text="spv.name"></div>
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200/60 uppercase">SPV</span>
                                            </div>
                                            <div class="pl-2 border-l-2 border-blue-200 space-y-1">
                                                <template x-for="sales in getSalesForSpv(spv.id)" :key="sales.id">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                        <span class="text-[11px] text-slate-600 truncate" x-text="sales.name"></span>
                                                    </div>
                                                </template>
                                                <div x-show="getSalesForSpv(spv.id).length === 0" class="text-[11px] text-slate-400 italic">Belum ada tim di bawah SPV ini.</div>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <div x-show="!selectedEvent?.spvs?.length && selectedEvent?.sales?.length" class="bg-white rounded-lg p-2.5 border border-slate-200/80 shadow-2xs">
                                        <div class="font-bold text-slate-800 text-xs mb-1.5">Anggota Terlibat</div>
                                        <div class="pl-2 border-l-2 border-blue-200 space-y-1">
                                            <template x-for="person in selectedEvent?.sales" :key="person.id">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                    <span class="text-[11px] text-slate-600 truncate" x-text="person.name"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div x-show="!selectedEvent?.spvs?.length && !selectedEvent?.sales?.length" class="text-center py-4 text-xs text-slate-400 bg-white/60 rounded-lg border border-dashed border-slate-200">
                                        Belum ada personil yang ditugaskan.
                                    </div>
                                </div>
                            </div>
                        </template>

                            <!-- Case B: Internal Agenda Details (Informasi & Koordinasi Tim) -->
                            <template x-if="!selectedEvent?.is_eo_event">
                                <div class="space-y-4">
                                    <!-- Status Agenda Box -->
                                    <div class="bg-emerald-50/70 border border-emerald-200/70 rounded-xl p-4">
                                        <div class="flex items-center gap-2 mb-2.5">
                                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs shrink-0 shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </span>
                                            <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Status Agenda</span>
                                        </div>
                                        <div class="bg-white/90 rounded-lg p-3 border border-emerald-100 shadow-2xs space-y-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs text-slate-500">Kategori Kegiatan:</span>
                                                <span class="text-xs font-bold text-slate-800" x-text="selectedEvent?.jenis || 'Agenda Internal'"></span>
                                            </div>
                                            <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100">
                                                <span class="text-xs text-slate-500">Status Agenda:</span>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-800 bg-emerald-100/70 px-2.5 py-0.5 rounded-full">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>Terjadwal Aktif</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Koordinasi Internal Tim -->
                                    <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Koordinasi Internal</h4>
                                        </div>
                                        <div class="bg-white rounded-lg p-3 border border-slate-200/80 shadow-2xs">
                                            <p class="text-xs text-slate-600 leading-relaxed">
                                                Agenda ini dibagikan secara transparan kepada seluruh tim operasional CRM untuk sinkronisasi jadwal kegiatan, meeting, dan kunjungan.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex items-center justify-between gap-3 px-6 md:px-7 py-4 border-t border-slate-100 bg-slate-50/70">
                <div class="flex items-center gap-2">
                    <template x-if="canManageAgenda(selectedEvent)">
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="openEditAgenda(selectedEvent)" 
                                class="px-3.5 py-2 text-xs font-bold text-blue-700 bg-white border border-blue-200 rounded-xl hover:bg-blue-50 transition shadow-2xs flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                <span>Edit Agenda</span>
                            </button>
                            <button 
                                type="button" 
                                @click="doDeleteAgenda(selectedEvent)" 
                                :disabled="agendaDeleteLoading"
                                class="px-3.5 py-2 text-xs font-bold text-rose-700 bg-white border border-rose-200 rounded-xl hover:bg-rose-50 disabled:opacity-50 transition shadow-2xs flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                <span x-text="agendaDeleteLoading ? 'Menghapus...' : 'Hapus Agenda'"></span>
                            </button>
                        </div>
                    </template>
                    <template x-if="canManageEvent(selectedEvent)">
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="openEditEvent(selectedEvent)" 
                                class="px-3.5 py-2 text-xs font-bold text-indigo-700 bg-white border border-indigo-200 rounded-xl hover:bg-indigo-50 transition shadow-2xs flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                <span>Edit Event</span>
                            </button>
                            <button 
                                type="button" 
                                @click="doDeleteEvent(selectedEvent)" 
                                :disabled="eventDeleteLoading"
                                class="px-3.5 py-2 text-xs font-bold text-rose-700 bg-white border border-rose-200 rounded-xl hover:bg-rose-50 disabled:opacity-50 transition shadow-2xs flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                <span x-text="eventDeleteLoading ? 'Menghapus...' : 'Hapus Event'"></span>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="modalEventDetail = false" class="px-5 py-2 text-xs md:text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition shadow-2xs">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Agenda (Structured Multi-Card Form Layout) -->
    <div x-show="modalAddAgenda" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="modalAddAgenda" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" 
             @click="modalAddAgenda = false"></div>
        <div x-show="modalAddAgenda" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 scale-95 translate-y-2" 
             x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
             x-transition:leave-end="opacity-0 scale-95 translate-y-2" 
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl md:max-w-4xl h-[90vh] max-h-[90vh] flex flex-col transform transition-all overflow-hidden border border-slate-100">
            <form action="{{ route('calendar.meetings.store') }}" method="POST" x-data="{ isSubmitting: false }" @submit="if(isSubmitting){ $event.preventDefault(); return false; } isSubmitting = true" class="flex flex-col h-full min-h-0 overflow-hidden">
                @csrf
                <!-- Header (Identik dengan modal detail) -->
                <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border shadow-2xs bg-blue-100 text-blue-600 border-blue-200/60">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 leading-tight">Buat Agenda Baru</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Lengkapi data agenda kegiatan atau meeting koordinasi internal</p>
                        </div>
                    </div>
                    <button type="button" @click="modalAddAgenda = false" class="text-slate-400 hover:text-slate-600 transition p-2 rounded-xl hover:bg-slate-200/60 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Body: 2-Column Balanced Cards Form Layout -->
                <div class="flex-1 min-h-0 p-6 md:p-7 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <!-- Kolom Kiri: Informasi Pokok & Lokasi -->
                        <div class="space-y-5">
                            <!-- Card 1: Informasi Pokok -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-4">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                                    <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Informasi Pokok</h4>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Nama Agenda <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" name="name" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: Kunjungan Edukasi SMA N 1 Cirebon">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Jenis Agenda <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="jenis" required class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                            <option value="Kunjungan Sekolah">Kunjungan Sekolah</option>
                                            <option value="Rapat Internal">Rapat Internal</option>
                                            <option value="Koordinasi">Koordinasi</option>
                                            <option value="Follow Up">Follow Up</option>
                                            <option value="Event">Event</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Lokasi / Ruangan -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-3">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Lokasi / Instansi</h4>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Lokasi / Instansi Tujuan
                                    </label>
                                    <input type="text" name="lokasi" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: SMA Negeri 1 Cirebon atau Ruang Rapat Lt. 2">
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Jadwal Pelaksanaan & Catatan Tambahan -->
                        <div class="space-y-5">
                            <!-- Card 3: Jadwal Pelaksanaan -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-4">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Jadwal & Waktu</h4>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tanggal Pelaksanaan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" name="tanggal" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                            Waktu Mulai <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="time" name="waktu_mulai" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                            Waktu Selesai <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="time" name="waktu_selesai" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                    </div>
                                </div>
                            </div>

                            <!-- Card 4: Catatan & Instruksi -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-3">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Catatan Tambahan</h4>
                                    </div>
                                    <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                                </div>

                                <div>
                                    <textarea name="deskripsi" rows="3" class="w-full p-3.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs resize-none font-medium leading-relaxed" placeholder="Tuliskan catatan koordinasi atau persiapan penting..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer (Identik dengan modal detail) -->
                <div class="shrink-0 px-6 py-4 border-t border-slate-100 bg-slate-50/80 rounded-b-2xl flex items-center justify-between">
                    <div class="text-xs text-slate-500 flex items-center gap-1.5 hidden sm:flex">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Agenda akan tersinkronisasi otomatis ke kalender tim</span>
                    </div>
                    <div class="flex items-center gap-2.5 ml-auto">
                        <button type="button" @click="modalAddAgenda = false" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition shadow-2xs">Batal</button>
                        <button type="submit" :disabled="isSubmitting" class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl disabled:opacity-50 transition shadow-sm hover:shadow flex items-center gap-2">
                            <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Agenda'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Agenda (Structured Multi-Card Form Layout) -->
    <div x-show="modalEditAgenda" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="modalEditAgenda" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" 
             @click="modalEditAgenda = false"></div>
        <div x-show="modalEditAgenda" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 scale-95 translate-y-2" 
             x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
             x-transition:leave-end="opacity-0 scale-95 translate-y-2" 
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl md:max-w-4xl h-[90vh] max-h-[90vh] flex flex-col transform transition-all overflow-hidden border border-slate-100">
            <form :action="`/calendar/meetings/${selectedAgenda?.id}`" method="POST" x-data="{ isSubmitting: false }" @submit="if(isSubmitting){ $event.preventDefault(); return false; } isSubmitting = true" class="flex flex-col h-full min-h-0 overflow-hidden">
                @csrf
                @method('PUT')
                <!-- Header (Identik dengan modal detail) -->
                <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border shadow-2xs bg-amber-100 text-amber-600 border-amber-200/60">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 leading-tight">Edit Agenda</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Perbarui informasi dan jadwal kegiatan agenda internal</p>
                        </div>
                    </div>
                    <button type="button" @click="modalEditAgenda = false" class="text-slate-400 hover:text-slate-600 transition p-2 rounded-xl hover:bg-slate-200/60 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Body: 2-Column Balanced Cards Form Layout -->
                <div class="flex-1 min-h-0 p-6 md:p-7 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <!-- Kolom Kiri: Informasi Pokok & Lokasi -->
                        <div class="space-y-5">
                            <!-- Card 1: Informasi Pokok -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-4">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                                    <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Informasi Pokok</h4>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Nama Agenda <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" name="name" :value="selectedAgenda?.title" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Jenis Agenda <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="jenis" :value="selectedAgenda?.jenis" required class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                            <option value="Kunjungan Sekolah">Kunjungan Sekolah</option>
                                            <option value="Rapat Internal">Rapat Internal</option>
                                            <option value="Koordinasi">Koordinasi</option>
                                            <option value="Follow Up">Follow Up</option>
                                            <option value="Event">Event</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Lokasi / Ruangan -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-3">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Lokasi / Instansi</h4>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Lokasi / Instansi Tujuan
                                    </label>
                                    <input type="text" name="lokasi" :value="selectedAgenda?.lokasi === '-' ? '' : selectedAgenda?.lokasi" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: SMA Negeri 1 Cirebon atau Ruang Rapat Lt. 2">
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Jadwal Pelaksanaan & Catatan Tambahan -->
                        <div class="space-y-5">
                            <!-- Card 3: Jadwal Pelaksanaan -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-4">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Jadwal & Waktu</h4>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tanggal Pelaksanaan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" name="tanggal" :value="selectedAgenda?.tanggal" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                            Waktu Mulai <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="time" name="waktu_mulai" :value="selectedAgenda?.waktu_mulai" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                            Waktu Selesai <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="time" name="waktu_selesai" :value="selectedAgenda?.waktu_selesai" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                    </div>
                                </div>
                            </div>

                            <!-- Card 4: Catatan & Instruksi -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-3">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Catatan Tambahan</h4>
                                    </div>
                                    <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                                </div>

                                <div>
                                    <textarea name="deskripsi" :value="selectedAgenda?.deskripsi" rows="3" class="w-full p-3.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs resize-none font-medium leading-relaxed" placeholder="Catatan opsional..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer (Identik dengan modal detail) -->
                <div class="shrink-0 px-6 py-4 border-t border-slate-100 bg-slate-50/80 rounded-b-2xl flex items-center justify-between">
                    <div class="text-xs text-slate-500 flex items-center gap-1.5 hidden sm:flex">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Perubahan akan langsung sinkron ke seluruh kalender</span>
                    </div>
                    <div class="flex items-center gap-2.5 ml-auto">
                        <button type="button" @click="modalEditAgenda = false" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition shadow-2xs">Batal</button>
                        <button type="submit" :disabled="isSubmitting" class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl disabled:opacity-50 transition shadow-sm hover:shadow flex items-center gap-2">
                            <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Event EO -->
    <div x-show="modalEditEvent" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="modalEditEvent" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" 
             @click="modalEditEvent = false"></div>
        <div x-show="modalEditEvent" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 scale-95 translate-y-2" 
             x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
             x-transition:leave-end="opacity-0 scale-95 translate-y-2" 
              class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl md:max-w-4xl h-[90vh] max-h-[90vh] flex flex-col transform transition-all overflow-hidden border border-slate-100">
            <form :action="`/calendar/events/${selectedEvent?.id}`" method="POST" x-data="{ isSubmitting: false }" @submit="if(isSubmitting){ $event.preventDefault(); return false; } isSubmitting = true" class="flex flex-col h-full min-h-0 overflow-hidden">
                @csrf
                @method('PUT')
                <!-- Header -->
                <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border shadow-2xs bg-indigo-100 text-indigo-600 border-indigo-200/60">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 leading-tight">Edit Event EO</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Perbarui rincian pelaksanaan, lokasi, atau target institusi event</p>
                        </div>
                    </div>
                    <button type="button" @click="modalEditEvent = false" class="text-slate-400 hover:text-slate-600 transition p-2 rounded-xl hover:bg-slate-200/60 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Body: 2-Column Form Layout -->
                <div class="flex-1 min-h-0 p-6 md:p-7 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <!-- Kolom Kiri: Informasi Pokok & Jadwal -->
                        <div class="space-y-5">
                            <!-- Card 1: Informasi Pokok -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-4">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                                    <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Informasi Event</h4>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Nama Event <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" name="name" :value="selectedEvent?.name || selectedEvent?.title" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Jenis Event <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="type_id" :value="selectedEvent?.type_id" required class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                            <option value="">-- Pilih Jenis Event --</option>
                                            @foreach($eventTypes as $type)
                                                <option value="{{ $type->id }}" :selected="selectedEvent?.type_id == {{ $type->id }}">{{ $type->nama }}</option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Lokasi Event <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" name="lokasi" :value="selectedEvent?.lokasi" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: Aula SMA Pasundan 1 Cirebon">
                                </div>
                            </div>

                            <!-- Card 2: Jadwal Pelaksanaan -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-4">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Jadwal & Waktu</h4>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tanggal Pelaksanaan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" name="tanggal" :value="selectedEvent?.tanggal" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                            Waktu Mulai <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="time" name="waktu_mulai" :value="selectedEvent?.waktu_mulai" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                            Waktu Selesai <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="time" name="waktu_selesai" :value="selectedEvent?.waktu_selesai" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Institusi, Akademik, & Catatan -->
                        <div class="space-y-5">
                            <!-- Card 3: Informasi Target & PIC -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-3.5">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Target Institusi & PIC</h4>
                                    </div>
                                    <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jenis Institusi</label>
                                        <select name="jenis_institusi" :value="selectedEvent?.jenis_institusi" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 font-medium">
                                            <option value="">-- Pilih --</option>
                                            <option value="Sekolah" :selected="selectedEvent?.jenis_institusi == 'Sekolah'">Sekolah / Kampus</option>
                                            <option value="Perusahaan" :selected="selectedEvent?.jenis_institusi == 'Perusahaan'">Perusahaan</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Institusi</label>
                                        <input type="text" name="nama_institusi" :value="selectedEvent?.nama_institusi" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 font-medium placeholder:text-slate-400" placeholder="Nama sekolah/instansi">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alamat</label>
                                    <input type="text" name="alamat" :value="selectedEvent?.alamat" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 font-medium placeholder:text-slate-400" placeholder="Alamat lengkap...">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama PIC</label>
                                        <input type="text" name="pic_name" :value="selectedEvent?.pic_name" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 font-medium placeholder:text-slate-400" placeholder="Bpk/Ibu PIC">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">WhatsApp PIC</label>
                                        <x-input-whatsapp name="pic_whatsapp" bindValue="selectedEvent?.pic_whatsapp" placeholder="8123456789" />
                                    </div>
                                </div>
                            </div>

                            <!-- Card 4: Akademik & Catatan -->
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4.5 space-y-3.5">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akademik & Deskripsi</h4>
                                    </div>
                                    <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dosen / Pemateri</label>
                                        <input type="text" name="dosen_pemateri" :value="selectedEvent?.dosen_pemateri" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 font-medium placeholder:text-slate-400" placeholder="Nama dosen/pemateri">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Program Studi</label>
                                        <select name="prodi_id" :value="selectedEvent?.prodi_id" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 font-medium">
                                            <option value="">-- Pilih Prodi --</option>
                                            @foreach($prodis as $prodi)
                                                <option value="{{ $prodi->id }}" :selected="selectedEvent?.prodi_id == {{ $prodi->id }}">{{ $prodi->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Deskripsi / Catatan Tambahan</label>
                                    <textarea name="deskripsi" :value="selectedEvent?.deskripsi" rows="2" class="w-full p-3 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 font-medium placeholder:text-slate-400 resize-none" placeholder="Catatan instruksi event..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer -->
                <div class="shrink-0 px-6 py-4 border-t border-slate-100 bg-slate-50/80 rounded-b-2xl flex items-center justify-between">
                    <div class="text-xs text-slate-500 flex items-center gap-1.5 hidden sm:flex">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Perubahan event akan langsung sinkron ke seluruh kalender tim</span>
                    </div>
                    <div class="flex items-center gap-2.5 ml-auto">
                        <button type="button" @click="modalEditEvent = false" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition shadow-2xs">Batal</button>
                        <button type="submit" :disabled="isSubmitting" class="px-6 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 rounded-xl disabled:opacity-50 transition shadow-sm hover:shadow flex items-center gap-2">
                            <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<script>
    function calendarApp() {
        return {
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            month: new Date().getMonth(),
            year: new Date().getFullYear(),
            calendarDays: [],
            events: [],
            loading: false,
            agendaFilter: 'Semua',
            modalEventDetail: false,
            modalAddAgenda: false,
            modalEditAgenda: false,
            modalEditEvent: false,
            selectedEvent: null,
            selectedAgenda: null,
            agendaDeleteLoading: false,
            eventDeleteLoading: false,
            agendaAddForm: {
                name: '',
                jenis: 'Kunjungan Sekolah',
                tanggal: new Date().toISOString().split('T')[0],
                waktu_mulai: '08:00',
                waktu_selesai: '10:00',
                lokasi: '',
                deskripsi: ''
            },
            agendaEditForm: {
                id: null,
                name: '',
                jenis: 'Kunjungan Sekolah',
                tanggal: '',
                waktu_mulai: '',
                waktu_selesai: '',
                lokasi: '',
                deskripsi: ''
            },

            openAddAgenda() {
                this.agendaAddForm = {
                    name: '',
                    jenis: 'Kunjungan Sekolah',
                    tanggal: new Date().toISOString().split('T')[0],
                    waktu_mulai: '08:00',
                    waktu_selesai: '10:00',
                    lokasi: '',
                    deskripsi: ''
                };
                this.modalAddAgenda = true;
            },

            init() {
                this.generateCalendar();
                this.fetchEvents();
            },

            generateCalendar() {
                this.calendarDays = [];
                let firstDay = new Date(this.year, this.month, 1).getDay();
                firstDay = firstDay === 0 ? 6 : firstDay - 1; // Adjust for Monday start
                
                let daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                let daysInPrevMonth = new Date(this.year, this.month, 0).getDate();

                // Previous month overflow
                for (let i = firstDay - 1; i >= 0; i--) {
                    this.calendarDays.push({
                        day: daysInPrevMonth - i,
                        isCurrentMonth: false,
                        date: this.formatDateString(this.year, this.month - 1, daysInPrevMonth - i)
                    });
                }

                // Current month
                for (let i = 1; i <= daysInMonth; i++) {
                    this.calendarDays.push({
                        day: i,
                        isCurrentMonth: true,
                        date: this.formatDateString(this.year, this.month, i)
                    });
                }

                // Next month overflow
                let remaining = 42 - this.calendarDays.length;
                for (let i = 1; i <= remaining; i++) {
                    this.calendarDays.push({
                        day: i,
                        isCurrentMonth: false,
                        date: this.formatDateString(this.year, this.month + 1, i)
                    });
                }
            },

            formatDateString(year, month, day) {
                let d = new Date(year, month, day);
                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            },

            isToday(dateString) {
                const today = new Date();
                return dateString === this.formatDateString(today.getFullYear(), today.getMonth(), today.getDate());
            },

            prevMonth() {
                this.month--;
                if (this.month < 0) {
                    this.month = 11;
                    this.year--;
                }
                this.generateCalendar();
            },

            nextMonth() {
                this.month++;
                if (this.month > 11) {
                    this.month = 0;
                    this.year++;
                }
                this.generateCalendar();
            },

            async fetchEvents() {
                this.loading = true;
                try {
                    const response = await fetch('/api/calendar/events');
                    if (response.ok) {
                        this.events = await response.json();
                    }
                } catch (error) {
                    console.error('Failed to fetch events', error);
                } finally {
                    this.loading = false;
                }
            },

            getEventsForDate(dateString) {
                return this.events.filter(e => {
                    if (e.tanggal !== dateString) return false;
                    if (this.agendaFilter === 'Agenda') return !e.is_eo_event && !e.is_personal;
                    if (this.agendaFilter === 'Event') return e.is_eo_event;
                    if (this.agendaFilter === 'Pribadi') return e.is_personal;
                    return true;
                }).sort((a, b) => a.waktu_mulai.localeCompare(b.waktu_mulai));
            },

            getEventColor(evt) {
                if (evt.status === 'Cancelled') return 'bg-rose-50 text-rose-700 border-rose-200';
                if (evt.status === 'Completed') return 'bg-slate-50 text-slate-600 border-slate-200';
                if (evt.is_eo_event) return 'bg-violet-50 text-violet-700 border-violet-200';
                return 'bg-blue-50 text-blue-700 border-blue-200';
            },

            getEventDotColor(evt) {
                if (evt.status === 'Cancelled') return 'bg-rose-500';
                if (evt.status === 'Completed') return 'bg-slate-400';
                if (evt.is_eo_event) return 'bg-violet-500';
                return 'bg-blue-500';
            },

            openEventDetails(evt) {
                this.selectedEvent = evt;
                this.modalEventDetail = true;
            },

            getSalesForSpv(spvId) {
                if (!this.selectedEvent || !this.selectedEvent.sales) return [];
                return this.selectedEvent.sales.filter(s => s.assigned_by_spv_id === spvId);
            },

            currentUserRole: '{{ auth()->user()->role ?? '' }}',
            currentUserId: {{ auth()->id() ?? 'null' }},
            takeoverLoading: false,

            canManageAgenda(evt) {
                if (!evt || evt.is_eo_event) return false;
                const role = (this.currentUserRole || '').toLowerCase();
                return role !== 'sales';
            },

            canManageEvent(evt) {
                if (!evt || !evt.is_eo_event) return false;
                const role = (this.currentUserRole || '').toLowerCase();
                return role !== 'sales';
            },

            openEditAgenda(evt) {
                if (!evt) return;
                this.selectedAgenda = evt;
                this.agendaEditForm = {
                    id: evt.id,
                    name: evt.title || '',
                    jenis: evt.jenis || 'Kunjungan Sekolah',
                    tanggal: evt.tanggal || '',
                    waktu_mulai: evt.waktu_mulai || '',
                    waktu_selesai: evt.waktu_selesai || '',
                    lokasi: evt.lokasi === '-' ? '' : (evt.lokasi || ''),
                    deskripsi: evt.deskripsi || ''
                };
                this.modalEventDetail = false;
                this.modalEditAgenda = true;
            },

            openEditEvent(evt) {
                if (!evt) return;
                this.selectedEvent = evt;
                this.modalEventDetail = false;
                this.modalEditEvent = true;
            },

            async doDeleteAgenda(evt) {
                if (!evt) return;
                const result = await Swal.fire({
                    title: 'Hapus Agenda?',
                    html: `Agenda <strong>"${evt.title}"</strong> akan dihapus secara permanen dan tidak dapat dikembalikan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Ya, Hapus</span>',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-100',
                        title: 'text-slate-900 font-bold text-lg',
                        htmlContainer: 'text-slate-600 text-sm',
                        confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5 shadow-xs',
                        cancelButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                    }
                });
                if (!result.isConfirmed) return;

                this.agendaDeleteLoading = true;
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(`/calendar/meetings/${evt.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.modalEventDetail = false;
                        await this.fetchEvents();
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: data.message || 'Agenda berhasil dihapus.',
                            timer: 2500,
                            timerProgressBar: true,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-xl shadow-lg border border-emerald-200 bg-white' }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: data.message || 'Terjadi kesalahan saat menghapus agenda.',
                            confirmButtonColor: '#e11d48',
                            confirmButtonText: 'Tutup',
                            customClass: {
                                popup: 'rounded-2xl shadow-xl border border-rose-100',
                                confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                            }
                        });
                    }
                } catch (err) {
                    console.error('Delete agenda error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Jaringan',
                        text: 'Terjadi kesalahan jaringan saat menghapus agenda. Silakan coba lagi.',
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Tutup',
                        customClass: {
                            popup: 'rounded-2xl shadow-xl border border-rose-100',
                            confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                        }
                    });
                } finally {
                    this.agendaDeleteLoading = false;
                }
            },

            async doDeleteEvent(evt) {
                if (!evt) return;
                const result = await Swal.fire({
                    title: 'Hapus Event EO?',
                    html: `Event <strong>"${evt.title || evt.name}"</strong> akan dihapus beserta seluruh data penugasan terkait. Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Ya, Hapus Event</span>',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-100',
                        title: 'text-slate-900 font-bold text-lg',
                        htmlContainer: 'text-slate-600 text-sm',
                        confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5 shadow-xs',
                        cancelButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                    }
                });
                if (!result.isConfirmed) return;

                this.eventDeleteLoading = true;
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(`/calendar/events/${evt.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.modalEventDetail = false;
                        await this.fetchEvents();
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: data.message || 'Event EO berhasil dihapus.',
                            timer: 2500,
                            timerProgressBar: true,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-xl shadow-lg border border-emerald-200 bg-white' }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: data.message || 'Terjadi kesalahan saat menghapus event.',
                            confirmButtonColor: '#e11d48',
                            confirmButtonText: 'Tutup',
                            customClass: {
                                popup: 'rounded-2xl shadow-xl border border-rose-100',
                                confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                            }
                        });
                    }
                } catch (err) {
                    console.error('Delete event error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Jaringan',
                        text: 'Terjadi kesalahan jaringan saat menghapus event. Silakan coba lagi.',
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Tutup',
                        customClass: {
                            popup: 'rounded-2xl shadow-xl border border-rose-100',
                            confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                        }
                    });
                } finally {
                    this.eventDeleteLoading = false;
                }
            },

            canTakeover(evt) {
                if (!evt || !evt.is_eo_event) return false;
                const role = (this.currentUserRole || '').toLowerCase();
                const disallowed = ['admin', 'data analyst', 'data-analyst', 'analyst'];
                if (disallowed.includes(role)) return false;
                return !evt.takeover_by;
            },

            async doTakeover(evt) {
                if (!evt) return;
                const result = await Swal.fire({
                    title: 'Takeover Event?',
                    html: `Anda akan mengambil alih event <strong>"${evt.title}"</strong>. Setelah di-takeover, event ini akan menjadi tanggung jawab Anda.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Ya, Ambil Alih</span>',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-100',
                        title: 'text-slate-900 font-bold text-lg',
                        htmlContainer: 'text-slate-600 text-sm',
                        confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5 shadow-xs',
                        cancelButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                    }
                });
                if (!result.isConfirmed) return;

                this.takeoverLoading = true;
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(`/calendar/events/${evt.id}/takeover`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        await this.fetchEvents();
                        if (this.selectedEvent && this.selectedEvent.id === evt.id) {
                            this.selectedEvent = this.events.find(e => e.id === evt.id) || this.selectedEvent;
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Takeover Berhasil!',
                            text: data.message || 'Event berhasil diambil alih.',
                            timer: 2500,
                            timerProgressBar: true,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-xl shadow-lg border border-emerald-200 bg-white' }
                        });
                    } else {
                        await this.fetchEvents();
                        if (this.selectedEvent && this.selectedEvent.id === evt.id) {
                            this.selectedEvent = this.events.find(e => e.id === evt.id) || this.selectedEvent;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Takeover',
                            text: data.message || 'Tidak dapat mengambil alih event ini.',
                            confirmButtonColor: '#e11d48',
                            confirmButtonText: 'Tutup',
                            customClass: {
                                popup: 'rounded-2xl shadow-xl border border-rose-100',
                                confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                            }
                        });
                    }
                } catch (err) {
                    console.error('Takeover error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Jaringan',
                        text: 'Terjadi kesalahan jaringan saat melakukan takeover. Silakan coba lagi.',
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Tutup',
                        customClass: {
                            popup: 'rounded-2xl shadow-xl border border-rose-100',
                            confirmButton: 'rounded-xl text-xs font-semibold px-5 py-2.5'
                        }
                    });
                } finally {
                    this.takeoverLoading = false;
                }
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return `${d.getDate()} ${this.monthNames[d.getMonth()]} ${d.getFullYear()}`;
            }
        }
    }
</script>
</x-app-layout>
