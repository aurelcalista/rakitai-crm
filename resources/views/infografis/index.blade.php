@php $pageTitle = 'Infografis & Statistik CRM'; @endphp

<x-app-layout :title="'Infografis & Statistik - CRM UCIC'">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Infografis & Statistik CRM </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Ringkasan statistik performa, pembatalan beasiswa (Cancel), pemberkasan, dan teritori wilayah.</p>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Prospek</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm"></div>
                </div>
                <div class="text-2xl font-extrabold text-slate-900 mt-3">{{ number_format($totalProspek) }}</div>
                <p class="text-[11px] text-slate-400 mt-1">Seluruh prospek terdaftar</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-rose-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-rose-600 uppercase tracking-wider">CANCEL (Pengunduran Diri)</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm"></div>
                </div>
                <div class="text-2xl font-extrabold text-rose-700 mt-3">{{ number_format($cancelCount) }}</div>
                <p class="text-[11px] text-rose-500 mt-1">Penerima beasiswa mengundurkan diri</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-purple-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Pemberkasan</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm"></div>
                </div>
                <div class="text-2xl font-extrabold text-purple-700 mt-3">{{ number_format($pemberkasanCount) }}</div>
                <p class="text-[11px] text-purple-500 mt-1">Target Pemberkasan: {{ number_format($targetPemberkasan) }}</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-emerald-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Closing (LUNAS)</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm"></div>
                </div>
                <div class="text-2xl font-extrabold text-emerald-700 mt-3">{{ number_format($lunasCount) }}</div>
                <p class="text-[11px] text-emerald-500 mt-1">Mahasiswa lunas pendaftaran</p>
            </div>
        </div>

        <!-- Tabel Indikator & Skor Wilayah (PRD Business Logic Final) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <span>Indikator & Skor Wilayah</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-100 text-purple-800">
                            Bobot: Kontak {{ $bobotWeights['bobot_kontak'] ?? 40 }}% | Closing {{ $bobotWeights['bobot_closing'] ?? 60 }}%
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Penilaian performa gabungan (Skor Volume Kontak 40% + Skor Closing 60%), Grade, dan Matriks Performa per Wilayah Teritori.</p>
                </div>

                @if(strtolower(auth()->user()->role) === 'admin')
                    <form action="{{ route('admin.master-data.update-weights') }}" method="POST" class="flex items-center gap-2 text-xs bg-slate-50 p-2 rounded-xl border border-slate-200">
                        @csrf
                        <span class="font-bold text-slate-700">Ubah Bobot:</span>
                        <input type="number" name="bobot_kontak" value="{{ $bobotWeights['bobot_kontak'] ?? 40 }}" min="0" max="100" class="w-14 h-7 text-xs border-slate-300 rounded px-1.5 font-bold text-slate-900" title="Bobot Kontak (%)">
                        <span class="text-slate-400">:</span>
                        <input type="number" name="bobot_closing" value="{{ $bobotWeights['bobot_closing'] ?? 60 }}" min="0" max="100" class="w-14 h-7 text-xs border-slate-300 rounded px-1.5 font-bold text-slate-900" title="Bobot Closing (%)">
                        <button type="submit" class="px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition text-[11px]">Simpan</button>
                    </form>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">Wilayah (Kode)</th>
                            <th class="py-3 px-3 text-center">Skor Kontak (Vol / Target)</th>
                            <th class="py-3 px-3 text-center">Skor Closing (LUNAS / Target)</th>
                            <th class="py-3 px-3 text-center">Skor Wilayah</th>
                            <th class="py-3 px-3 text-center">Grade</th>
                            <th class="py-3 px-4">Matriks Performa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($wilayahIndicators ?? [] as $ind)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    {{ $ind['nama_wilayah'] }}
                                    <span class="text-slate-400 text-[11px] font-normal">({{ $ind['kode_wilayah'] }})</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="font-bold text-slate-800">{{ $ind['skor_kontak'] !== null ? $ind['skor_kontak'] . '%' : 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400">({{ number_format($ind['realisasi_kontak']) }} / {{ number_format($ind['target_kontak']) }})</div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="font-bold text-emerald-700">{{ $ind['skor_closing'] !== null ? $ind['skor_closing'] . '%' : 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400">({{ number_format($ind['realisasi_lunas']) }} / {{ number_format($ind['target_lunas']) }})</div>
                                </td>
                                <td class="py-3.5 px-3 text-center font-extrabold text-purple-700 text-sm">
                                    {{ $ind['skor_wilayah'] !== null ? $ind['skor_wilayah'] . '%' : 'N/A' }}
                                </td>
                                <td class="py-3.5 px-3 text-center align-middle">
                                    <div class="flex flex-col items-center justify-center gap-1.5 mt-1">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold border {{ $ind['grade']['badge'] }}">
                                            {{ $ind['grade']['code'] === 'N/A' ? 'N/A' : 'Grade ' . $ind['grade']['code'] }}
                                        </span>
                                        @if($ind['grade']['code'] !== 'N/A')
                                            <span class="text-[10px] text-slate-500 font-medium max-w-[100px] leading-tight break-words text-center">
                                                {{ trim(str_replace($ind['grade']['code'] . ' — ', '', $ind['grade']['label'])) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 max-w-[220px] align-middle">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-bold text-slate-800 text-xs leading-tight whitespace-normal">{{ $ind['matrix']['label'] }}</span>
                                        <span class="text-[10px] text-slate-500 leading-tight whitespace-normal">{{ $ind['matrix']['desc'] }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">Belum ada data indikator wilayah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Wilayah Territory Breakdown Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Statistik Wilayah Teritori</h3>
                <p class="text-xs text-slate-500 mt-0.5">Ringkasan prospek, pendaftaran lunas, pemberkasan, dan status cancel per wilayah.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">Nama Wilayah</th>
                            <th class="py-3 px-3 text-center">Total Prospek</th>
                            <th class="py-3 px-3 text-center">Pemberkasan</th>
                            <th class="py-3 px-3 text-center">LUNAS</th>
                            <th class="py-3 px-3 text-center">CANCEL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($wilayahStats as $w)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900">{{ $w->nama }}</td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-700">{{ number_format($w->total_prospek) }}</td>
                                <td class="py-3.5 px-3 text-center font-bold text-purple-700">{{ number_format($w->total_berkas) }}</td>
                                <td class="py-3.5 px-3 text-center font-bold text-emerald-700">{{ number_format($w->total_lunas) }}</td>
                                <td class="py-3.5 px-3 text-center font-bold text-rose-600">{{ number_format($w->total_cancel) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">Belum ada data statistik wilayah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
