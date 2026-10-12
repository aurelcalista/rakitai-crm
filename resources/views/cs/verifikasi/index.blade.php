@php
    $pageTitle = 'Verifikasi & Riwayat Pembayaran';
    $pageSubtitle = $activeTab === 'pending' ? 'Menunggu Konfirmasi CS' : 'Arsip Transaksi Diproses';
@endphp

<x-app-layout :title="'Verifikasi Pembayaran - CRM UCIC'">

    <div class="space-y-6" x-data="{ rejectModal: false, rejectId: null, rejectReason: '' }">

        {{-- Header & Stat Cards --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    Verifikasi & Riwayat Pembayaran
                    @if($totalPending > 0)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            {{ $totalPending }} Perlu Tindakan
                        </span>
                    @endif
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Periksa dan verifikasi pembayaran Termin 1 dari Sales serta pantau riwayat persetujuan CS.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-600">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Audit CS Real-time
                </span>
            </div>
        </div>

        {{-- Summary Metric Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Pending --}}
            <a href="{{ route('cs.verifikasi.index', ['tab' => 'pending']) }}"
               class="p-4 rounded-2xl border transition duration-150 {{ $activeTab === 'pending' ? 'bg-amber-50/50 border-amber-300 ring-2 ring-amber-400/20' : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">Menunggu</span>
                    <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">
                        ⏳
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-amber-900">{{ $totalPending }}</span>
                    <span class="text-[11px] text-amber-700 font-medium">transaksi</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Butuh review CS hari ini</p>
            </a>

            {{-- Verified --}}
            <a href="{{ route('cs.verifikasi.index', ['tab' => 'riwayat', 'status' => 'verified']) }}"
               class="p-4 rounded-2xl border transition duration-150 {{ $activeTab === 'riwayat' && $statusFilter === 'verified' ? 'bg-emerald-50/50 border-emerald-300 ring-2 ring-emerald-400/20' : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">Disetujui</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">
                        ✓
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-emerald-900">{{ $totalVerified }}</span>
                    <span class="text-[11px] text-emerald-700 font-medium">sah / closing</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Telah dinyatakan Lunas</p>
            </a>

            {{-- Rejected --}}
            <a href="{{ route('cs.verifikasi.index', ['tab' => 'riwayat', 'status' => 'rejected']) }}"
               class="p-4 rounded-2xl border transition duration-150 {{ $activeTab === 'riwayat' && $statusFilter === 'rejected' ? 'bg-rose-50/50 border-rose-300 ring-2 ring-rose-400/20' : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">Ditolak</span>
                    <span class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-xs font-bold">
                        ✕
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-rose-900">{{ $totalRejected }}</span>
                    <span class="text-[11px] text-rose-700 font-medium">dibatalkan</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Bukti ditolak / bermasalah</p>
            </a>

            {{-- Nominal Disahkan --}}
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">Total Sah CS</span>
                    <span class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
                        Rp
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-lg sm:text-xl font-black text-slate-900">Rp {{ number_format($sumVerifiedAmount, 0, ',', '.') }}</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Total pembayaran diverifikasi</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="px-5 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="px-5 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('info') }}
            </div>
        @endif
        @if($errors->any())
            <div class="px-5 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Main Container Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">

            {{-- Tab Bar Navigation --}}
            <div class="border-b border-slate-100 px-5 pt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0">
                    {{-- Tab 1: Menunggu --}}
                    <a href="{{ route('cs.verifikasi.index', ['tab' => 'pending']) }}"
                       class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'pending' ? 'bg-white text-slate-900 border border-slate-200 shadow-xs' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <span>Menunggu Verifikasi</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $activeTab === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700' }}">
                            {{ $totalPending }}
                        </span>
                    </a>

                    {{-- Tab 2: Riwayat Verifikasi --}}
                    <a href="{{ route('cs.verifikasi.index', ['tab' => 'riwayat']) }}"
                       class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'riwayat' ? 'bg-white text-slate-900 border border-slate-200 shadow-xs' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <span>Riwayat Verifikasi</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $activeTab === 'riwayat' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-700' }}">
                            {{ $totalRiwayat }}
                        </span>
                    </a>
                </div>

                {{-- Status Pills filter for Riwayat tab --}}
                @if($activeTab === 'riwayat')
                    <div class="flex items-center gap-1.5 pb-2 sm:pb-0 text-xs">
                        <span class="text-[11px] font-semibold text-slate-400 mr-1">Filter:</span>
                        <a href="{{ route('cs.verifikasi.index', ['tab' => 'riwayat', 'q' => $search]) }}"
                           class="px-2.5 py-1 rounded-lg font-medium text-[11px] transition {{ empty($statusFilter) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Semua
                        </a>
                        <a href="{{ route('cs.verifikasi.index', ['tab' => 'riwayat', 'status' => 'verified', 'q' => $search]) }}"
                           class="px-2.5 py-1 rounded-lg font-medium text-[11px] transition {{ $statusFilter === 'verified' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                            Disetujui ({{ $totalVerified }})
                        </a>
                        <a href="{{ route('cs.verifikasi.index', ['tab' => 'riwayat', 'status' => 'rejected', 'q' => $search]) }}"
                           class="px-2.5 py-1 rounded-lg font-medium text-[11px] transition {{ $statusFilter === 'rejected' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                            Ditolak ({{ $totalRejected }})
                        </a>
                    </div>
                @endif
            </div>

            {{-- Search Bar (di kedua tab atau tab riwayat) --}}
            <div class="p-4 border-b border-slate-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form action="{{ route('cs.verifikasi.index') }}" method="GET" class="flex items-center gap-2 w-full sm:max-w-md">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    @if($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    <div class="relative w-full">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="q" value="{{ $search }}"
                               placeholder="Cari camaba, sales, catatan, atau alasan..."
                               class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    </div>
                    <button type="submit" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition shrink-0 cursor-pointer">
                        Cari
                    </button>
                    @if($search)
                        <a href="{{ route('cs.verifikasi.index', ['tab' => $activeTab, 'status' => $statusFilter]) }}"
                           class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-800 font-semibold shrink-0">
                            Reset
                        </a>
                    @endif
                </form>

                <div class="text-xs text-slate-500">
                    Menampilkan <span class="font-bold text-slate-800">{{ $transaksis->total() }}</span> transaksi
                </div>
            </div>

            {{-- ═════════════════════════════════════════════════════════════ --}}
            {{-- TAB 1: DAFTAR MENUNGGU VERIFIKASI                           --}}
            {{-- ═════════════════════════════════════════════════════════════ --}}
            @if($activeTab === 'pending')
                @if($transaksis->isEmpty())
                    <div class="py-16 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 font-bold text-xl">
                            ✓
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Tidak ada pembayaran yang perlu diverifikasi</p>
                        <p class="text-xs text-slate-400 mt-1">Semua transaksi Termin 1 dari Sales sudah diproses.</p>
                        <a href="{{ route('cs.verifikasi.index', ['tab' => 'riwayat']) }}" class="mt-4 inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-700 font-bold">
                            Lihat Riwayat Verifikasi →
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Camaba / Prospek</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sales Lapangan</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Jenis Transaksi</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nominal</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Metode & Info Bank</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Input</th>
                                    <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aksi Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($transaksis as $trx)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        {{-- Prospek --}}
                                        <td class="px-4 py-4">
                                            <a href="{{ route('prospek.show', $trx->prospek_id) }}" class="font-bold text-slate-900 hover:text-blue-600 block text-xs">
                                                {{ $trx->prospek?->name ?? 'Prospek #' . $trx->prospek_id }}
                                            </a>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-slate-400 text-[10px]">{{ $trx->prospek?->type ?? 'Individu' }}</span>
                                                @if($trx->prospek?->whatsapp)
                                                    <span class="text-[10px] text-slate-400">• {{ $trx->prospek->whatsapp }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        {{-- Sales --}}
                                        <td class="px-4 py-4">
                                            <span class="font-medium text-slate-800">{{ $trx->user?->name ?? $trx->prospek?->sales?->name ?? '-' }}</span>
                                        </td>
                                        {{-- Jenis Transaksi --}}
                                        <td class="px-4 py-4">
                                            <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-100 font-semibold text-[10px]">
                                                {{ $trx->jenis }}
                                            </span>
                                        </td>
                                        {{-- Nominal --}}
                                        <td class="px-4 py-4 text-right font-black text-slate-900 text-xs">
                                            Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                        </td>
                                        {{-- Metode --}}
                                        <td class="px-4 py-4">
                                            @php
                                                $metodeIcons = [
                                                    'bank_transfer'   => ['label' => 'Transfer Bank', 'color' => 'bg-amber-50 text-amber-800 border-amber-200'],
                                                    'virtual_account' => ['label' => 'Virtual Account', 'color' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                                    'gopay'           => ['label' => 'GoPay', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                                    'dana'            => ['label' => 'DANA', 'color' => 'bg-sky-50 text-sky-700 border-sky-200'],
                                                    'shopeepay'       => ['label' => 'ShopeePay', 'color' => 'bg-orange-50 text-orange-700 border-orange-200'],
                                                    'tunai'           => ['label' => 'Kasir / Tunai', 'color' => 'bg-slate-50 text-slate-700 border-slate-200'],
                                                ];
                                                $m = $metodeIcons[$trx->metode_pembayaran] ?? ['label' => $trx->metode_pembayaran ?: 'Transfer Bank', 'color' => 'bg-slate-50 text-slate-600 border-slate-200'];
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-md border font-semibold text-[10px] inline-flex items-center gap-1 {{ $m['color'] }}">
                                                {{ $m['label'] }}
                                            </span>

                                            @if($trx->notes)
                                                <div class="mt-1 text-[10px] text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-100 max-w-xs leading-tight font-medium">
                                                    {{ $trx->notes }}
                                                </div>
                                            @endif
                                        </td>
                                        {{-- Tanggal --}}
                                        <td class="px-4 py-4 text-slate-600">
                                            {{ $trx->tanggal?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        {{-- Aksi --}}
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                {{-- Tombol Verifikasi --}}
                                                <form action="{{ route('cs.verifikasi.verify', $trx->id) }}" method="POST"
                                                      onsubmit="event.preventDefault(); confirmVerifyPayment(this, '{{ addslashes($trx->prospek?->name ?? 'Calon Mahasiswa') }}', '{{ number_format($trx->nominal, 0, ',', '.') }}');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold shadow-xs transition flex items-center gap-1 cursor-pointer">
                                                        ✓ Verifikasi
                                                    </button>
                                                </form>

                                                {{-- Tombol Tolak --}}
                                                <button type="button"
                                                    @click="rejectModal = true; rejectId = {{ $trx->id }}"
                                                    class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-bold transition flex items-center gap-1 cursor-pointer">
                                                    ✕ Tolak
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            {{-- ═════════════════════════════════════════════════════════════ --}}
            {{-- TAB 2: RIWAYAT VERIFIKASI (VERIFIED & REJECTED)             --}}
            {{-- ═════════════════════════════════════════════════════════════ --}}
            @else
                @if($transaksis->isEmpty())
                    <div class="py-16 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 font-bold text-xl">
                            📋
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Belum ada riwayat transaksi yang diproses</p>
                        <p class="text-xs text-slate-400 mt-1">Transaksi yang telah Anda setujui atau tolak akan dicatat secara transparan di sini.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Camaba / Prospek</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sales Pemilik</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nominal</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Metode</th>
                                    <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Diproses Oleh</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Waktu Proses</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Catatan / Alasan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($transaksis as $trx)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        {{-- Prospek --}}
                                        <td class="px-4 py-3.5">
                                            <a href="{{ route('prospek.show', $trx->prospek_id) }}" class="font-bold text-slate-900 hover:text-blue-600 block text-xs">
                                                {{ $trx->prospek?->name ?? 'Prospek #' . $trx->prospek_id }}
                                            </a>
                                            <span class="text-slate-400 text-[10px]">{{ $trx->prospek?->type ?? 'Individu' }}</span>
                                        </td>
                                        {{-- Sales --}}
                                        <td class="px-4 py-3.5">
                                            <span class="font-medium text-slate-800">{{ $trx->user?->name ?? $trx->prospek?->sales?->name ?? '-' }}</span>
                                        </td>
                                        {{-- Nominal --}}
                                        <td class="px-4 py-3.5 text-right font-black text-slate-900 text-xs">
                                            Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                        </td>
                                        {{-- Metode --}}
                                        <td class="px-4 py-3.5">
                                            @php
                                                $metodeLabel = \App\Models\Transaksi::METODE_PEMBAYARAN[$trx->metode_pembayaran] ?? ($trx->metode_pembayaran ?: 'Transfer Bank');
                                            @endphp
                                            <span class="text-[11px] text-slate-700 font-semibold">{{ $metodeLabel }}</span>
                                        </td>
                                        {{-- Status Badge --}}
                                        <td class="px-4 py-3.5 text-center">
                                            @if($trx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED)
                                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] inline-flex items-center gap-1 border border-emerald-200">
                                                    ✓ Disetujui
                                                </span>
                                            @elseif($trx->payment_status === \App\Models\Transaksi::STATUS_REJECTED)
                                                <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[10px] inline-flex items-center gap-1 border border-rose-200">
                                                    ✕ Ditolak
                                                </span>
                                            @else
                                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px] border border-amber-200">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        {{-- Diproses Oleh --}}
                                        <td class="px-4 py-3.5">
                                            @if($trx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED)
                                                <div class="font-semibold text-slate-800 text-[11px]">
                                                    {{ $trx->verifier?->name ?? 'CS Staff' }}
                                                </div>
                                                <span class="text-[9px] text-emerald-600 font-bold">Verifikator</span>
                                            @elseif($trx->payment_status === \App\Models\Transaksi::STATUS_REJECTED)
                                                <div class="font-semibold text-slate-800 text-[11px]">
                                                    {{ $trx->rejecter?->name ?? 'CS Staff' }}
                                                </div>
                                                <span class="text-[9px] text-rose-600 font-bold">Menolak</span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        {{-- Waktu Proses --}}
                                        <td class="px-4 py-3.5 text-slate-600 text-[11px]">
                                            @if($trx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED && $trx->verified_at)
                                                {{ $trx->verified_at->format('d/m/Y H:i') }}
                                            @elseif($trx->payment_status === \App\Models\Transaksi::STATUS_REJECTED && $trx->rejected_at)
                                                {{ $trx->rejected_at->format('d/m/Y H:i') }}
                                            @else
                                                {{ $trx->updated_at?->format('d/m/Y H:i') ?? '-' }}
                                            @endif
                                        </td>
                                        {{-- Catatan / Alasan --}}
                                        <td class="px-4 py-3.5">
                                            @if($trx->rejection_reason)
                                                <div class="text-[10px] text-rose-700 bg-rose-50 p-1.5 rounded-lg border border-rose-100 font-medium max-w-xs leading-tight">
                                                    <span class="font-bold">Alasan tolak:</span> {{ $trx->rejection_reason }}
                                                </div>
                                            @elseif($trx->notes)
                                                <div class="text-[10px] text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-100 font-medium max-w-xs truncate">
                                                    {{ $trx->notes }}
                                                </div>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif

            {{-- Pagination --}}
            @if($transaksis->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $transaksis->links() }}
                </div>
            @endif

        </div>

    </div>

    {{-- MODAL TOLAK --}}
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="rejectModal = false; rejectId = null; rejectReason = ''"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">Tolak Pembayaran</h3>
                    <button @click="rejectModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <p class="text-xs text-slate-600 mb-4">Pembayaran akan ditolak dan Sales akan mendapatkan notifikasi. Transaksi <strong>tidak</strong> akan menambah target Closing.</p>

                <template x-for="trxId in [rejectId]" :key="trxId">
                    <form :action="`/cs/verifikasi/${rejectId}/reject`" method="POST">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan (wajib/disarankan)</label>
                                <textarea name="rejection_reason" x-model="rejectReason" rows="3"
                                    placeholder="Contoh: Bukti transfer tidak valid, mutasi bank tidak ditemukan, nominal kurang, dsb."
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition"></textarea>
                            </div>
                            <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                                <button type="button" @click="rejectModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-xs cursor-pointer">
                                    Tolak Pembayaran
                                </button>
                            </div>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

    <script>
        function confirmVerifyPayment(form, name, nominal) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Verifikasi Pembayaran',
                    html: `<div class="text-xs text-slate-600">
                            <p class="mb-3">Yakin ingin memverifikasi pembayaran ini? Calon mahasiswa akan dinyatakan <strong>LUNAS</strong> dan masuk ke target Closing Sales.</p>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-left space-y-1.5 text-xs">
                                <div class="flex justify-between"><span class="text-slate-500">Calon Mahasiswa:</span> <span class="font-bold text-slate-800">${name}</span></div>
                                <div class="flex justify-between"><span class="text-slate-500">Nominal:</span> <span class="font-bold text-emerald-700">Rp ${nominal}</span></div>
                            </div>
                           </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Verifikasi Sekarang',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-100 p-6',
                        title: 'text-slate-900 font-bold text-base',
                        confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5 shadow-xs',
                        cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm('Yakin memverifikasi pembayaran ini? Prospek akan dinyatakan LUNAS.')) {
                    form.submit();
                }
            }
        }
    </script>

</x-app-layout>
