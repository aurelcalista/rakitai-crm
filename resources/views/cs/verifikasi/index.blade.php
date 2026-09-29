@php
    $pageTitle = 'Verifikasi Pembayaran';
    $pageSubtitle = 'Menunggu Konfirmasi CS';
@endphp

<x-app-layout :title="'Verifikasi Pembayaran - CRM UCIC'">

    <div class="space-y-6" x-data="{ rejectModal: false, rejectId: null, rejectReason: '' }">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    🔔 Verifikasi Pembayaran
                    @if($totalPending > 0)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            {{ $totalPending }} Menunggu
                        </span>
                    @endif
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Periksa dan verifikasi pembayaran dari Sales sebelum transaksi dinyatakan Closing.</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="px-5 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="px-5 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm font-semibold flex items-center gap-2">
                ℹ️ {{ session('info') }}
            </div>
        @endif
        @if($errors->any())
            <div class="px-5 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold">
                ❌ {{ $errors->first() }}
            </div>
        @endif

        {{-- Tabel Transaksi Pending --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Pembayaran Menunggu Verifikasi</h3>
                <span class="text-xs text-slate-500">{{ $transaksis->total() }} transaksi</span>
            </div>

            @if($transaksis->isEmpty())
                <div class="py-16 text-center">
                    <div class="text-4xl mb-3">✅</div>
                    <p class="text-sm font-semibold text-slate-700">Tidak ada pembayaran yang perlu diverifikasi</p>
                    <p class="text-xs text-slate-400 mt-1">Semua transaksi sudah diproses.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50/80 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Camaba / Prospek</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sales</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Jenis Transaksi</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nominal</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Metode Pembayaran</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($transaksis as $trx)
                                <tr class="hover:bg-slate-50/50 transition">
                                    {{-- Prospek --}}
                                    <td class="px-4 py-4">
                                        <a href="{{ route('prospek.show', $trx->prospek_id) }}" class="font-semibold text-slate-900 hover:text-blue-600 block text-xs">
                                            {{ $trx->prospek?->name ?? '-' }}
                                        </a>
                                        <span class="text-slate-400 text-[10px]">{{ $trx->prospek?->type ?? '' }}</span>
                                    </td>
                                    {{-- Sales --}}
                                    <td class="px-4 py-4">
                                        <span class="font-medium text-slate-700">{{ $trx->user?->name ?? '-' }}</span>
                                    </td>
                                    {{-- Jenis Transaksi --}}
                                    <td class="px-4 py-4">
                                        <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-100 font-semibold text-[10px]">
                                            {{ $trx->jenis }}
                                        </span>
                                    </td>
                                    {{-- Nominal --}}
                                    <td class="px-4 py-4 text-right font-bold text-slate-800">
                                        Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                    </td>
                                    {{-- Metode --}}
                                    <td class="px-4 py-4">
                                        @php
                                            $metodeIcons = [
                                                'virtual_account' => ['icon' => '🏦', 'label' => 'Virtual Account', 'color' => 'bg-blue-50 text-blue-700 border-blue-100'],
                                                'gopay'           => ['icon' => '💚', 'label' => 'GoPay', 'color' => 'bg-green-50 text-green-700 border-green-100'],
                                                'dana'            => ['icon' => '🔵', 'label' => 'DANA', 'color' => 'bg-sky-50 text-sky-700 border-sky-100'],
                                                'bank_transfer'   => ['icon' => '🏛️', 'label' => 'Transfer Bank Mandiri', 'color' => 'bg-amber-50 text-amber-700 border-amber-100'],
                                            ];
                                            $m = $metodeIcons[$trx->metode_pembayaran] ?? ['icon' => '💳', 'label' => $trx->metode_pembayaran, 'color' => 'bg-slate-50 text-slate-600 border-slate-100'];
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-md border font-semibold text-[10px] {{ $m['color'] }}">
                                            {{ $m['icon'] }} {{ $m['label'] }}
                                        </span>

                                        {{-- Info rekening Mandiri jika bank_transfer --}}
                                        @if($trx->metode_pembayaran === 'bank_transfer')
                                            @php
                                                $rekening = \App\Models\BankAccount::where('bank_name', 'Mandiri')->where('is_active', true)->first();
                                            @endphp
                                            @if($rekening)
                                                <div class="mt-1 text-[10px] text-slate-500 leading-tight">
                                                    <span class="font-medium">No. Rek:</span> {{ $rekening->account_number }}<br>
                                                    <span class="font-medium">A/N:</span> {{ $rekening->account_name }}
                                                </div>
                                            @else
                                                <div class="mt-1 text-[10px] text-amber-600">Rekening belum dikonfigurasi</div>
                                            @endif
                                        @endif
                                    </td>
                                    {{-- Tanggal --}}
                                    <td class="px-4 py-4 text-slate-600">
                                        {{ $trx->tanggal?->format('d/m/Y') }}
                                    </td>
                                    {{-- Aksi --}}
                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            {{-- Tombol Verifikasi --}}
                                            <form action="{{ route('cs.verifikasi.verify', $trx->id) }}" method="POST"
                                                  onsubmit="return confirm('Yakin memverifikasi pembayaran ini? Prospek akan dinyatakan LUNAS.')">
                                                @csrf
                                                <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold shadow-xs transition flex items-center gap-1 cursor-pointer">
                                                    ✅ Verifikasi
                                                </button>
                                            </form>

                                            {{-- Tombol Tolak --}}
                                            <button type="button"
                                                @click="rejectModal = true; rejectId = {{ $trx->id }}"
                                                class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-bold transition flex items-center gap-1 cursor-pointer">
                                                ❌ Tolak
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Catatan Sales --}}
                                @if($trx->notes)
                                    <tr class="bg-slate-50/30">
                                        <td colspan="7" class="px-4 py-2 text-[10px] text-slate-500 italic">
                                            💬 Catatan Sales: {{ $trx->notes }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($transaksis->hasPages())
                    <div class="px-5 py-4 border-t border-slate-100">
                        {{ $transaksis->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>

    {{-- MODAL TOLAK --}}
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="rejectModal = false; rejectId = null; rejectReason = ''"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">❌ Tolak Pembayaran</h3>
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
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan (opsional)</label>
                                <textarea name="rejection_reason" x-model="rejectReason" rows="3"
                                    placeholder="Contoh: Bukti transfer tidak valid, nominal tidak sesuai, dsb."
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition"></textarea>
                            </div>
                            <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                                <button type="button" @click="rejectModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-xs cursor-pointer"
                                    onclick="return confirm('Yakin menolak pembayaran ini?')">
                                    Tolak Pembayaran
                                </button>
                            </div>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

</x-app-layout>
