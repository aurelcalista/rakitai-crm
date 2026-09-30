@php $pageTitle = 'Potensi Wilayah'; @endphp

<x-app-layout :title="'Potensi Wilayah - CRM UCIC'">

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Potensi Wilayah</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau sebaran prospek dan closing berdasarkan struktur wilayah dan penugasan Sales.</p>
            </div>
        </div>

        <!-- Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4 w-1/3">Kota / Kabupaten</th>
                            <th class="py-3.5 px-4 w-1/3">Kecamatan</th>
                            <th class="py-3.5 px-4">Sales Ditugaskan</th>
                            <th class="py-3.5 px-4 text-center">Jumlah Prospek</th>
                            <th class="py-3.5 px-4 text-center">Total Closing</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($wilayahs as $parent)
                            @php
                                $parentProspek = $wilayahStats[$parent->id]['prospek'] ?? 0;
                                $parentClosing = $wilayahStats[$parent->id]['closing'] ?? 0;
                            @endphp
                            <!-- Parent Row (Kota/Kabupaten) -->
                            <tr class="bg-slate-50 font-bold border-b-2 border-slate-200">
                                <td class="py-3 px-3 text-center font-bold text-slate-600">{{ $loop->iteration }}</td>
                                <td class="py-3 px-4 text-slate-900">
                                    <span class="inline-flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        {{ $parent->nama }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 font-medium">Semua Kecamatan</td>
                                <td class="py-3 px-4 text-slate-500 font-medium">-</td>
                                <td class="py-3 px-4 text-center text-blue-700">{{ $parentProspek }}</td>
                                <td class="py-3 px-4 text-center text-emerald-700">{{ $parentClosing }}</td>
                            </tr>
                            
                            <!-- Children Rows (Kecamatan) -->
                            @foreach($parent->children as $child)
                                @php
                                    $childProspek = $wilayahStats[$child->id]['prospek'] ?? 0;
                                    $childClosing = $wilayahStats[$child->id]['closing'] ?? 0;
                                    $salesUsers = $child->assignedUsers->pluck('name')->toArray();
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-3 text-center text-[10px] text-slate-400 font-medium">{{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                                    <td class="py-3 px-4"></td>
                                    <td class="py-3 px-4 text-slate-700 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-slate-300 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        {{ $child->nama }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if(count($salesUsers) > 0)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($salesUsers as $sales)
                                                    <span class="px-2 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 font-semibold border border-blue-100">
                                                        {{ $sales }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-[10px] text-slate-400 italic">Belum ada Sales</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center font-semibold text-slate-700">{{ $childProspek }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-emerald-600">{{ $childClosing }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">
                                    Tidak ada data potensi wilayah yang dapat ditampilkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-app-layout>
