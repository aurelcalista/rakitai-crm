<x-app-layout title="Kelola Event">
<div x-data="{ 
    modalTambahEvent: false, 
    modalEditEvent: false,
    modalDetailEvent: false,
    selectedEvent: null,
    detailEvent: null,
    copiedQr: false,

    formatTime(val) {
        if (!val) return '-';
        if (typeof val === 'string' && val.includes('T')) return val.split('T')[1].substring(0, 5);
        if (typeof val === 'string' && val.includes(' ')) return val.split(' ')[1].substring(0, 5);
        return val.substring(0, 5);
    },
    formatDate(val) {
        if (!val) return '-';
        if (typeof val === 'string' && val.length >= 10) return val.substring(0, 10);
        return val;
    },
    copyQr(token) {
        if (!token) return;
        navigator.clipboard.writeText(token);
        this.copiedQr = true;
        setTimeout(() => this.copiedQr = false, 2000);
    },
    getSalesForSpv(spvId) {
        if (!this.detailEvent || !this.detailEvent.sales) return [];
        return this.detailEvent.sales.filter(s => s.pivot && s.pivot.assigned_by_spv_id == spvId);
    }
}">

    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kelola Event</h1>
            <p class="text-sm text-slate-500 mt-1">Buat dan kelola jadwal event beserta penugasan SPV.</p>
        </div>
        <button @click="modalTambahEvent = true" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition shadow-sm ring-1 ring-blue-700/50">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Buat Event Baru
        </button>
    </div>

    <!-- Data Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-5 py-4 w-12 text-center">No</th>
                        <th class="px-5 py-4">Event</th>
                        <th class="px-5 py-4">Waktu & Lokasi</th>
                        <th class="px-5 py-4">Anggota Ditugaskan</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($events as $index => $event)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-5 py-4 text-center font-medium text-slate-500">{{ $events->firstItem() + $index }}</td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900">{{ $event->name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $event->type ? $event->type->nama : '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-slate-900 font-medium">{{ \Carbon\Carbon::parse($event->tanggal)->translatedFormat('d M Y') }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ \Carbon\Carbon::parse($event->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($event->waktu_selesai)->format('H:i') }}</div>
                                <div class="text-xs text-blue-600 mt-0.5 truncate max-w-[200px]" title="{{ $event->lokasi }}">{{ $event->lokasi }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($event->spvs as $member)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0"></span>
                                            {{ $member->name }}
                                        </span>
                                    @endforeach
                                    @if($event->spvs->isEmpty())
                                        <span class="text-slate-400 italic text-xs">Belum ada anggota</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($event->status === 'Scheduled')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Terjadwal</span>
                                @elseif($event->status === 'Cancelled')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Dibatalkan</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">{{ $event->status }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <button type="button" @click='detailEvent = @json($event); modalDetailEvent = true' class="p-1.5 text-slate-400 hover:text-emerald-600 transition" title="Detail Event">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </button>
                                <button type="button" @click='selectedEvent = @json($event); selectedEvent.spv_ids = @json($event->spvs->pluck("id")); modalEditEvent = true' class="p-1.5 text-slate-400 hover:text-blue-600 transition" title="Edit Event">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </button>
                                <form action="{{ route('eo.events.destroy', $event->id) }}" method="POST" class="inline-block" data-confirm="Apakah Anda yakin ingin membatalkan dan menghapus event ini? Semua assignment terkait akan ikut terhapus.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus Event">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="font-medium">Belum ada event yang dibuat.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($events->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $events->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Tambah Event (Style Identik dengan Modal Detail) -->
    <div x-show="modalTambahEvent" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="modalTambahEvent" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" 
             @click="modalTambahEvent = false"></div>
        <div x-show="modalTambahEvent" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 scale-95 translate-y-2" 
             x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
             x-transition:leave-end="opacity-0 scale-95 translate-y-2" 
             class="relative bg-white rounded-2xl shadow-xl w-full max-w-3xl h-[90vh] max-h-[90vh] flex flex-col transform transition-all overflow-hidden border border-slate-100">
            <form action="{{ route('eo.events.store') }}" method="POST" x-data="{ isSubmitting: false }" @submit="if(isSubmitting){ $event.preventDefault(); return false; } isSubmitting = true" class="flex flex-col h-full min-h-0 overflow-hidden">
                @csrf
                <!-- Header (Identik dengan modal detail) -->
                <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80 rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 text-emerald-700 rounded-xl">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Buat Event Baru</h3>
                            <p class="text-xs text-slate-500">Lengkapi data pelaksanaan, target institusi, dan rincian akademik</p>
                        </div>
                    </div>
                    <button type="button" @click="modalTambahEvent = false" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Body (Kartu section terstruktur identik dengan modal detail) -->
                <div class="flex-1 min-h-0 p-6 overflow-y-auto space-y-5">
                    
                    <!-- Card 1: Waktu & Lokasi Pelaksanaan -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4.5 space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60">
                            <div class="p-1.5 bg-blue-100 text-blue-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Waktu & Lokasi Pelaksanaan</h4>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Event <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: Edu Expo SMA Pasundan 1">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Jenis Event <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <select name="type_id" required class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                        <option value="">-- Pilih Jenis --</option>
                                        @foreach($eventTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->nama }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Tanggal Pelaksanaan <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="tanggal" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Lokasi Event <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="lokasi" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: Aula SMA Pasundan 1 Cirebon">
                        </div>
                    </div>

                    <!-- Card 2: Informasi Target & PIC -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4.5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-emerald-100 text-emerald-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Informasi Target & PIC</h4>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Jenis Institusi
                                </label>
                                <div class="relative">
                                    <select name="jenis_institusi" class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="Sekolah">Sekolah / Universitas</option>
                                        <option value="Perusahaan">Perusahaan / Corporate</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Institusi
                                </label>
                                <input type="text" name="nama_institusi" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: SMA Negeri 1 Cirebon">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Alamat Institusi
                            </label>
                            <input type="text" name="alamat" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Alamat lengkap institusi atau instansi...">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama PIC / Kontak
                                </label>
                                <input type="text" name="pic_name" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: Bpk. Bambang, S.Pd.">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nomor WhatsApp PIC
                                </label>
                                <input type="text" name="pic_whatsapp" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: 08123456789">
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Akademik & Pemateri -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4.5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                </div>
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akademik & Pemateri</h4>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Dosen / Pemateri
                                </label>
                                <input type="text" name="dosen_pemateri" placeholder="Contoh: Dr. Ir. H. Ahmad, M.T." class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Program Studi
                                </label>
                                <div class="relative">
                                    <select name="prodi_id" class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                        <option value="">-- Pilih Prodi --</option>
                                        @foreach($prodis as $prodi)
                                            <option value="{{ $prodi->id }}">{{ $prodi->nama }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Deskripsi & Catatan -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4.5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-amber-100 text-amber-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                </div>
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Deskripsi & Catatan</h4>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                        </div>

                        <div>
                            <textarea name="deskripsi" rows="3" class="w-full p-3.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs resize-none font-medium leading-relaxed" placeholder="Catatan tambahan acara atau rincian instruksi khusus..."></textarea>
                        </div>
                    </div>

                </div>

                <!-- Footer (Identik dengan modal detail) -->
                <div class="shrink-0 px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                    <button type="button" @click="modalTambahEvent = false" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition shadow-2xs">Batal</button>
                    <button type="submit" :disabled="isSubmitting" class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl disabled:opacity-50 transition shadow-sm hover:shadow flex items-center gap-2">
                        <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Event'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Event (Style Identik dengan Modal Detail) -->
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
             class="relative bg-white rounded-2xl shadow-xl w-full max-w-3xl h-[90vh] max-h-[90vh] flex flex-col transform transition-all overflow-hidden border border-slate-100">
            <form :action="`/eo/events/${selectedEvent?.id}`" method="POST" x-data="{ isSubmitting: false }" @submit="if(isSubmitting){ $event.preventDefault(); return false; } isSubmitting = true" class="flex flex-col h-full min-h-0 overflow-hidden">
                @csrf
                @method('PUT')
                <!-- Header (Identik dengan modal detail) -->
                <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80 rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-100 text-amber-700 rounded-xl">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Edit Event</h3>
                            <p class="text-xs text-slate-500">Perbarui rincian event, lokasi, target institusi, atau akademik</p>
                        </div>
                    </div>
                    <button type="button" @click="modalEditEvent = false" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Body (Kartu section terstruktur identik dengan modal detail) -->
                <div class="flex-1 min-h-0 p-6 overflow-y-auto space-y-5">
                    
                    <!-- Card 1: Waktu & Lokasi Pelaksanaan -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4.5 space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60">
                            <div class="p-1.5 bg-blue-100 text-blue-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Waktu & Lokasi Pelaksanaan</h4>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Event <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" :value="selectedEvent?.name" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Jenis Event <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <select name="type_id" :value="selectedEvent?.type_id" required class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                        <option value="">-- Pilih Jenis --</option>
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
                                    Tanggal Pelaksanaan <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="tanggal" :value="formatDate(selectedEvent?.tanggal)" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Waktu Mulai <span class="text-rose-500">*</span>
                                </label>
                                <input type="time" name="waktu_mulai" :value="formatTime(selectedEvent?.waktu_mulai)" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Waktu Selesai <span class="text-rose-500">*</span>
                                </label>
                                <input type="time" name="waktu_selesai" :value="formatTime(selectedEvent?.waktu_selesai)" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Lokasi Event <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="lokasi" :value="selectedEvent?.lokasi" required class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                        </div>
                    </div>

                    <!-- Card 2: Informasi Target & PIC -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4.5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-emerald-100 text-emerald-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Informasi Target & PIC</h4>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Jenis Institusi
                                </label>
                                <div class="relative">
                                    <select name="jenis_institusi" :value="selectedEvent?.jenis_institusi" class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="Sekolah" :selected="selectedEvent?.jenis_institusi == 'Sekolah'">Sekolah / Universitas</option>
                                        <option value="Perusahaan" :selected="selectedEvent?.jenis_institusi == 'Perusahaan'">Perusahaan / Corporate</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Institusi
                                </label>
                                <input type="text" name="nama_institusi" :value="selectedEvent?.nama_institusi" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: SMA Negeri 1 Cirebon">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Alamat Institusi
                            </label>
                            <input type="text" name="alamat" :value="selectedEvent?.alamat" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Alamat lengkap...">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama PIC / Kontak
                                </label>
                                <input type="text" name="pic_name" :value="selectedEvent?.pic_name" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Nama PIC...">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nomor WhatsApp PIC
                                </label>
                                <input type="text" name="pic_whatsapp" :value="selectedEvent?.pic_whatsapp" class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium" placeholder="Contoh: 08123456789">
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Akademik & Pemateri -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4.5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                </div>
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akademik & Pemateri</h4>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Dosen / Pemateri
                                </label>
                                <input type="text" name="dosen_pemateri" :value="selectedEvent?.dosen_pemateri || selectedEvent?.dosen?.name" placeholder="Contoh: Dr. Ir. H. Ahmad, M.T." class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Program Studi
                                </label>
                                <div class="relative">
                                    <select name="prodi_id" :value="selectedEvent?.prodi_id" class="w-full px-3.5 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs appearance-none font-medium">
                                        <option value="">-- Pilih Prodi --</option>
                                        @foreach($prodis as $prodi)
                                            <option value="{{ $prodi->id }}" :selected="selectedEvent?.prodi_id == {{ $prodi->id }}">{{ $prodi->nama }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Deskripsi & Catatan -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4.5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-amber-100 text-amber-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                </div>
                                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Deskripsi & Catatan</h4>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium">Opsional</span>
                        </div>

                        <div>
                            <textarea name="deskripsi" :value="selectedEvent?.deskripsi" rows="3" class="w-full p-3.5 text-sm bg-white border border-slate-300 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 hover:border-slate-400 transition shadow-2xs resize-none font-medium leading-relaxed" placeholder="Catatan tambahan acara..."></textarea>
                        </div>
                    </div>

                </div>

                <!-- Footer (Identik dengan modal detail) -->
                <div class="shrink-0 px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                    <button type="button" @click="modalEditEvent = false" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition shadow-2xs">Batal</button>
                    <button type="submit" :disabled="isSubmitting" class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl disabled:opacity-50 transition shadow-sm hover:shadow flex items-center gap-2">
                        <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

<!-- Modal Detail Event -->
<div x-show="modalDetailEvent" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-0">
    <div x-show="modalDetailEvent" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="modalDetailEvent = false"></div>
    <div x-show="modalDetailEvent" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative bg-white rounded-2xl shadow-xl w-full max-w-3xl transform transition-all">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 text-emerald-700 rounded-xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Detail Event</h3>
                    <p class="text-xs text-slate-500">Informasi lengkap event dan tim penugasan</p>
                </div>
            </div>
            <button @click="modalDetailEvent = false" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-200">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(100vh-10rem)] space-y-6">
            <template x-if="detailEvent">
                <div class="space-y-6">
                    <!-- Title & Header Badges -->
                    <div class="bg-gradient-to-r from-slate-50 to-blue-50/30 p-4 rounded-xl border border-slate-200/80">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-extrabold text-slate-900" x-text="detailEvent.name"></h2>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-blue-100 text-blue-800" x-text="detailEvent.type ? detailEvent.type.nama : 'Umum'"></span>
                                    
                                    <!-- Status Pill -->
                                    <template x-if="detailEvent.status === 'Scheduled'">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Terjadwal</span>
                                    </template>
                                    <template x-if="detailEvent.status === 'Cancelled'">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Dibatalkan</span>
                                    </template>
                                    <template x-if="detailEvent.status !== 'Scheduled' && detailEvent.status !== 'Cancelled'">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200" x-text="detailEvent.status"></span>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- QR Code Token Badge -->
                            <template x-if="detailEvent.qr_code">
                                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-xs">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    <span class="text-xs font-mono font-bold text-slate-700" x-text="detailEvent.qr_code"></span>
                                    <button type="button" @click="copyQr(detailEvent.qr_code)" class="text-xs text-blue-600 hover:text-blue-800 font-medium ml-1">
                                        <span x-text="copiedQr ? 'Tersalin!' : 'Salin'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Grid Info Waktu & Lokasi -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Waktu & Lokasi Pelaksanaan</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 flex items-start gap-3">
                                <div class="p-2 bg-white text-blue-600 rounded-lg shadow-xs shrink-0 border border-slate-200">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase">Tanggal</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="formatDate(detailEvent.tanggal)"></p>
                                </div>
                            </div>

                            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 flex items-start gap-3">
                                <div class="p-2 bg-white text-blue-600 rounded-lg shadow-xs shrink-0 border border-slate-200">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase">Jam Pelaksanaan</p>
                                    <p class="text-sm font-bold text-slate-900">
                                        <span x-text="formatTime(detailEvent.waktu_mulai)"></span> - <span x-text="formatTime(detailEvent.waktu_selesai)"></span>
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 flex items-start gap-3">
                                <div class="p-2 bg-white text-blue-600 rounded-lg shadow-xs shrink-0 border border-slate-200">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase">Lokasi</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="detailEvent.lokasi || '-'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Information Target / Institusi & PIC -->
                    <template x-if="detailEvent.jenis_institusi || detailEvent.nama_institusi || detailEvent.pic_name">
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Informasi Target & PIC</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
                                <div>
                                    <p class="text-xs text-slate-500 font-semibold mb-0.5">Institusi / Sekolah</p>
                                    <p class="text-sm font-bold text-slate-900">
                                        <span x-text="detailEvent.nama_institusi || (detailEvent.sekolah ? detailEvent.sekolah.nama : (detailEvent.perusahaan ? detailEvent.perusahaan.nama : '-'))"></span>
                                        <span class="text-xs font-normal text-slate-500" x-text="detailEvent.jenis_institusi ? `(${detailEvent.jenis_institusi})` : ''"></span>
                                    </p>
                                    <p class="text-xs text-slate-600 mt-1" x-show="detailEvent.alamat" x-text="detailEvent.alamat"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-semibold mb-0.5">PIC / Penanggung Jawab</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="detailEvent.pic_name || '-'"></p>
                                    <template x-if="detailEvent.pic_whatsapp">
                                        <div class="mt-1 flex items-center gap-1.5 text-xs text-emerald-600 font-medium">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984 0 1.758.459 3.474 1.33 4.982l-1.413 5.161 5.283-1.386a9.937 9.937 0 004.787 1.229h.005c5.507 0 9.99-4.478 9.99-9.984s-4.483-9.986-9.992-9.986zm5.836 14.129c-.247.694-1.439 1.325-1.986 1.388-.507.058-1.157.086-3.714-.97-3.08-1.27-5.068-4.398-5.221-4.602-.153-.204-1.248-1.66-1.248-3.166 0-1.506.791-2.247 1.073-2.553.282-.306.613-.383.817-.383.204 0 .408.002.587.01.191.008.447-.072.7.536.26.623.886 2.164.963 2.322.077.158.128.343.025.548-.102.204-.153.332-.306.51-.153.179-.323.399-.46.536-.153.153-.313.319-.134.626.179.307.795 1.313 1.706 2.125 1.173 1.045 2.162 1.37 2.469 1.523.307.153.485.128.664-.077.179-.204.767-.894.971-1.2.204-.306.408-.255.689-.153.281.102 1.787.843 2.093.996.306.153.51.23.587.357.077.128.077.744-.17 1.438z"/></svg>
                                            <a :href="`https://wa.me/${detailEvent.pic_whatsapp.replace(/[^0-9]/g, '')}`" target="_blank" class="hover:underline" x-text="detailEvent.pic_whatsapp"></a>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Information Dosen / Pemateri / Prodi -->
                    <template x-if="detailEvent.dosen || detailEvent.dosen_pemateri || detailEvent.prodi">
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Akademik & Pemateri</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
                                <div>
                                    <p class="text-xs text-slate-500 font-semibold mb-0.5">Dosen Pemateri</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="detailEvent.dosen ? detailEvent.dosen.name : (detailEvent.dosen_pemateri || '-')"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-semibold mb-0.5">Program Studi</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="detailEvent.prodi ? detailEvent.prodi.nama : '-'"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Deskripsi Event -->
                    <template x-if="detailEvent.deskripsi">
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Deskripsi / Catatan</h4>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed" x-text="detailEvent.deskripsi"></p>
                            </div>
                        </div>
                    </template>

                    <!-- Assignments - Anggota Tim Ditugaskan -->
                    <div class="pt-4 border-t border-slate-200">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Anggota Tim Ditugaskan</h4>

                        <template x-if="detailEvent.spvs && detailEvent.spvs.length > 0">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="member in detailEvent.spvs" :key="member.id">
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-800 shadow-xs">
                                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0" x-text="member.name.charAt(0).toUpperCase()"></span>
                                        <span x-text="member.name"></span>
                                    </span>
                                </template>
                            </div>
                        </template>

                        <template x-if="!detailEvent.spvs || detailEvent.spvs.length === 0">
                            <div class="text-center p-6 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                                <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="text-sm font-medium text-slate-500">Belum ada anggota yang ditugaskan.</p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
        <div class="flex justify-between items-center gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl">
            <template x-if="detailEvent">
                <button type="button" @click="modalDetailEvent = false; selectedEvent = detailEvent; selectedEvent.spv_ids = (detailEvent.spvs || []).map(s => s.id); modalEditEvent = true" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition border border-blue-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    Edit Event
                </button>
            </template>
            <button type="button" @click="modalDetailEvent = false" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition shadow-xs">Tutup</button>
        </div>
    </div>
</div>
</x-app-layout>
