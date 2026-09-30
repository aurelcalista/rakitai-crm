@php
    $pageTitle = 'Kelola Tim';
    $pageSubtitle = 'Atur struktur Supervisor, Sales, dan CS berdasarkan wilayah kerja.';
@endphp

<x-app-layout :title="'Kelola Tim - CRM UCIC'">
    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if($errors->any())
            <x-alert type="error">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <!-- FORM ASSIGNMENT -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5 sm:p-6" x-data="{
            selectedKota: '',
            selectedKec: '',
            kecamatans: {{ json_encode($kecamatan) }},
            kotas: {{ json_encode($kota) }},
            get filteredKecamatan() {
                if(!this.selectedKota) return [];
                return this.kecamatans.filter(k => k.parent_id == this.selectedKota);
            },
            get assignmentCode() {
                if(!this.selectedKota || !this.selectedKec) return '-';
                let k = this.kotas.find(x => x.id == this.selectedKota);
                let c = this.kecamatans.find(x => x.id == this.selectedKec);
                if(k && c) return k.kode + c.kode;
                return '-';
            }
        }">
            <h2 class="text-base font-bold text-slate-800 mb-4">Buat / Ubah Assignment Tim</h2>
            <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 mb-5 text-sm text-slate-700">
                <span class="block mb-1 text-blue-900 font-bold">Aturan Assignment:</span>
                <p class="mb-2">Supervisor bertanggung jawab atas satu wilayah Kota/Kabupaten. Setiap Kecamatan dapat memiliki maksimal satu Sales.</p>
                <div class="bg-white/60 p-3 rounded-lg border border-blue-100">
                    <p class="font-semibold text-blue-900 mb-1">Contoh kode wilayah:</p>
                    <ul class="space-y-0.5 text-xs text-slate-700">
                        <li>Kota Cirebon = 45</li>
                        <li>Harjamukti = 01</li>
                        <li class="font-bold text-blue-700 mt-1">Kode area tim = 4501</li>
                    </ul>
                </div>
            </div>

            <form action="{{ route('tim.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                @csrf
                
                <!-- Supervisor -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Supervisor (Ketua) <span class="text-red-500">*</span></label>
                    <select name="supervisor_id" required class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                        <option value="">Pilihan Supervisor...</option>
                        @foreach($supervisors as $s)
                            @if($s->wilayah)
                                <option value="{{ $s->id }}">{{ $s->name }} — SPV — {{ $s->wilayah->nama }} ({{ $s->wilayah->kode }})</option>
                            @else
                                <option value="{{ $s->id }}">{{ $s->name }} — SPV</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Kota / Kabupaten -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kota/Kabupaten <span class="text-red-500">*</span></label>
                    <select name="kota_id" required x-model="selectedKota" @change="selectedKec = ''" class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                        <option value="">Pilihan Kota/Kabupaten...</option>
                        @foreach($kota as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kecamatan -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
                    <select name="kecamatan_id" required x-model="selectedKec" class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10" :disabled="!selectedKota">
                        <option value="">Pilihan Kecamatan...</option>
                        <template x-for="k in filteredKecamatan" :key="k.id">
                            <option :value="k.id" x-text="k.nama"></option>
                        </template>
                    </select>
                </div>

                <!-- Assignment Code Display -->
                <div class="lg:col-span-6 bg-slate-50 p-3 rounded-lg border border-slate-200 flex items-center justify-between h-12">
                    <span class="text-sm font-semibold text-slate-700">Kode Wilayah:</span>
                    <span class="text-base font-bold text-blue-700 tracking-wider bg-blue-100 px-3 py-1 rounded-md" x-text="assignmentCode"></span>
                </div>

                <!-- Sales -->
                <div class="lg:col-span-6">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sales (Anggota) <span class="text-red-500">*</span></label>
                    <select name="sales_id" required class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                        <option value="">Pilihan Sales...</option>
                        @foreach($salesList as $s)
                            @if($s->wilayah && $s->wilayah->parent)
                                <option value="{{ $s->id }}">{{ $s->name }} — Sales — Area {{ $s->wilayah->parent->kode . $s->wilayah->kode }} — {{ $s->wilayah->nama }}, {{ $s->wilayah->parent->nama }}</option>
                            @else
                                <option value="{{ $s->id }}">{{ $s->name }} — Sales</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-6 mt-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-xl text-sm shadow-xs transition">
                        Simpan Assignment
                    </button>
                </div>
            </form>
        </div>

        <!-- DAFTAR STRUKTUR TIM -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-slate-800 mb-6">Struktur Tim Saat Ini</h2>

            <div class="space-y-6">
                @forelse($tims as $spv)
                    @php
                        $subordinatesByWilayah = $spv->subordinates->groupBy('wilayah_id');
                        $jmlKecamatan = $subordinatesByWilayah->count();
                        $jmlSales = $spv->subordinates->where('role', 'Sales')->count();
                        $jmlCs = $spv->subordinates->where('role', 'CS')->count();
                    @endphp
                    <div class="border border-slate-200 rounded-xl overflow-hidden" x-data="{ openDetail: false }">
                        <!-- SPV Header -->
                        <div class="bg-white px-5 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xl shrink-0 mt-1 md:mt-0">
                                    {{ substr($spv->name, 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-lg">{{ $spv->name }} <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full ml-2 align-middle border border-blue-200">SPV</span></h3>
                                    
                                    @if($spv->wilayah)
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <p class="text-sm font-semibold text-slate-700">{{ $spv->wilayah->nama }}</p>
                                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                            <p class="text-sm font-medium text-slate-500">Kode Wilayah: <span class="font-bold text-slate-700">{{ $spv->wilayah->kode }}</span></p>
                                        </div>
                                        <p class="text-xs font-medium text-slate-500 mt-2 flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1"><svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg> {{ $jmlKecamatan }} Kecamatan</span>
                                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                            <span class="inline-flex items-center gap-1"><svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> {{ $jmlSales }} Sales</span>
                                        </p>
                                    @else
                                        <p class="text-sm font-semibold text-amber-600 mt-1">Belum memiliki wilayah</p>
                                    @endif
                                </div>
                            </div>
                            
                            @if($spv->wilayah)
                            <div class="flex-shrink-0 self-start md:self-center ml-16 md:ml-0">
                                <button @click="openDetail = !openDetail" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition whitespace-nowrap">
                                    <span x-text="openDetail ? 'Tutup Detail' : 'Lihat Detail'"></span>
                                </button>
                            </div>
                            @endif
                        </div>

                        <!-- Members list (Grouped by Kecamatan) -->
                        <div x-show="openDetail" style="display: none;" class="border-t border-slate-200 bg-slate-50">
                            @if($subordinatesByWilayah->isEmpty())
                                <div class="p-6 text-center">
                                    <p class="text-sm text-slate-500 font-medium">Belum ada anggota tim (Sales) yang ditugaskan ke supervisor ini di wilayah manapun.</p>
                                </div>
                            @else
                                <div class="p-5">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 px-1">Daftar Wilayah Kerja</h4>
                                    
                                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left border-collapse">
                                                <thead>
                                                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-500 font-bold">
                                                        <th class="py-3 px-3 w-10 text-center">No</th>
                                                        <th class="py-3 px-4">Kecamatan</th>
                                                        <th class="py-3 px-4">Kode Area</th>
                                                        <th class="py-3 px-4">Sales</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 text-sm">
                                                @foreach($subordinatesByWilayah as $wilayahId => $members)
                                                    @php
                                                        $wilayahNama = $members->first()->wilayah ? $members->first()->wilayah->nama : 'Unknown';
                                                        $sales = $members->where('role', 'Sales')->first();
                                                        $kodeArea = '-';
                                                        if ($spv->wilayah && $members->first()->wilayah) {
                                                            $kodeArea = $spv->wilayah->kode . $members->first()->wilayah->kode;
                                                        }
                                                    @endphp
                                                    <tr class="hover:bg-slate-50 transition">
                                                        <td class="py-3 px-3 text-center font-bold text-slate-400">{{ $loop->iteration }}</td>
                                                        <td class="py-3 px-4 font-semibold text-slate-800">{{ $wilayahNama }}</td>
                                                        <td class="py-3 px-4">
                                                            <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold bg-blue-100 text-blue-800 rounded-md">{{ $kodeArea }}</span>
                                                        </td>
                                                        <td class="py-3 px-4 font-medium text-slate-700">{{ $sales ? $sales->name : '-' }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 px-4 bg-slate-50 border border-dashed border-slate-300 rounded-xl">
                        <div class="w-16 h-16 bg-white border border-slate-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 mb-1">Belum ada assignment tim</h3>
                        <p class="text-sm text-slate-500">Kamu belum membuat pembagian wilayah dan anggota tim.</p>
                    </div>
                @endforelse
            </div>

        </div>

    </div>
</x-app-layout>
