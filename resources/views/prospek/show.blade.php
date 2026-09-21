@php
    $pageTitle = 'Detail Prospek';
    $pageSubtitle = $prospect['name'];
@endphp

<x-app-layout :title="'Detail Prospek - ' . $prospect['name']">

    <div class="space-y-6" x-data="{
        prospect: {{ json_encode($prospect) }},
        currentStatus: '{{ $prospect['status'] }}',
        modalRealokasi: false
    }">

        <!-- Back Button & Breadcrumbs -->
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route((strtolower(auth()->user()->role ?? '') === 'spv' ? 'spv.' : '') . 'prospek.index') }}" class="hover:text-blue-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Kembali ke Daftar Prospek
            </a>
            <span>/</span>
            <span class="text-slate-900">{{ $prospect['name'] }}</span>
        </div>

        <!-- Detail Header Card -->
        <div class="crm-card bg-white p-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">{{ $prospect['name'] }}</h2>
                        <x-status-badge :status="$prospect['status']" />
                        <span class="px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-semibold">{{ $prospect['type'] }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 font-medium">
                        <span>PIC: <strong class="text-slate-800">{{ $prospect['pic'] }}</strong></span>
                        <span>&bull;</span>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $prospect['whatsapp']) }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 flex items-center gap-1 font-semibold">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            <span>WhatsApp ({{ $prospect['whatsapp'] }})</span>
                        </a>
                        <span>&bull;</span>
                        <span>Created: <strong class="text-slate-800">{{ $prospect['created_at'] }}</strong></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    @php 
                        $prospekModel = \App\Models\Prospek::find($prospect['id']); 
                    @endphp

                    @can('followUp', $prospekModel)
                    <button 
                        type="button" 
                        @click="selectedProspect = prospect; modalFollowUp = true"
                        class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        <span>Follow Up</span>
                    </button>
                    @endcan

                    @can('updateStatus', $prospekModel)
                    <button 
                        type="button" 
                        @click="selectedProspect = prospect; modalUpdateStatus = true"
                        class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        <span>Update Status</span>
                    </button>
                    @endcan

                    @can('reallocate', $prospekModel ?? null)
                    <button 
                        type="button" 
                        @click="modalRealokasi = true"
                        class="px-3.5 py-2 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 hover:bg-purple-100 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        <span>Re-alokasi Sales</span>
                    </button>
                    @endcan

                    @can('takeover', $prospekModel ?? null)
                    <form action="{{ route('prospek.takeover', $prospect['id']) }}" method="POST" class="inline-block" data-confirm="Yakin ingin menyerahkan prospek ini ke CS? Penanganan selanjutnya akan dialihkan ke tim CS.">
                        @csrf
                        <button 
                            type="submit" 
                            class="px-3.5 py-2 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                            <span>Serahkan ke CS</span>
                        </button>
                    </form>
                    @endcan
                </div>
            </div>

            <!-- PIPELINE HORIZONTAL STEPPER -->
            <div class="pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tahapan Pipeline Saat Ini</h3>
                    @if($prospect['status'] === 'Lost')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            Status Khusus: Lost (Arsip Prospek)
                        </span>
                    @endif
                </div>

                <!-- Desktop Stepper -->
                <div class="hidden md:flex items-center justify-between relative">
                    <div class="absolute top-1/2 left-4 right-4 h-1 bg-slate-100 -translate-y-1/2 z-0"></div>
                    
                    @foreach($allStages as $index => $stage)
                        @php
                            $isReached = $prospect['stage_number'] >= $stage['number'];
                            $isCurrent = $prospect['stage_number'] === $stage['number'];
                        @endphp
                        <div class="relative z-10 flex flex-col items-center group">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition {{ $isCurrent ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md' : ($isReached ? 'bg-blue-100 text-blue-700 border border-blue-300' : 'bg-white text-slate-400 border border-slate-200') }}">
                                @if($isReached && !$isCurrent)
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                @else
                                    {{ $stage['number'] }}
                                @endif
                            </div>
                            <span class="text-[11px] mt-2 font-semibold text-center {{ $isCurrent ? 'text-blue-700 font-bold' : ($isReached ? 'text-slate-800' : 'text-slate-400') }}">
                                {{ $stage['name'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- Mobile Stepper (Scrollable) -->
                <div class="md:hidden overflow-x-auto pb-2 flex items-center gap-3">
                    @foreach($allStages as $stage)
                        @php
                            $isReached = $prospect['stage_number'] >= $stage['number'];
                            $isCurrent = $prospect['stage_number'] === $stage['number'];
                        @endphp
                        <div class="shrink-0 flex items-center gap-2 p-2.5 rounded-xl border {{ $isCurrent ? 'bg-blue-50 border-blue-400 text-blue-700 font-bold' : ($isReached ? 'bg-slate-50 border-slate-200 text-slate-800' : 'opacity-50 border-dashed border-slate-200') }}">
                            <span class="w-5 h-5 rounded-full {{ $isCurrent ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700' }} flex items-center justify-center text-[10px] font-bold">
                                {{ $stage['number'] }}
                            </span>
                            <span class="text-xs whitespace-nowrap">{{ $stage['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- 2-COLUMN MAIN CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT COLUMN: DETAIL & TAKEOVER -->
            <div class="space-y-6">


                <!-- INFORMASI LENGKAP PROSPEK -->
                <div class="crm-card bg-white p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">Informasi Prospek</h3>
                    
                    <div class="space-y-3 text-xs">
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Sumber Prospek</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospect['source'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Sumber Prospek</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospect['source'] }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi Calon Mahasiswa</span>
                            <p class="font-medium text-slate-800 mt-0.5 leading-relaxed">{{ $prospect['potential'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Training AI & Robotics</span>
                            <p class="font-semibold text-blue-600 mt-0.5">{{ $prospect['ai_training'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Catatan Internal</span>
                            <p class="text-slate-600 mt-0.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100">{{ $prospect['notes'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: ACTIVITY TIMELINE -->
            <div class="crm-card bg-white p-6 lg:col-span-2 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Riwayat Aktivitas & Timeline</h3>
                        <p class="text-xs text-slate-500">Log lengkap interaksi, perubahan status, dan catatan tim</p>
                    </div>
                    <button 
                        @click="selectedProspect = prospect; modalFollowUp = true"
                        class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-xs transition"
                    >
                        + Tambah Catatan
                    </button>
                </div>

                <!-- Timeline Items -->
                <div class="space-y-6 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-slate-200">
                    @foreach($prospect['timeline'] as $item)
                        <div class="relative flex items-start gap-4">
                            <!-- Timeline Dot / Avatar -->
                            <div class="w-6 h-6 rounded-full bg-white border-2 {{ str_contains($item['title'], 'Re-alokasi') ? 'border-purple-600 text-purple-600' : 'border-blue-600 text-blue-600' }} font-bold flex items-center justify-center text-[10px] shrink-0 z-10">
                                @if(str_contains($item['title'], 'Re-alokasi')) ⇄ @elseif($item['role'] === 'Sales') S @elseif($item['role'] === 'CS') C @else ✓ @endif
                            </div>

                            <div class="flex-1 {{ str_contains($item['title'], 'Re-alokasi') ? 'bg-purple-50/60 border-purple-200' : 'bg-slate-50/70 border-slate-200/80' }} p-4 rounded-xl border space-y-1">
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900">{{ $item['title'] }}</span>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded {{ str_contains($item['title'], 'Re-alokasi') ? 'bg-purple-100 text-purple-700' : 'bg-slate-200 text-slate-700' }} font-medium">{{ $item['role'] }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400">{{ $item['time'] }}</span>
                                </div>
                                
                                <p class="text-xs text-slate-600 pt-1 leading-relaxed">{{ $item['notes'] }}</p>
                                <div class="text-[10px] text-slate-400 pt-1 font-medium">Oleh: {{ $item['user'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- MODAL RE-ALOKASI PROSPEK (SPV / MANAGEMENT) -->
        <div 
            x-show="modalRealokasi" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog" 
            aria-modal="true"
        >
            <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="modalRealokasi" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalRealokasi = false"></div>

                <div 
                    x-show="modalRealokasi" 
                    class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10"
                >
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Re-alokasi Prospek ke Sales Lain</h3>
                            <p class="text-xs text-slate-500">Pindahkan penugasan prospek ke personil sales lain dalam tim Anda.</p>
                        </div>
                        <button @click="modalRealokasi = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form action="{{ route('prospek.realokasi', $prospect['id']) }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Sales Saat Ini</label>
                            <input type="text" readonly value="{{ $prospect['takeover_sales'] ?? 'Belum Ditugaskan' }}" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-semibold cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Sales Baru (Penerima Tugas) *</label>
                            <select name="sales_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition bg-white font-medium">
                                <option value="">-- Pilih Sales Anggota Tim --</option>
                                @foreach($salesTeam as $st)
                                    <option value="{{ $st->id }}" {{ (isset($prospekModel) && $prospekModel->sales_id == $st->id) ? 'disabled' : '' }}>
                                        {{ $st->name }} ({{ $st->email }}) {{ (isset($prospekModel) && $prospekModel->sales_id == $st->id) ? '- (Saat ini)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan / Catatan Re-alokasi</label>
                            <textarea name="alasan" rows="3" placeholder="Contoh: Pembagian beban wilayah / percepatan follow-up..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition"></textarea>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalRealokasi = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition cursor-pointer">
                                Simpan Re-alokasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
