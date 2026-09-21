@php
    $pageTitle = 'Direktori Tim (SPV)';
    $pageSubtitle = 'Daftar Anggota Tim Sales & CS di Bawah Pengawasan SPV';
@endphp

<x-app-layout :title="'Anggota Tim - Supervisor CRM'">

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

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Anggota Tim & Penugasan Wilayah</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Wilayah Kerja Utama SPV: <strong class="text-slate-800">{{ $myWilayah }}</strong></p>
            </div>
        </div>

        <!-- Team Members Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($teamData as $member)
                <div class="crm-card bg-white p-5 space-y-4 hover:shadow-md transition border border-slate-200">
                    
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $member['role'] === 'Sales' ? 'bg-blue-600' : 'bg-purple-600' }} text-white font-bold flex items-center justify-center text-sm">
                                {{ strtoupper(substr($member['name'], 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-slate-900">{{ $member['name'] }}</h3>
                                <span class="px-2 py-0.2 rounded-full text-[10px] font-bold {{ $member['role'] === 'Sales' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                    {{ $member['role'] }}
                                </span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $member['status'] === 'Aktif' || $member['status'] === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ ucfirst($member['status']) }}
                        </span>
                    </div>

                    <!-- Contact & Wilayah -->
                    <div class="bg-slate-50 p-3 rounded-xl space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Email:</span>
                            <span class="font-semibold text-slate-800">{{ $member['email'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">WhatsApp:</span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $member['phone']) }}" target="_blank" class="font-semibold text-emerald-600">
                                {{ $member['phone'] }}
                            </a>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Wilayah Tugas:</span>
                            <span class="font-bold text-blue-700">{{ $member['wilayah'] }} ({{ $member['kota'] }})</span>
                        </div>
                    </div>

                    <!-- Activity Summary -->
                    <div class="grid grid-cols-3 gap-2 text-center text-xs py-2 bg-slate-50/50 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-slate-400 text-[10px] block">Prospek</span>
                            <span class="font-bold text-slate-900">{{ $member['prospects'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] block">Closing</span>
                            <span class="font-bold text-emerald-600">{{ $member['closings'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] block">Kunjungan</span>
                            <span class="font-bold text-blue-600">{{ $member['visits'] }}</span>
                        </div>
                    </div>

                    <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-100 text-right">
                        Login terakhir: {{ $member['last_login'] }}
                    </div>

                </div>
            @empty
                <div class="col-span-3 crm-card bg-white p-12 text-center text-slate-400 text-xs">
                    Belum ada anggota tim yang terdaftar di bawah pengawasan Anda.
                </div>
            @endforelse
        </div>

    </div>

</x-app-layout>
