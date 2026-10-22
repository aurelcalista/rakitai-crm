@php
    $pageTitle = 'Dashboard PMB';
    $pageSubtitle = 'Statistik & Monitoring Administrasi Penerimaan Mahasiswa Baru UCIC';
@endphp

<x-app-layout :title="'Dashboard PMB - CRM UCIC'">
    <div class="space-y-6">

        <!-- Top Welcome & Quick Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        Admisi PMB UCIC
                    </span>
                    <span class="text-xs text-slate-500">• Tahun Akademik 2026/2027</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1.5">
                    Monitoring Pendaftaran & Keuangan PMB
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Ringkasan performa calon mahasiswa baru, berkas, status ujian, dan verifikasi transaksi.
                </p>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <a 
                    href="{{ route('pmb.pendaftar.index') }}" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Data Pendaftar</span>
                </a>
                <a 
                    href="{{ route('pmb.pembayaran.index') }}" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-xs transition cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span>Data Pembayaran</span>
                </a>
            </div>
        </div>

        <!-- Section 1: Statistik Pendaftar (Matching PMB Screenshot) -->
        <div>
            <div class="flex items-center justify-between mb-3 px-1">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Statistik Calon Mahasiswa</h3>
                <span class="text-xs text-blue-600 font-semibold">Real-time DB</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total User -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($totalPendaftar) }}</div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">TOTAL USER</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 2: Belum Isi Formulir -->
                <div class="bg-white p-5 rounded-2xl border border-rose-100/90 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-rose-600">{{ number_format($belumIsiForm) }}</div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">BELUM ISI FORMULIR</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 3: Belum Upload Berkas -->
                <div class="bg-white p-5 rounded-2xl border border-amber-100/90 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-600">{{ number_format($belumUploadBerkas) }}</div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">BELUM UPLOAD</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                </div>

                <!-- Card 4: Sudah Upload Berkas -->
                <div class="bg-white p-5 rounded-2xl border border-emerald-100/90 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-600">{{ number_format($sudahUploadBerkas) }}</div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">SUDAH UPLOAD</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Statistik Pembayaran (Matching PMB Screenshot) -->
        <div>
            <div class="flex items-center justify-between mb-3 px-1">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Statistik Keuangan & Pembayaran</h3>
                <span class="text-xs text-blue-600 font-semibold">Nominal Aktual</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Diterima -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900">Rp {{ number_format($totalNominalDiterima, 0, ',', '.') }}</div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">TOTAL DITERIMA</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 2: Berhasil -->
                <div class="bg-white p-5 rounded-2xl border border-emerald-100/90 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-600">{{ number_format($countBerhasil) }}</div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">BERHASIL</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Card 3: Pending -->
                <div class="bg-white p-5 rounded-2xl border border-amber-100/90 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-600">{{ number_format($countPending) }}</div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">PENDING</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 4: Gagal / Batal -->
                <div class="bg-white p-5 rounded-2xl border border-rose-100/90 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-rose-600">{{ number_format($countGagal) }}</div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">GAGAL / BATAL</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Program Studi & Gelombang Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Program Studi Peminatan -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Peminatan Program Studi</h4>
                        <p class="text-[11px] text-slate-500">Jumlah pendaftar per program studi</p>
                    </div>
                    <a href="{{ route('pmb.pendaftar.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Lihat Semua →</a>
                </div>
                <div class="space-y-3">
                    @forelse($prodiStats as $p)
                        @php
                            $pct = $totalPendaftar > 0 ? round(($p->prospeks_count / $totalPendaftar) * 100, 1) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-semibold text-slate-800">{{ $p->nama }}</span>
                                <span class="font-bold text-slate-900">{{ $p->prospeks_count }} <span class="text-slate-400 font-normal">({{ $pct }}%)</span></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada data program studi.</p>
                    @endforelse
                </div>
            </div>

            <!-- Gelombang Pendaftaran -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Distribusi Gelombang Pendaftaran</h4>
                        <p class="text-[11px] text-slate-500">Sebaran pendaftar berdasarkan gelombang</p>
                    </div>
                    <a href="{{ route('pmb.pendaftar.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Lihat Semua →</a>
                </div>
                <div class="space-y-3">
                    @forelse($gelombangStats as $g)
                        @php
                            $pct = $totalPendaftar > 0 ? round(($g->total / $totalPendaftar) * 100, 1) : 0;
                        @endphp
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                <span class="font-bold text-xs text-slate-800">{{ $g->gelombang ?: 'Tanpa Gelombang' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-black text-slate-900">{{ $g->total }}</span>
                                <span class="text-[11px] text-slate-500 ml-1">pendaftar ({{ $pct }}%)</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada data gelombang.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Section 4: Tabel Pendaftar & Transaksi Terbaru -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Pendaftar Terbaru -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Pendaftar Terbaru</h4>
                        <p class="text-[11px] text-slate-500">Calon mahasiswa yang baru mendaftar</p>
                    </div>
                    <a href="{{ route('pmb.pendaftar.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Ke Tabel Pendaftar →</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentPendaftar as $p)
                        <div class="p-4 hover:bg-slate-50/80 transition flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('pmb.pendaftar.show', $p->id) }}" class="font-bold text-xs text-slate-900 hover:text-blue-600 truncate block">
                                    {{ $p->name }}
                                </a>
                                <div class="text-[11px] text-slate-500 truncate">{{ $p->email ?: $p->whatsapp }}</div>
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $p->status_formulir === 'Sudah Isi' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ $p->status_formulir ?? 'Belum Isi' }}
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700">
                                        {{ $p->prodi?->nama ?? '- Belum Pilih' }}
                                    </span>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-[10px] text-slate-400">{{ $p->created_at ? $p->created_at->format('d M Y') : '-' }}</div>
                                <a href="{{ route('pmb.pendaftar.show', $p->id) }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 hover:text-blue-800 mt-1">
                                    Detail
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">Belum ada pendaftar terbaru.</div>
                    @endforelse
                </div>
            </div>

            <!-- Transaksi Pembayaran Terbaru -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Pembayaran Terbaru</h4>
                        <p class="text-[11px] text-slate-500">Aktivitas pembayaran PMB</p>
                    </div>
                    <a href="{{ route('pmb.pembayaran.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Ke Tabel Pembayaran →</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentTransaksi as $t)
                        <div class="p-4 hover:bg-slate-50/80 transition flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-mono text-[11px] font-bold text-slate-800 truncate">
                                    {{ $t->order_id ?: ('TRX-' . $t->id) }}
                                </div>
                                <div class="text-xs font-bold text-slate-900 truncate mt-0.5">{{ $t->prospek?->name ?? 'Pendaftar #' . $t->prospek_id }}</div>
                                <div class="text-[10px] text-slate-500 truncate">{{ $t->detail_pembayaran ?: $t->jenis }}</div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-xs font-black text-slate-900">Rp {{ number_format($t->nominal, 0, ',', '.') }}</div>
                                <div class="mt-1">
                                    @php
                                        $pStatus = strtolower($t->payment_status ?? 'pending');
                                    @endphp
                                    @if(in_array($pStatus, ['verified', 'paid']))
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Paid
                                        </span>
                                    @elseif(in_array($pStatus, ['rejected', 'failed', 'batal']))
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Gagal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Pending
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">Belum ada transaksi pembayaran.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
