<x-app-layout title="Assignment Event">
<div x-data="{ 
    modalAssignSales: false, 
    selectedEvent: null
}">

    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Assignment Event</h1>
            <p class="text-sm text-slate-500 mt-1">Tugaskan tim Sales Anda untuk event yang dikelola EO.</p>
        </div>
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
                        <th class="px-5 py-4">Tim Sales (Ditugaskan Anda)</th>
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
                                    @foreach($event->sales as $sales)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $sales->name }}
                                        </span>
                                    @endforeach
                                    @if($event->sales->isEmpty())
                                        <span class="text-slate-400 italic text-xs">Belum ada Sales ditugaskan</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <button type="button" @click="selectedEvent = {{ json_encode($event) }}; selectedEvent.sales_ids = {{ json_encode($event->sales->pluck('id')) }}; modalAssignSales = true" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 text-xs font-semibold rounded-lg hover:bg-blue-100 transition border border-blue-100" title="Atur Assignment">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    Atur Sales
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="font-medium">Belum ada event yang ditugaskan ke Anda.</span>
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

    <!-- Modal Assign Sales -->
    <div x-show="modalAssignSales" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-0">
        <div x-show="modalAssignSales" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="modalAssignSales = false"></div>
        <div x-show="modalAssignSales" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg transform transition-all">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-900">Penugasan Tim Sales</h3>
                <button @click="modalAssignSales = false" class="text-slate-400 hover:text-slate-600 transition p-1 rounded-lg hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="px-6 pt-4 pb-2">
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex gap-3">
                    <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div class="text-xs text-blue-800 font-medium">
                        <span class="block font-bold mb-1" x-text="selectedEvent?.name"></span>
                        <span x-text="(selectedEvent?.tanggal ? selectedEvent.tanggal.substring(0,10) : '') + ' | ' + (selectedEvent?.waktu_mulai ? selectedEvent.waktu_mulai.substring(11,16) : '') + ' - ' + (selectedEvent?.waktu_selesai ? selectedEvent.waktu_selesai.substring(11,16) : '')"></span>
                        <span class="block mt-1" x-text="selectedEvent?.lokasi"></span>
                    </div>
                </div>
            </div>

            <form :action="`/spv/events/${selectedEvent?.id}/assign`" method="POST" class="p-6 pt-2">
                @csrf
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Sales dari Tim Anda</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 max-h-60 overflow-y-auto">
                        @forelse($teamSales as $sales)
                            <label class="flex items-center gap-3 p-2 hover:bg-slate-100 rounded-lg cursor-pointer transition">
                                <input type="checkbox" name="sales[]" value="{{ $sales->id }}" :checked="selectedEvent?.sales_ids?.includes({{ $sales->id }})" class="rounded text-blue-600 focus:ring-blue-500 border-slate-300 bg-white shadow-sm w-4 h-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-slate-800">{{ $sales->name }}</span>
                                    <span class="text-[10px] text-slate-500">{{ $sales->wilayah ? $sales->wilayah->nama : '' }}</span>
                                </div>
                            </label>
                        @empty
                            <div class="text-center text-sm text-slate-500 p-2">Belum ada Sales dalam tim Anda.</div>
                        @endforelse
                    </div>
                    <p class="text-[10px] text-slate-500 mt-2">Sistem akan menolak penugasan jika terdapat jadwal bentrok atau jeda antar-event kurang dari 2 jam bagi Sales yang dipilih.</p>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="modalAssignSales = false" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm transition">Simpan Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
