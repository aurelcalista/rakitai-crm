@php
    $isEventMode  = isset($event) && $event !== null;
    $pageTitle    = $isEventMode ? 'Laporan Kunjungan Event' : 'Tambah Kunjungan Mandiri';
    $pageSubtitle = $isEventMode
        ? 'Form laporan kunjungan terikat dengan Event: ' . ($event->nama ?? $event->name)
        : 'Kunjungan tidak terkait dengan Jadwal Event';

    $schoolOptions = [];
    if(isset($prospekSekolah) && $prospekSekolah->isNotEmpty()) {
        foreach($prospekSekolah as $p) {
            $schoolOptions[] = [
                'value' => 'prospek_' . $p->id,
                'label' => $p->name,
                'sub' => '📋 Prospek • Status: ' . $p->status . ($p->pic ? ' • PIC: ' . $p->pic : ''),
            ];
        }
    }
    if(isset($sekolahs) && $sekolahs->isNotEmpty()) {
        foreach($sekolahs as $s) {
            $schoolOptions[] = [
                'value' => 'sekolah_' . $s->id,
                'label' => $s->nama,
                'sub' => 'Master Database Sekolah' . ($s->kota ? ' • ' . $s->kota : ''),
            ];
        }
    }

    $perusahaanOptions = [];
    if(isset($perusahaans) && $perusahaans->isNotEmpty()) {
        foreach($perusahaans as $p) {
            $perusahaanOptions[] = [
                'value' => (string)$p->id,
                'label' => $p->nama,
                'sub' => '🏢 Database Perusahaan' . ($p->kota ? ' • ' . $p->kota : ($p->alamat ? ' • ' . $p->alamat : '')),
            ];
        }
    }
@endphp

<x-app-layout :title="'Tambah Kunjungan - CRM UCIC'">
    <div class="space-y-6">

        {{-- ─── Page Header ─────────────────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3">
                <a href="{{ route('sales.kunjungan.index') }}" class="p-1.5 rounded-lg bg-slate-100 text-slate-500 hover:text-slate-700 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $pageTitle }}</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">{{ $pageSubtitle }}</p>
                </div>
            </div>
            @if($isEventMode)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-bold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Dari Jadwal Event
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 text-slate-600 border border-slate-200 text-xs font-bold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Kunjungan Mandiri
                </span>
            @endif
        </div>

        {{-- Flash errors --}}
        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-sm text-rose-700">
                <p class="font-bold mb-1">Terdapat kesalahan input:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ─── MAIN FORM ───────────────────────────────────────────────────── --}}
        <form
            action="{{ route('sales.kunjungan.store') }}"
            method="POST"
            enctype="multipart/form-data"
            x-data="{
                jenisTab: '{{ $isEventMode ? ($event->jenis_institusi ?? 'Sekolah') : 'Sekolah' }}',
                fotoPreview: null,
                geoStatus: 'idle', /* idle | loading | ok | error */
                lat: '',
                lng: '',
                isTraining: {{ old('kesediaan_training_ai') ? 'true' : 'false' }},
                modeManualSekolah: false,
                sekolah_manual: '{{ old('sekolah_manual', '') }}',
                modeManualCorp: false,
                perusahaan_manual: '{{ old('perusahaan_manual', '') }}',
                selectedSchoolSource: '',
                selectedPerusahaanId: '{{ old('perusahaan_id', '') }}',
                prospek_id: '',
                sekolah_id: '{{ old('sekolah_id', '') }}',
                nama_institusi: '{{ old('nama_institusi', '') }}',
                pic_name: '{{ old('pic_name', '') }}',
                pic_whatsapp: '{{ old('pic_whatsapp', '') }}',
                prospekMap: {{ json_encode(($prospekSekolah ?? collect())->keyBy('id')->toArray()) }},
                sekolahMap: {{ json_encode($sekolahs->keyBy('id')->toArray()) }},
                perusahaanMap: {{ json_encode(($perusahaans ?? collect())->keyBy('id')->toArray()) }},
                handlePerusahaanChange(id) {
                    this.selectedPerusahaanId = id;
                    if (id && this.perusahaanMap[id]) {
                        this.nama_institusi = this.perusahaanMap[id].nama || '';
                        if (this.perusahaanMap[id].pic_name) this.pic_name = this.perusahaanMap[id].pic_name;
                        if (this.perusahaanMap[id].pic_phone) this.pic_whatsapp = this.perusahaanMap[id].pic_phone;
                    } else {
                        if (!this.modeManualCorp) {
                            this.nama_institusi = '';
                        }
                    }
                },
                handleSchoolChange(val) {
                    this.selectedSchoolSource = val;
                    if (val && val.startsWith('prospek_')) {
                        const pid = val.replace('prospek_', '');
                        const p = this.prospekMap[pid];
                        if (p) {
                            this.prospek_id = p.id;
                            this.sekolah_id = p.sekolah_id || '';
                            this.nama_institusi = p.name || '';
                            this.pic_name = p.pic || '';
                            this.pic_whatsapp = p.whatsapp || '';
                        }
                    } else if (val && val.startsWith('sekolah_')) {
                        const sid = val.replace('sekolah_', '');
                        this.prospek_id = '';
                        this.sekolah_id = sid;
                        const s = this.sekolahMap[sid];
                        if (s) {
                            this.nama_institusi = s.nama || '';
                            this.pic_name = s.pic_name || '';
                            this.pic_whatsapp = s.pic_phone || '';
                        }
                    } else {
                        this.prospek_id = '';
                        this.sekolah_id = '';
                        if (!this.modeManualSekolah) {
                            this.nama_institusi = '';
                        }
                    }
                },
                captureGeo() {
                    this.geoStatus = 'loading';
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.lat = pos.coords.latitude;
                            this.lng = pos.coords.longitude;
                            this.geoStatus = 'ok';
                        },
                        () => { this.geoStatus = 'error'; },
                        { enableHighAccuracy: true, timeout: 15000 }
                    );
                }
            }"
            @submit.prevent="if(!lat || !lng){ alert('Lokasi GPS belum ditangkap. Tekan tombol Tangkap GPS terlebih dahulu.'); return; } $el.submit();"
        >
            @csrf

            {{-- Hidden: event_id (hanya untuk Alur 1) --}}
            @if($isEventMode)
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <input type="hidden" name="jenis" value="{{ $event->jenis_institusi ?? 'Sekolah' }}">
            @endif

            <input type="hidden" name="lat" :value="lat">
            <input type="hidden" name="lng" :value="lng">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ── KOLOM KIRI: Info Instansi + Data Laporan ──────────────────── --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- ─── BLOK A: INFORMASI INSTANSI / EVENT ─────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">

                        @if($isEventMode)
                            {{-- ════ ALUR 1: DATA DARI EVENT (READ-ONLY) ════ --}}
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Informasi Event / Instansi
                                </h3>
                                <p class="text-xs text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 mb-4">
                                    Data instansi di bawah berasal dari Event yang sudah terdaftar. Anda tidak perlu mengisi ulang data ini.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Nama Event</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->nama ?? $event->name }}</p>
                                </div>
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Jenis Institusi</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->jenis_institusi ?? '-' }}</p>
                                </div>
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Nama Instansi</p>
                                    <p class="text-sm font-bold text-slate-800">
                                        {{ $event->nama_institusi ?: ($event->sekolah?->nama ?? $event->perusahaan?->nama ?? $event->lokasi ?? '-') }}
                                    </p>
                                </div>
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Lokasi / Tempat</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->lokasi ?? '-' }}</p>
                                </div>
                                <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200">
                                    <p class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wider mb-1">PIC / Guru BK</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->pic_name ?: '-' }}</p>
                                </div>
                                <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200">
                                    <p class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wider mb-1">Nomor WhatsApp PIC</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $event->pic_whatsapp ?: '-' }}</p>
                                </div>
                            </div>

                        @else
                            {{-- ════ ALUR 2: KUNJUNGAN MANDIRI (INPUT MANUAL) ════ --}}
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                                    Informasi Instansi
                                </h3>
                            </div>

                            {{-- Tab Sekolah / Perusahaan --}}
                            <div class="flex gap-2 mb-4">
                                <button type="button"
                                    @click="jenisTab = 'Sekolah'"
                                    :class="jenisTab === 'Sekolah' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold transition cursor-pointer">
                                    Sekolah (SMA/SMK)
                                </button>
                                <button type="button"
                                    @click="jenisTab = 'Perusahaan'"
                                    :class="jenisTab === 'Perusahaan' ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold transition cursor-pointer">
                                    Corporate
                                </button>
                            </div>

                            <input type="hidden" name="jenis" :value="jenisTab">

                            {{-- Sekolah fields --}}
                            <div x-show="jenisTab === 'Sekolah'" x-cloak class="space-y-4">
                                <div 
                                    x-on:switch-manual.stop="if ($event.detail.name === 'school_source_select') { 
                                        modeManualSekolah = true; 
                                        sekolah_manual = $event.detail.search; 
                                        nama_institusi = $event.detail.search; 
                                        selectedSchoolSource = ''; 
                                        prospek_id = ''; 
                                        sekolah_id = ''; 
                                        $nextTick(function() { 
                                            const inp = $el.querySelector('input[name=sekolah_manual]'); 
                                            if(inp) { inp.value = $event.detail.search; inp.focus(); } 
                                        }); 
                                    }" 
                                    class="space-y-2"
                                >
                                    <div class="flex items-center justify-between mb-1.5 h-6">
                                        <template x-if="!modeManualSekolah">
                                            <div class="flex items-center justify-between w-full">
                                                <label class="block text-xs font-semibold text-slate-700">Pilih Nama Sekolah (Sumber: Prospek / Database) <span class="text-rose-500">*</span></label>
                                                <button 
                                                    type="button" 
                                                    @click="modeManualSekolah = true; selectedSchoolSource = ''; prospek_id = ''; sekolah_id = ''; const sel = $el.closest('.space-y-2').querySelector('input[name=school_source_select]'); if(sel) sel.value = '';" 
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200/60 transition cursor-pointer group shadow-2xs"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-blue-500 group-hover:text-blue-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                    <span>Input Manual</span>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="modeManualSekolah">
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-2">
                                                    <label class="block text-xs font-semibold text-slate-700">Nama Sekolah <span class="text-rose-500">*</span></label>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                        Manual
                                                    </span>
                                                </div>
                                                <button 
                                                    type="button" 
                                                    @click="modeManualSekolah = false; sekolah_manual = '';" 
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition cursor-pointer shadow-2xs"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    <span>Cari Database / Prospek</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <div x-show="!modeManualSekolah" x-on:change="if ($event.detail.name === 'school_source_select') handleSchoolChange($event.detail.value)">
                                        <x-searchable-select 
                                            name="school_source_select" 
                                            :options="$schoolOptions" 
                                            placeholder="-- Cari & Pilih Nama Sekolah dari Prospek / Database --" 
                                        />
                                    </div>

                                    <div x-show="modeManualSekolah" style="display: none;" class="space-y-2">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-500">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <input 
                                                type="text" 
                                                name="sekolah_manual" 
                                                x-model="sekolah_manual" 
                                                @input="nama_institusi = $event.target.value"
                                                placeholder="Ketik nama sekolah manual (misal: SMAN 1 Cirebon)..." 
                                                class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-blue-200 bg-blue-50/20 text-slate-800 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-2xs"
                                            >
                                        </div>
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/70 border border-amber-200/50 text-[11px] text-amber-800 font-medium">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Sekolah baru akan otomatis tersimpan ke master data.</span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="prospek_id" :value="prospek_id">
                                    <input type="hidden" name="sekolah_id" :value="modeManualSekolah ? '' : sekolah_id">
                                    <input type="hidden" name="nama_institusi" :value="modeManualSekolah ? sekolah_manual : nama_institusi">
                                    <p class="text-[11px] text-slate-500 mt-1" x-show="!modeManualSekolah">Memilih sekolah otomatis memuat data PIC, no. WA, dan nama instansi.</p>
                                </div>
                            </div>

                            {{-- Perusahaan fields --}}
                            <div x-show="jenisTab === 'Perusahaan'" x-cloak class="space-y-4">
                                <div 
                                    x-on:switch-manual.stop="if ($event.detail.name === 'perusahaan_select') { 
                                        modeManualCorp = true; 
                                        perusahaan_manual = $event.detail.search; 
                                        nama_institusi = $event.detail.search; 
                                        selectedPerusahaanId = ''; 
                                        $nextTick(function() { 
                                            const inp = $el.querySelector('input[name=perusahaan_manual]'); 
                                            if(inp) { inp.value = $event.detail.search; inp.focus(); } 
                                        }); 
                                    }" 
                                    class="space-y-2"
                                >
                                    <div class="flex items-center justify-between mb-1.5 h-6">
                                        <template x-if="!modeManualCorp">
                                            <div class="flex items-center justify-between w-full">
                                                <label class="block text-xs font-semibold text-slate-700">Pilih Perusahaan <span class="text-rose-500">*</span></label>
                                                <button 
                                                    type="button" 
                                                    @click="modeManualCorp = true; selectedPerusahaanId = ''; const sel = $el.closest('.space-y-2').querySelector('input[name=perusahaan_select]'); if(sel) sel.value = '';" 
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200/60 transition cursor-pointer group shadow-2xs"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-purple-500 group-hover:text-purple-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                    <span>Input Manual</span>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="modeManualCorp">
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-2">
                                                    <label class="block text-xs font-semibold text-slate-700">Nama Perusahaan <span class="text-rose-500">*</span></label>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                        Manual
                                                    </span>
                                                </div>
                                                <button 
                                                    type="button" 
                                                    @click="modeManualCorp = false; perusahaan_manual = '';" 
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition cursor-pointer shadow-2xs"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    <span>Cari Database Perusahaan</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <div x-show="!modeManualCorp" x-on:change="if ($event.detail.name === 'perusahaan_select') handlePerusahaanChange($event.detail.value)">
                                        <x-searchable-select 
                                            name="perusahaan_select" 
                                            :options="$perusahaanOptions" 
                                            placeholder="-- Cari & Pilih Nama Perusahaan dari Database --" 
                                        />
                                    </div>

                                    <div x-show="modeManualCorp" style="display: none;" class="space-y-2">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-500">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <input 
                                                type="text" 
                                                name="perusahaan_manual" 
                                                x-model="perusahaan_manual" 
                                                @input="nama_institusi = $event.target.value"
                                                placeholder="Ketik nama perusahaan manual (misal: PT Telkom Indonesia)..." 
                                                class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-purple-200 bg-purple-50/20 text-slate-800 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition shadow-2xs"
                                            >
                                        </div>
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/70 border border-amber-200/50 text-[11px] text-amber-800 font-medium">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Perusahaan baru akan otomatis tersimpan ke master data.</span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="perusahaan_id" :value="modeManualCorp ? '' : selectedPerusahaanId">
                                    <p class="text-[11px] text-slate-500 mt-1" x-show="!modeManualCorp">Memilih perusahaan otomatis mengisi data HRD / PIC di bawah.</p>
                                </div>
                            </div>

                            {{-- Alamat --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Alamat</label>
                                <input type="text" name="alamat" value="{{ old('alamat') }}"
                                    placeholder="Jl. contoh no. 1, Kota"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition">
                            </div>

                            {{-- PIC --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nama PIC / Guru BK <span class="text-rose-500">*</span></label>
                                    <input type="text" name="pic_name" x-model="pic_name" required
                                        placeholder="Contoh: Budi Santoso"
                                        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('pic_name') border-rose-300 @enderror">
                                    @error('pic_name')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">No. WhatsApp PIC <span class="text-rose-500">*</span></label>
                                    <input type="text" name="pic_whatsapp" x-model="pic_whatsapp" required
                                        placeholder="08123456789"
                                        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('pic_whatsapp') border-rose-300 @enderror">
                                    @error('pic_whatsapp')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- ─── BLOK B: WAKTU & PROGRAM STUDI ──────────────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                            Waktu & Program Studi
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                                <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('tanggal') border-rose-300 @enderror">
                                @error('tanggal')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Waktu Kunjungan <span class="text-rose-500">*</span></label>
                                <input type="time" name="waktu" value="{{ old('waktu', date('H:i')) }}" required
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition @error('waktu') border-rose-300 @enderror">
                                @error('waktu')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="space-y-3 pt-2 border-t border-slate-100">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                        <span>Program Studi yang Dipromosikan</span>
                                        <span class="text-[10px] text-blue-700 bg-blue-100 font-bold px-2 py-0.5 rounded-full">Bisa Pilih Beberapa</span>
                                    </label>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Centang satu atau beberapa program studi yang dipromosikan / diminati saat kunjungan ini.</p>
                                </div>
                                <div class="flex items-center gap-2 text-[11px]">
                                    <button type="button" @click="$el.closest('.space-y-3').querySelectorAll('input[name=\'prodi_ids[]\']').forEach(cb => cb.checked = true)" class="text-blue-600 hover:text-blue-800 font-semibold cursor-pointer">Pilih Semua</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click="$el.closest('.space-y-3').querySelectorAll('input[name=\'prodi_ids[]\']').forEach(cb => cb.checked = false)" class="text-slate-500 hover:text-slate-700 font-semibold cursor-pointer">Hapus Pilihan</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                @php
                                    $oldProdiIds = (array) old('prodi_ids', isset($event) && $event->prodi_id ? [$event->prodi_id] : []);
                                @endphp
                                @foreach($prodis as $prodi)
                                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:bg-blue-50/40 hover:border-blue-300 transition cursor-pointer group select-none shadow-2xs">
                                        <input 
                                            type="checkbox" 
                                            name="prodi_ids[]" 
                                            value="{{ $prodi->id }}" 
                                            {{ in_array($prodi->id, $oldProdiIds) ? 'checked' : '' }}
                                            class="w-4 h-4 mt-0.5 text-blue-600 rounded border-slate-300 focus:ring-blue-500 shrink-0"
                                        >
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-slate-800 group-hover:text-blue-900 leading-tight">
                                                {{ $prodi->nama }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-medium mt-0.5 flex items-center gap-1.5">
                                                <span class="inline-block px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-semibold text-[9px]">{{ $prodi->jenjang }}</span>
                                                <span class="truncate">{{ $prodi->fakultas }}</span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- ─── BLOK C: HASIL KUNJUNGAN (SEKOLAH) ──────────────────── --}}
                    <div x-show="jenisTab === 'Sekolah'" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                            Hasil Kunjungan — Sekolah
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Potensi Mahasiswa / Pendaftar Baru</label>
                                <input type="text" name="potensi_mahasiswa" value="{{ old('potensi_mahasiswa') }}"
                                    placeholder="Contoh: 30-50 siswa"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Detail Potensi Mahasiswa</label>
                            <textarea name="detail_potensi_mahasiswa" rows="3"
                                placeholder="Deskripsikan potensi lebih detail: jurusan yang diminati, antusiasme siswa, dll."
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">{{ old('detail_potensi_mahasiswa') }}</textarea>
                        </div>

                        {{-- Opsi Kesediaan Training AI & Mandatori Dosen Pemateri --}}
                        <div class="pt-3 border-t border-slate-100 space-y-3">
                            <label class="flex items-center gap-3 p-3.5 rounded-xl bg-purple-50/70 border border-purple-200/80 cursor-pointer hover:bg-purple-100/50 transition">
                                <input type="checkbox" name="kesediaan_training_ai" value="1"
                                    x-model="isTraining"
                                    class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                                <div>
                                    <span class="text-xs font-bold text-slate-900">Sekolah Bersedia Diadakan Training AI / Miniclass</span>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Centang jika sekolah meminta/menyetujui pelatihan AI. Wajib menentukan Dosen Pemateri.</p>
                                </div>
                            </label>

                            <div x-show="isTraining" x-cloak class="p-4 bg-amber-50/80 rounded-xl border border-amber-200 space-y-3">
                                <div class="flex items-center gap-2 text-xs font-bold text-amber-900">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Penugasan Dosen Pemateri (Wajib untuk Kegiatan Training) <span class="text-rose-600">*</span></span>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Dosen / Pemateri <span class="text-rose-600">*</span></label>
                                    <input type="text" name="dosen_pemateri" value="{{ old('dosen_pemateri', $event->dosen_pemateri ?? '') }}"
                                        :required="isTraining"
                                        placeholder="Contoh: Dr. Ir. H. Ahmad, M.T."
                                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 @error('dosen_pemateri') border-rose-300 @enderror">
                                    @error('dosen_pemateri')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── BLOK D: HASIL KUNJUNGAN (CORPORATE) ────────────────── --}}
                    <div x-show="jenisTab === 'Perusahaan'" x-cloak class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                            Hasil Kunjungan — Corporate
                        </h3>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bidang Usaha</label>
                            <input type="text" name="bidang_usaha" value="{{ old('bidang_usaha') }}"
                                placeholder="Contoh: Teknologi Informasi"
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Potensi S1</label>
                                <input type="text" name="potensi_s1" value="{{ old('potensi_s1') }}"
                                    placeholder="Jumlah / estimasi"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Potensi S2</label>
                                <input type="text" name="potensi_s2" value="{{ old('potensi_s2') }}"
                                    placeholder="Jumlah / estimasi"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Program CSR</label>
                                <input type="text" name="potensi_csr" value="{{ old('potensi_csr') }}"
                                    placeholder="Bentuk kerjasama"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition">
                            </div>
                        </div>
                    </div>

                    {{-- ─── BLOK E: CATATAN / HASIL PEMBICARAAN ────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                            Catatan & Hasil Pembicaraan
                        </h3>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Catatan Kunjungan</label>
                            <textarea name="catatan" rows="5"
                                placeholder="Tuliskan hasil diskusi, kesepakatan, poin penting yang dibahas, dan tindak lanjut yang diperlukan..."
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 transition @error('catatan') border-rose-300 @enderror">{{ old('catatan') }}</textarea>
                            @error('catatan')<p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>@enderror
                        </div>

                    </div>

                </div>

                {{-- ── KOLOM KANAN: Foto + GPS + Submit ──────────────────────────── --}}
                <div class="space-y-6">

                    {{-- ─── BLOK F: FOTO DOKUMENTASI ───────────────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                            Dokumentasi Foto <span class="text-rose-500">*</span>
                        </h3>

                        <div
                            @click="$refs.fotoInput.click()"
                            class="relative w-full h-48 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition group"
                        >
                            <template x-if="!fotoPreview">
                                <div class="flex flex-col items-center justify-center h-full gap-2 text-slate-400 group-hover:text-blue-500 transition">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <p class="text-xs font-semibold">Klik untuk upload foto</p>
                                    <p class="text-[11px]">JPG, PNG, WEBP — Max 5MB</p>
                                </div>
                            </template>
                            <template x-if="fotoPreview">
                                <img :src="fotoPreview" class="w-full h-full object-cover">
                            </template>
                        </div>

                        <input
                            type="file"
                            name="foto"
                            accept="image/*"
                            x-ref="fotoInput"
                            class="hidden"
                            required
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = e => fotoPreview = e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            "
                        >
                        @error('foto')<p class="text-rose-500 text-[11px] mt-2">{{ $message }}</p>@enderror
                    </div>

                    {{-- ─── BLOK G: LOKASI GPS ──────────────────────────────────── --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                            Verifikasi Lokasi GPS <span class="text-rose-500">*</span>
                        </h3>

                        <button
                            type="button"
                            @click="captureGeo()"
                            :disabled="geoStatus === 'loading'"
                            class="w-full px-4 py-2.5 rounded-xl text-sm font-semibold transition cursor-pointer flex items-center justify-center gap-2"
                            :class="{
                                'bg-blue-600 hover:bg-blue-700 text-white': geoStatus === 'idle',
                                'bg-slate-300 text-slate-500 cursor-not-allowed': geoStatus === 'loading',
                                'bg-emerald-600 hover:bg-emerald-700 text-white': geoStatus === 'ok',
                                'bg-rose-600 hover:bg-rose-700 text-white': geoStatus === 'error',
                            }"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span x-text="{
                                idle: 'Tangkap Lokasi GPS',
                                loading: 'Mendeteksi lokasi...',
                                ok: '✓ Lokasi Berhasil Ditangkap',
                                error: 'Gagal — Coba Lagi'
                            }[geoStatus]"></span>
                        </button>

                        <div x-show="geoStatus === 'ok'" x-cloak class="mt-3 p-3 bg-emerald-50 rounded-xl border border-emerald-100 text-[11px] text-emerald-700 space-y-1">
                            <p><span class="font-semibold">Lat:</span> <span x-text="lat"></span></p>
                            <p><span class="font-semibold">Lng:</span> <span x-text="lng"></span></p>
                        </div>

                        <div x-show="geoStatus === 'error'" x-cloak class="mt-3 p-3 bg-rose-50 rounded-xl border border-rose-100 text-[11px] text-rose-700">
                            Tidak dapat mendeteksi lokasi. Pastikan GPS aktif dan izin lokasi diizinkan di browser.
                        </div>

                        <p class="text-[11px] text-slate-400 mt-3">
                            Lokasi GPS digunakan untuk memverifikasi bahwa kunjungan dilakukan di lokasi yang benar.
                        </p>
                    </div>



                    {{-- ─── BLOK I: SUBMIT ──────────────────────────────────────── --}}
                    <div class="flex flex-col gap-3">
                        <button
                            type="submit"
                            class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition shadow-sm cursor-pointer flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Simpan Laporan Kunjungan
                        </button>
                        <a href="{{ route('sales.kunjungan.index') }}"
                            class="w-full px-6 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-sm font-semibold transition text-center cursor-pointer">
                            Batal
                        </a>
                    </div>

                </div>
            </div>

        </form>

    </div>

    {{-- Alpine: sync nama_institusi dari dua input --}}
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            // Unify nama_institusi from Sekolah/Perusahaan free-text inputs
            const jenisInput = this.querySelector('input[name="jenis"]') || this.querySelector('select[name="jenis"]');
            const jenis = jenisInput ? jenisInput.value : '';

            let namaInput = null;
            if (jenis === 'Sekolah') {
                namaInput = this.querySelector('input[name="nama_institusi_sekolah"]');
            } else {
                namaInput = this.querySelector('input[name="nama_institusi_perusahaan"]');
            }

            if (namaInput && namaInput.value.trim()) {
                // Create hidden field to pass nama_institusi
                let hidden = this.querySelector('input[name="nama_institusi"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'nama_institusi';
                    this.appendChild(hidden);
                }
                hidden.value = namaInput.value.trim();
            }
        });
    </script>

</x-app-layout>
