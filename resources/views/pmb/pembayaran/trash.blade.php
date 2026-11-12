@php
    $pageTitle = 'Trash Data Pembayaran';
    $pageSubtitle = 'Tempat Sampah (Soft Delete) Data Pembayaran PMB UCIC';
    $userRole = strtolower(auth()->user()->role ?? '');
@endphp

<x-app-layout :title="'Trash Data Pembayaran PMB - CRM UCIC'">
    <div class="space-y-6">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs transition-all">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="font-bold text-xs">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 p-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs transition-all">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <span class="font-bold text-xs">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 p-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- Top Breadcrumbs & Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-900/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-display">Trash Data Pembayaran PMB</h1>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-0.5 flex-wrap">
                        <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Dashboard</a>
                        <span>&gt;</span>
                        <a href="{{ route('pmb.pembayaran.index') }}" class="hover:text-blue-600 transition">Data Pembayaran PMB</a>
                        <span>&gt;</span>
                        <span class="font-semibold text-rose-700">Trash (Tempat Sampah)</span>
                    </div>
                </div>
            </div>

            <a
                href="{{ route('pmb.pembayaran.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition cursor-pointer self-start md:self-auto"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Data Pembayaran</span>
            </a>
        </div>

        <!-- Menu Tab Data Pembayaran Aktif vs Trash -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-3">
            <a
                href="{{ route('pmb.pembayaran.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition"
            >
                <svg class="w-4 h-4 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>Data Pembayaran Aktif</span>
            </a>
            <a
                href="{{ route('pmb.pembayaran.trash') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Trash (Tempat Sampah)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-white/20 text-white">
                    {{ $totalTrashed }}
                </span>
            </a>
        </div>

        <!-- Perlindungan Keuangan Info Banner -->
        <div class="bg-amber-50/90 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 text-xs text-amber-900">
            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div class="space-y-1">
                <p class="font-extrabold text-amber-950">Perlindungan Data &amp; Histori Transaksi Keuangan</p>
                <p class="text-[11px] leading-relaxed text-amber-800">
                    Data CAMABA yang dipindahkan ke Trash menggunakan mekanisme <strong>Soft Delete</strong>. Seluruh riwayat transaksi pembayaran, bukti bayar, nominal, dan informasi voucher diskon tetap aman tersimpan.
                    Gunakan tombol <strong>Pulihkan</strong> untuk mengembalikan data secara utuh. Opsi <strong>Hapus Permanen</strong> hanya diizinkan pada data yang tidak memiliki riwayat transaksi keuangan atau penggunaan voucher agar integritas keuangan tetap terjaga.
                </p>
            </div>
        </div>

        <!-- Search Bar Card -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-xs">
            <form action="{{ route('pmb.pembayaran.trash') }}" method="GET" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari di Trash: Nama CAMABA, No. Pendaftaran, Email, Program Studi, atau Order ID..."
                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 bg-slate-50/70 text-slate-800 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition"
                    >
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-2xs transition cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Cari di Trash</span>
                    </button>
                    @if(request()->filled('search'))
                        <a
                            href="{{ route('pmb.pembayaran.trash') }}"
                            class="inline-flex items-center justify-center px-3.5 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Trash Table Card -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
            @if($trashedList->isEmpty())
                <!-- Empty State -->
                <div class="p-12 sm:p-16 text-center space-y-3">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    @if(request()->filled('search'))
                        <h3 class="text-sm font-extrabold text-slate-800">Tidak ada data di Trash yang cocok</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            Tidak ditemukan data terhapus untuk kata kunci "<strong>{{ request('search') }}</strong>".
                        </p>
                        <div class="pt-2">
                            <a href="{{ route('pmb.pembayaran.trash') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                Tampilkan Semua Data Trash
                            </a>
                        </div>
                    @else
                        <h3 class="text-sm font-extrabold text-slate-800">Trash Data Pembayaran masih kosong</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            Belum ada data pembayaran CAMABA yang dihapus. Data yang dihapus dari menu Data Pembayaran PMB akan tampil di sini agar dapat dipulihkan jika diperlukan.
                        </p>
                        <div class="pt-2">
                            <a href="{{ route('pmb.pembayaran.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white transition">
                                Lihat Data Pembayaran Aktif
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap min-w-[1000px]">
                        <thead>
                            <tr class="bg-slate-50/90 text-slate-500 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200/80">
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4">CAMABA &amp; No. Pendaftaran</th>
                                <th class="py-3.5 px-4">Email &amp; Kontak</th>
                                <th class="py-3.5 px-4">Program Studi</th>
                                <th class="py-3.5 px-4">Informasi Pembayaran</th>
                                <th class="py-3.5 px-4">Dihapus Pada &amp; Oleh</th>
                                <th class="py-3.5 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($trashedList as $index => $p)
                                @php
                                    $r = $p->rekap;
                                    $regNo = $p->no_registrasi ?: ('REG-' . str_pad($p->id, 4, '0', STR_PAD_LEFT));
                                    $deletedAtText = $p->deleted_at ? $p->deleted_at->format('d M Y, H:i') . ' WIB' : '-';
                                    $deletedByName = $p->deletedBy?->name ?? 'Admin';
                                    $dppVoucher = $r['rincian']['dpp']['voucher'] ?? null;
                                    $hasFinancialRelations = ($p->transaksis_count > 0) || ($p->voucher_usages_count > 0);
                                    $modalPayload = [
                                        'name'           => $p->name ?? '-',
                                        'reg_no'         => $regNo,
                                        'email'          => $p->email ?? '-',
                                        'prodi'          => $p->prodi?->nama ?? '-',
                                        'terbayar'       => 'Rp ' . number_format($r['total_terbayar'], 0, ',', '.'),
                                        'trx_count'      => $p->transaksis_count,
                                        'voucher_count'  => $p->voucher_usages_count,
                                        'has_relations'  => $hasFinancialRelations,
                                    ];
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-4 px-4 text-center font-bold text-slate-400">
                                        {{ ($trashedList->currentPage() - 1) * $trashedList->perPage() + $index + 1 }}
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-extrabold text-slate-900 text-xs sm:text-sm">{{ $p->name }}</div>
                                        <div class="font-mono text-[11px] font-bold text-[#0b3d82] mt-0.5">{{ $regNo }}</div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-700">{{ $p->email ?: '-' }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $p->whatsapp ?: '-' }}</div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="font-bold text-slate-800">{{ $p->prodi?->nama ?? '-' }}</span>
                                        @if($p->prodi?->jenjang)
                                            <span class="block text-[10px] text-slate-400">{{ $p->prodi->jenjang }}</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-extrabold text-emerald-700">Rp {{ number_format($r['total_terbayar'], 0, ',', '.') }}</span>
                                                <span class="text-slate-400">/</span>
                                                <span class="text-slate-600 font-semibold">Rp {{ number_format($r['total_kewajiban'], 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $r['is_lunas'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                    {{ $r['status_keseluruhan'] }}
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                    {{ $p->transaksis_count }} Transaksi
                                                </span>
                                                @if($dppVoucher)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0b3d82] border border-blue-200">
                                                        Voucher {{ $dppVoucher['voucher_code'] }} (-{{ $dppVoucher['percentage_formatted'] }}%)
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-700">{{ $deletedAtText }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            Oleh: <span class="font-semibold text-slate-700">{{ $deletedByName }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <!-- Aksi Pulihkan -->
                                            <form
                                                action="{{ route('pmb.pembayaran.restore', $p->id) }}"
                                                method="POST"
                                                class="inline"
                                                onsubmit="return window.confirmRestorePembayaran(event, {{ json_encode($modalPayload) }})"
                                            >
                                                @csrf
                                                <button
                                                    type="submit"
                                                    title="Pulihkan Data ke Daftar Utama"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border border-emerald-200 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white hover:border-emerald-600 shadow-2xs transition-all cursor-pointer"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                    </svg>
                                                    <span>Pulihkan</span>
                                                </button>
                                            </form>

                                            <!-- Aksi Hapus Permanen -->
                                            <form
                                                action="{{ route('pmb.pembayaran.force-delete', $p->id) }}"
                                                method="POST"
                                                class="inline"
                                                onsubmit="return window.confirmForceDeletePembayaran(event, {{ json_encode($modalPayload) }})"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    title="{{ $hasFinancialRelations ? 'Dilindungi: Memiliki riwayat transaksi keuangan' : 'Hapus Permanen dari Database' }}"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border border-rose-200 bg-rose-50 hover:bg-rose-700 text-rose-700 hover:text-white hover:border-rose-700 shadow-2xs transition-all cursor-pointer"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span>Hapus Permanen</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($trashedList->hasPages())
                    <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $trashedList->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>

    <script>
        (function() {
            const esc = (val) => {
                const div = document.createElement('div');
                div.textContent = val ?? '-';
                return div.innerHTML;
            };

            window.confirmRestorePembayaran = function(event, camaba) {
                const form = event.target;
                if (form.dataset.confirmed === 'true') {
                    return true;
                }
                event.preventDefault();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Pulihkan Data Pembayaran?',
                        html: `
                            <div class="text-left text-xs space-y-2 mt-2">
                                <p class="text-slate-600">Data CAMABA berikut akan dikembalikan ke daftar utama <strong>Data Pembayaran PMB</strong>:</p>
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 space-y-1">
                                    <div><span class="text-slate-500">Nama CAMABA:</span> <strong class="text-slate-900">${esc(camaba.name)}</strong></div>
                                    <div><span class="text-slate-500">No. Pendaftaran:</span> <strong class="font-mono text-[#0b3d82]">${esc(camaba.reg_no)}</strong></div>
                                    <div><span class="text-slate-500">Total Terbayar:</span> <strong class="text-emerald-700">${esc(camaba.terbayar)}</strong> (${esc(camaba.trx_count)} transaksi)</div>
                                </div>
                                <p class="text-[11px] text-slate-500">Seluruh riwayat transaksi, bukti pembayaran, status, dan voucher akan utuh kembali tanpa duplikasi data.</p>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#059669',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Ya, Pulihkan Data',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        customClass: {
                            popup: 'rounded-2xl shadow-2xl border border-slate-100 p-6',
                            title: 'text-slate-900 font-bold text-lg',
                            confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5 shadow-xs',
                            cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = 'true';
                            form.submit();
                        }
                    });
                } else if (confirm(`Pulihkan data ${camaba.name} (${camaba.reg_no})?`)) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }

                return false;
            };

            window.confirmForceDeletePembayaran = function(event, camaba) {
                const form = event.target;
                if (form.dataset.confirmed === 'true') {
                    return true;
                }
                event.preventDefault();

                if (typeof Swal !== 'undefined') {
                    const relationNote = camaba.has_relations
                        ? `<div class="text-[11px] text-rose-800 bg-rose-50 border border-rose-200 rounded-lg px-2.5 py-2">
                               <strong>Perhatian Integritas Keuangan:</strong> Data ini memiliki ${esc(camaba.trx_count)} transaksi dan ${esc(camaba.voucher_count)} riwayat voucher. Sistem akan menolak penghapusan permanen demi menjaga histori keuangan.
                           </div>`
                        : `<div class="text-[11px] text-rose-800 bg-rose-50 border border-rose-200 rounded-lg px-2.5 py-2">
                               <strong>TINDAKAN TIDAK DAPAT DIBATALKAN:</strong> Data akan dihapus permanen dari database dan tidak dapat dipulihkan lagi.
                           </div>`;

                    Swal.fire({
                        title: 'Hapus Data Secara Permanen?',
                        html: `
                            <div class="text-left text-xs space-y-2 mt-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-1">
                                    <div><span class="text-slate-400">Nama CAMABA:</span> <strong class="text-slate-900">${esc(camaba.name)}</strong></div>
                                    <div><span class="text-slate-400">No. Pendaftaran:</span> <strong class="font-mono text-[#0b3d82]">${esc(camaba.reg_no)}</strong></div>
                                    <div><span class="text-slate-400">Program Studi:</span> <strong class="text-slate-800">${esc(camaba.prodi)}</strong></div>
                                </div>
                                ${relationNote}
                            </div>
                        `,
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#be123c',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Ya, Saya Mengerti & Hapus Permanen',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        customClass: {
                            popup: 'rounded-2xl shadow-2xl border border-rose-200 p-6',
                            title: 'text-rose-900 font-bold text-lg',
                            confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5 shadow-xs',
                            cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = 'true';
                            form.submit();
                        }
                    });
                } else if (confirm(`PERINGATAN: Data ${camaba.name} (${camaba.reg_no}) akan dihapus PERMANEN dan tidak dapat dipulihkan! Lanjutkan?`)) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }

                return false;
            };
        })();
    </script>
</x-app-layout>

