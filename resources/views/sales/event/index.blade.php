@php $pageTitle = 'Jadwal Event'; @endphp

<x-app-layout :title="'Jadwal Event - CRM UCIC'">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Jadwal Event</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Daftar event yang ditugaskan ke Anda oleh Event Organizer.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-data="{ confirmModal: false, selectedEvent: null }">
            @forelse($events as $e)
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold border
                                @if($e->status === 'Selesai') bg-emerald-50 text-emerald-700 border-emerald-200
                                @elseif($e->status === 'Sedang Berjalan') bg-blue-50 text-blue-700 border-blue-200
                                @elseif($e->status === 'Rencana' || $e->status === 'Scheduled') bg-amber-50 text-amber-700 border-amber-200
                                @else bg-rose-50 text-rose-700 border-rose-200 @endif">
                                {{ $e->status }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ \Carbon\Carbon::parse($e->tanggal_mulai)->format('d M Y H:i') }}
                            </span>
                        </div>

                        <h3 class="text-lg font-bold text-slate-900 mb-3">{{ $e->nama }}</h3>

                        <div class="space-y-2 mb-4 text-sm text-slate-600">
                            {{-- Lokasi --}}
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span>{{ $e->lokasi }}</span>
                            </div>
                            {{-- Instansi --}}
                            @if($e->nama_institusi || $e->sekolah_id || $e->perusahaan_id)
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <span class="font-medium">
                                    {{ $e->nama_institusi ?: ($e->sekolah?->nama ?? $e->perusahaan?->nama ?? '-') }}
                                    @if($e->jenis_institusi)
                                        <span class="text-xs text-slate-400">({{ $e->jenis_institusi }})</span>
                                    @endif
                                </span>
                            </div>
                            @endif
                            {{-- PIC --}}
                            @if($e->pic_name)
                            <div class="flex items-start gap-2 p-2 bg-emerald-50 rounded-lg border border-emerald-100">
                                <svg class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                <div class="text-xs">
                                    <span class="text-emerald-700 font-semibold">{{ $e->pic_name }}</span>
                                    @if($e->pic_whatsapp)
                                        <span class="text-emerald-500 ml-1">· {{ $e->pic_whatsapp }}</span>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    @php
                        $eventSales = DB::table('event_sales')
                            ->where('event_id', $e->id)
                            ->where('sales_id', auth()->id())
                            ->first();
                        $isStarted = \Carbon\Carbon::now('Asia/Jakarta')->greaterThanOrEqualTo(\Carbon\Carbon::parse($e->tanggal_mulai, 'Asia/Jakarta'));
                    @endphp

                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                        @if($eventSales && $eventSales->kehadiran)
                            <div class="w-full text-center px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-sm font-semibold border border-emerald-200">
                                Kehadiran Terkonfirmasi: {{ $eventSales->kehadiran }}
                            </div>

                            @if($eventSales->kehadiran === 'Hadir')
                                {{--
                                    ALUR 1: Kunjungan dari Event.
                                    Link langsung ke form create dengan event_id query param.
                                    Data instansi/PIC otomatis terisi dari Event di VisitController::create().
                                --}}
                                @if($isStarted)
                                    <a
                                        href="{{ route('sales.kunjungan.create', ['event_id' => $e->id]) }}"
                                        class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition shadow-sm flex items-center justify-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                        <span>Tambah Laporan Kunjungan</span>
                                    </a>
                                @else
                                    <button
                                        type="button"
                                        disabled
                                        class="w-full px-4 py-2.5 bg-slate-200 text-slate-400 rounded-xl text-sm font-semibold cursor-not-allowed flex items-center justify-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                        <span>Tambah Laporan Kunjungan</span>
                                    </button>
                                    <p class="text-[10px] text-slate-400 text-center">
                                        Laporan dapat dibuat mulai: {{ \Carbon\Carbon::parse($e->tanggal_mulai)->format('d M Y H:i') }}
                                    </p>
                                @endif
                            @endif
                        @else
                            <button
                                @click="selectedEvent = {{ $e->id }}; confirmModal = true"
                                class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition cursor-pointer shadow-sm"
                            >
                                Konfirmasi Kehadiran
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 bg-white rounded-2xl border border-slate-200 border-dashed flex flex-col items-center justify-center text-slate-400">
                    <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <p>Belum ada event yang ditugaskan ke Anda.</p>
                </div>
            @endforelse

            {{-- Modal: Konfirmasi Kehadiran --}}
            <div x-show="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0" x-cloak>
                <div x-show="confirmModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="confirmModal = false"></div>

                <div x-show="confirmModal" class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg transform transition-all p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-bold text-slate-900">Konfirmasi Kehadiran Event</h3>
                        <button @click="confirmModal = false" class="text-slate-400 hover:text-slate-600 transition p-1 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form :action="`/sales/events/${selectedEvent}/confirm`" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Status Kehadiran <span class="text-rose-500">*</span></label>
                                <select name="kehadiran" required class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition">
                                    <option value="Hadir">Hadir</option>
                                    <option value="Tidak Hadir">Tidak Hadir</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Catatan (Opsional)</label>
                                <textarea name="catatan" rows="3" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 transition" placeholder="Tuliskan alasan jika tidak hadir..."></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="confirmModal = false" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">Batal</button>
                            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm transition cursor-pointer">Konfirmasi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
