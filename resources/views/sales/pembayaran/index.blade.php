@php
    $pageTitle = 'Riwayat Pembayaran';
    $pageSubtitle = 'Daftar transaksi pembayaran calon mahasiswa dan status verifikasi CS';
    $currentStatus = request('status', 'all');
@endphp

<x-app-layout :title="'Riwayat Pembayaran - CRM UCIC'">

    <div class="space-y-6" x-data="{ 
        detailModal: false, 
        selectedTrx: null,
        openDetail(data) {
            this.selectedTrx = data;
            this.detailModal = true;
        }
    }">

        <!-- Header Card -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                    Riwayat Pembayaran & Transaksi
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Monitoring pembayaran formulir dan termin calon mahasiswa secara dinamis dan real-time.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="{{ route('sales.prospek.index') }}" 
                   class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Input Transaksi di Prospek</span>
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Total Terverifikasi (Lunas) --}}
            <div class="crm-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Terverifikasi (Lunas)
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $stats['count_verified'] }} Transaksi
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-extrabold text-emerald-700 mt-2">
                    Rp {{ number_format($stats['total_verified'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Masuk ke perhitungan target Closing Sales.
                </div>
            </div>

            {{-- 2. Menunggu Verifikasi CS (Pending) --}}
            <div class="crm-card bg-white p-5 rounded-2xl border {{ $stats['count_pending'] > 0 ? 'border-amber-300 bg-amber-50/40' : 'border-slate-200/80' }} shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold {{ $stats['count_pending'] > 0 ? 'text-amber-800' : 'text-slate-400' }} uppercase tracking-wider">
                        Menunggu CS (Pending)
                    </div>
                    @if($stats['count_pending'] > 0)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                            {{ $stats['count_pending'] }} Antrean
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                            0 Antrean
                        </span>
                    @endif
                </div>
                <div class="text-xl sm:text-2xl font-extrabold {{ $stats['count_pending'] > 0 ? 'text-amber-900' : 'text-slate-700' }} mt-2">
                    Rp {{ number_format($stats['total_pending'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Sedang dicek mutasi rekening oleh CS.
                </div>
            </div>

            {{-- 3. Ditolak CS (Rejected) --}}
            <div class="crm-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Ditolak CS
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        {{ $stats['count_rejected'] }} Transaksi
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-extrabold text-slate-800 mt-2">
                    Rp {{ number_format($stats['total_rejected'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Perlu konfirmasi ulang bukti pembayaran.
                </div>
            </div>

            {{-- 4. Akumulasi Seluruh Transaksi --}}
            <div class="crm-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Total Tercatat
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $stats['count_total'] }} Total
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2">
                    Rp {{ number_format($stats['total_verified'] + $stats['total_pending'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Total bruto transaksi calon mahasiswa.
                </div>
            </div>
        </div>

        <!-- Quick Status Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-semibold">
            <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}"
               class="px-4 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                <span>Semua Status</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'all' ? 'bg-slate-800 text-slate-200' : 'bg-slate-100 text-slate-600' }}">{{ $stats['count_total'] }}</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'verified']) }}"
               class="px-4 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'verified' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                <span>Diverifikasi CS (Lunas)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'verified' ? 'bg-emerald-700 text-emerald-100' : 'bg-emerald-50 text-emerald-700' }}">{{ $stats['count_verified'] }}</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}"
               class="px-4 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                <span>Menunggu CS (Pending)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'pending' ? 'bg-amber-600 text-amber-100' : 'bg-amber-50 text-amber-800' }}">{{ $stats['count_pending'] }}</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'rejected']) }}"
               class="px-4 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                <span>Ditolak CS</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'rejected' ? 'bg-rose-700 text-rose-100' : 'bg-rose-50 text-rose-700' }}">{{ $stats['count_rejected'] }}</span>
            </a>
        </div>

        <!-- Filter & Search Bar Dinamis -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <form method="GET" action="{{ route('sales.pembayaran.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <input type="hidden" name="status" value="{{ request('status', 'all') }}">

                {{-- Search --}}
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Cari Prospek / Ref</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Nama calon mahasiswa, PIC, WhatsApp, catatan..."
                        class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                    >
                </div>

                {{-- Jenis Transaksi Dinamis --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Jenis Transaksi</label>
                    <select name="jenis" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white font-medium">
                        <option value="all" {{ request('jenis') === 'all' || !request('jenis') ? 'selected' : '' }}>Semua Jenis</option>
                        @foreach($allJenis as $j)
                            <option value="{{ $j }}" {{ request('jenis') === $j ? 'selected' : '' }}>{{ $j }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Metode Pembayaran Dinamis --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Metode Bayar</label>
                    @php
                        $methodNames = [
                            'bank_transfer'   => 'Transfer Bank',
                            'virtual_account' => 'Virtual Account',
                            'gopay'           => 'GoPay',
                            'dana'            => 'DANA',
                            'shopeepay'       => 'ShopeePay',
                            'tunai'           => 'Kasir / Tunai',
                        ];
                    @endphp
                    <select name="metode" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white font-medium">
                        <option value="all" {{ request('metode') === 'all' || !request('metode') ? 'selected' : '' }}>Semua Metode</option>
                        @foreach($allMetode as $m)
                            <option value="{{ $m }}" {{ request('metode') === $m ? 'selected' : '' }}>
                                {{ $methodNames[$m] ?? ucwords(str_replace('_', ' ', $m)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Rentang Tanggal Dari --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tanggal Mulai</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        value="{{ request('start_date') }}" 
                        class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white"
                    >
                </div>

                {{-- Rentang Tanggal Sampai + Tombol --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tanggal Selesai</label>
                    <div class="flex items-center gap-1.5">
                        <input 
                            type="date" 
                            name="end_date" 
                            value="{{ request('end_date') }}" 
                            class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white"
                        >
                        <button type="submit" class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition cursor-pointer shrink-0" title="Terapkan Filter">
                            Filter
                        </button>
                    </div>
                </div>
            </form>

            @if(request()->anyFilled(['search', 'jenis', 'metode', 'start_date', 'end_date']) || (request('status') && request('status') !== 'all'))
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="text-slate-600">
                        Hasil Filter: <strong class="text-slate-900">{{ $stats['filtered_count'] }}</strong> data ditemukan &bull; Total Nominal: <strong class="text-emerald-700 font-bold">Rp {{ number_format($stats['filtered_total_amount'], 0, ',', '.') }}</strong>
                    </div>
                    <a href="{{ route('sales.pembayaran.index') }}" class="text-rose-600 hover:text-rose-800 font-semibold text-xs flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        <span>Reset Semua Filter</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Tabel Transaksi Dinamis -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Riwayat Pembayaran</h3>
                    <p class="text-xs text-slate-500">Menampilkan {{ $transaksis->count() }} dari {{ $transaksis->total() }} total transaksi</p>
                </div>
            </div>

            @if($transaksis->isEmpty())
                <div class="py-16 text-center">
                    <p class="text-sm font-semibold text-slate-700">Belum ada data transaksi pembayaran yang sesuai</p>
                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan filter atau lakukan input transaksi pada data prospek calon mahasiswa.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Ref / Tanggal</th>
                                <th class="px-4 py-3 text-left">Calon Mahasiswa</th>
                                <th class="px-4 py-3 text-left">Jenis Transaksi</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3 text-left">Metode Bayar</th>
                                <th class="px-4 py-3 text-center">Status Pembayaran</th>
                                <th class="px-4 py-3 text-left">Status Verifikasi CS</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($transaksis as $trx)
                                @php
                                    $metodeMap = [
                                        'bank_transfer'   => ['label' => 'Transfer Bank', 'class' => 'bg-amber-50 text-amber-800 border-amber-200'],
                                        'virtual_account' => ['label' => 'Virtual Account', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                        'gopay'           => ['label' => 'GoPay', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                        'dana'            => ['label' => 'DANA', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
                                        'shopeepay'       => ['label' => 'ShopeePay', 'class' => 'bg-orange-50 text-orange-700 border-orange-200'],
                                        'tunai'           => ['label' => 'Kasir / Tunai', 'class' => 'bg-slate-50 text-slate-700 border-slate-200'],
                                    ];
                                    $metodeInfo = $metodeMap[$trx->metode_pembayaran] ?? [
                                        'label' => ucwords(str_replace('_', ' ', $trx->metode_pembayaran ?: 'Transfer Bank')),
                                        'class' => 'bg-slate-50 text-slate-700 border-slate-200'
                                    ];

                                    $cleanWa = preg_replace('/[^0-9]/', '', $trx->prospek->whatsapp ?? '');
                                    if (str_starts_with($cleanWa, '0')) {
                                        $cleanWa = '62' . substr($cleanWa, 1);
                                    }

                                    $trxJson = [
                                        'id' => $trx->id,
                                        'tanggal' => $trx->tanggal ? $trx->tanggal->format('d/m/Y') : ($trx->created_at ? $trx->created_at->format('d/m/Y') : '-'),
                                        'waktu' => $trx->created_at ? $trx->created_at->format('H:i') : '',
                                        'jenis' => $trx->jenis,
                                        'nominal' => number_format($trx->nominal, 0, ',', '.'),
                                        'metode' => $metodeInfo['label'],
                                        'status' => $trx->payment_status,
                                        'notes' => $trx->notes,
                                        'prospek_id' => $trx->prospek_id,
                                        'prospek_name' => $trx->prospek?->name ?? 'Calon Mahasiswa',
                                        'prospek_pic' => $trx->prospek?->pic ?? '-',
                                        'prospek_whatsapp' => $trx->prospek?->whatsapp ?? '-',
                                        'prospek_school' => $trx->prospek?->sekolah?->nama ?? $trx->prospek?->perusahaan?->nama ?? '-',
                                        'prospek_prodi' => $trx->prospek?->prodi?->nama ?? '-',
                                        'sales_name' => $trx->user?->name ?? ($trx->prospek?->sales?->name ?? '-'),
                                        'verifier_name' => $trx->verifier?->name ?? null,
                                        'verified_at' => $trx->verified_at ? $trx->verified_at->format('d/m/Y H:i') : null,
                                        'rejecter_name' => $trx->rejecter?->name ?? null,
                                        'rejected_at' => $trx->rejected_at ? $trx->rejected_at->format('d/m/Y H:i') : null,
                                        'rejection_reason' => $trx->rejection_reason ?? null,
                                    ];
                                @endphp

                                <tr class="hover:bg-slate-50/60 transition">
                                    {{-- Ref & Tanggal --}}
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-slate-800">
                                            {{ $trx->tanggal ? $trx->tanggal->format('d/m/Y') : ($trx->created_at ? $trx->created_at->format('d/m/Y') : '-') }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                                            <span>Ref #{{ $trx->id }}</span>
                                            @if($trx->created_at)
                                                <span>&bull; {{ $trx->created_at->format('H:i') }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Calon Mahasiswa --}}
                                    <td class="px-4 py-3.5">
                                        @if($trx->prospek)
                                            <a href="{{ route('sales.prospek.show', $trx->prospek_id) }}" class="font-bold text-blue-600 hover:text-blue-800 hover:underline block text-xs">
                                                {{ $trx->prospek->name }}
                                            </a>
                                            <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-2">
                                                <span>PIC: {{ $trx->prospek->pic ?? '-' }}</span>
                                                @if($cleanWa)
                                                    <a href="https://wa.me/{{ $cleanWa }}" target="_blank" class="text-emerald-600 hover:underline inline-flex items-center gap-0.5 font-medium">
                                                        <span>WA</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-slate-400 italic">-</span>
                                        @endif
                                    </td>

                                    {{-- Jenis Transaksi --}}
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-md font-semibold text-[11px] {{ $trx->jenis === 'Pembayaran Termin 1' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                            {{ $trx->jenis }}
                                        </span>
                                    </td>

                                    {{-- Nominal --}}
                                    <td class="px-4 py-3.5 text-right font-extrabold text-slate-900 whitespace-nowrap">
                                        Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                    </td>

                                    {{-- Metode Bayar --}}
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-md border font-semibold text-[10px] inline-flex items-center {{ $metodeInfo['class'] }}">
                                            {{ $metodeInfo['label'] }}
                                        </span>
                                    </td>

                                    {{-- Status Pembayaran --}}
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if($trx->payment_status === 'pending')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center">
                                                Menunggu CS
                                            </span>
                                        @elseif($trx->payment_status === 'verified')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center">
                                                Diverifikasi CS
                                            </span>
                                        @elseif($trx->payment_status === 'rejected')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center">
                                                Ditolak CS
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                {{ $trx->payment_status ?? 'verified' }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Status Verifikasi CS --}}
                                    <td class="px-4 py-3.5 text-slate-600 text-[11px]">
                                        @if($trx->verified_by && $trx->verifier)
                                            <div class="text-[11px] font-bold text-emerald-700">
                                                Disetujui CS: {{ $trx->verifier->name }}
                                            </div>
                                            <div class="text-[10px] text-slate-400">
                                                {{ $trx->verified_at ? $trx->verified_at->format('d/m/Y H:i') : '' }}
                                            </div>
                                        @elseif($trx->rejected_by && $trx->rejecter)
                                            <div class="text-[11px] font-bold text-rose-700">
                                                Ditolak: {{ $trx->rejection_reason ?? '-' }}
                                            </div>
                                            <div class="text-[10px] text-slate-400">
                                                Oleh: {{ $trx->rejecter->name }} ({{ $trx->rejected_at ? $trx->rejected_at->format('d/m/Y H:i') : '' }})
                                            </div>
                                        @else
                                            <div class="text-[10px] text-amber-700 font-medium italic">
                                                Menunggu pengecekan CS
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        <button type="button" 
                                                @click="openDetail(@js($trxJson))"
                                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold transition cursor-pointer"
                                                title="Lihat Rincian Transaksi">
                                            Rincian
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($transaksis->hasPages())
                    <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $transaksis->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Detail Modal Dinamis (Alpine.js) -->
        <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="detailModal = false"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 z-10 space-y-5" x-show="selectedTrx">
                    {{-- Modal Header --}}
                    <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                        <div>
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rincian Pembayaran</div>
                            <h3 class="text-base font-bold text-slate-900 mt-0.5" x-text="'Transaksi #' + (selectedTrx ? selectedTrx.id : '')"></h3>
                        </div>
                        <button @click="detailModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Status Banner --}}
                    <template x-if="selectedTrx">
                        <div>
                            <div class="p-3.5 rounded-xl border flex items-center justify-between"
                                 :class="{
                                     'bg-emerald-50 border-emerald-200 text-emerald-900': selectedTrx.status === 'verified',
                                     'bg-amber-50 border-amber-200 text-amber-900': selectedTrx.status === 'pending',
                                     'bg-rose-50 border-rose-200 text-rose-900': selectedTrx.status === 'rejected'
                                 }">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider opacity-75">Status Transaksi</div>
                                    <div class="text-sm font-extrabold" x-text="selectedTrx.status === 'verified' ? 'Diverifikasi CS (Lunas)' : (selectedTrx.status === 'pending' ? 'Menunggu Verifikasi CS' : 'Ditolak CS')"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] font-bold uppercase tracking-wider opacity-75">Nominal</div>
                                    <div class="text-base font-extrabold" x-text="'Rp ' + selectedTrx.nominal"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Detail Breakdown Card --}}
                    <template x-if="selectedTrx">
                        <div class="space-y-3 text-xs">
                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 space-y-2">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Informasi Calon Mahasiswa</div>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Nama Calon Mahasiswa:</span>
                                        <span class="font-bold text-slate-800" x-text="selectedTrx.prospek_name"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">PIC / Kontak:</span>
                                        <span class="font-semibold text-slate-700" x-text="selectedTrx.prospek_pic"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Asal Sekolah / Mitra:</span>
                                        <span class="text-slate-700" x-text="selectedTrx.prospek_school"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Program Studi:</span>
                                        <span class="text-slate-700" x-text="selectedTrx.prospek_prodi"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 space-y-2">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Detail Pembayaran</div>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Jenis Transaksi:</span>
                                        <span class="font-semibold text-purple-700" x-text="selectedTrx.jenis"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Metode Pembayaran:</span>
                                        <span class="font-semibold text-slate-800" x-text="selectedTrx.metode"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Tanggal Bayar:</span>
                                        <span class="text-slate-700" x-text="selectedTrx.tanggal + (selectedTrx.waktu ? ' ' + selectedTrx.waktu : '')"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Sales Penanggung Jawab:</span>
                                        <span class="text-slate-700 font-medium" x-text="selectedTrx.sales_name"></span>
                                    </div>
                                </div>

                                <template x-if="selectedTrx.notes">
                                    <div class="pt-2 border-t border-slate-200/60">
                                        <span class="text-slate-400 block text-[10px]">Catatan / Info Rekening:</span>
                                        <div class="text-[11px] text-slate-700 mt-0.5 bg-white p-2 rounded-lg border border-slate-200" x-text="selectedTrx.notes"></div>
                                    </div>
                                </template>
                            </div>

                            {{-- Verifikasi CS Log --}}
                            <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50 space-y-1.5">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Riwayat Verifikasi CS</div>
                                <template x-if="selectedTrx.verifier_name">
                                    <div class="text-xs text-emerald-800">
                                        Disetujui oleh <strong x-text="selectedTrx.verifier_name"></strong> pada <span x-text="selectedTrx.verified_at"></span>.
                                    </div>
                                </template>
                                <template x-if="selectedTrx.rejecter_name">
                                    <div class="text-xs text-rose-800">
                                        Ditolak oleh <strong x-text="selectedTrx.rejecter_name"></strong> pada <span x-text="selectedTrx.rejected_at"></span>.
                                        <div class="text-[11px] text-rose-700 mt-1">Alasan: <span class="italic" x-text="selectedTrx.rejection_reason || '-'"></span></div>
                                    </div>
                                </template>
                                <template x-if="!selectedTrx.verifier_name && !selectedTrx.rejecter_name">
                                    <div class="text-xs text-amber-800 italic">
                                        Transaksi sedang menunggu pemeriksaan dan approval oleh tim CS.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Modal Actions --}}
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <template x-if="selectedTrx && selectedTrx.prospek_id">
                            <a :href="'/sales/prospek/' + selectedTrx.prospek_id" class="px-4 py-2 text-xs font-semibold rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                Buka Profil Prospek
                            </a>
                        </template>
                        <button type="button" @click="detailModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer ml-auto">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
