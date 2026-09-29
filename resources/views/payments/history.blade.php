@extends('layouts.app', ['title' => 'Riwayat Transaksi Pembayaran'])

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Riwayat Transaksi Pembayaran</h1>
            <p class="text-xs text-slate-500 mt-1">Daftar rekaman seluruh percobaan dan status transaksi pembayaran tagihan.</p>
        </div>
        <div>
            <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                Daftar Tagihan (Invoices)
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <!-- Filter Card -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('payments.history') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Cari Transaksi</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Transaction ID / invoice..." class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Metode Pembayaran</label>
                <select name="payment_method" class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
                    <option value="">Semua Metode</option>
                    <option value="qris" {{ request('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                    <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / VA</option>
                    <option value="e_wallet" {{ request('payment_method') === 'e_wallet' ? 'selected' : '' }}>E-Wallet</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Status Transaksi</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
                    <option value="">Semua Status</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid (Lunas)</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed (Gagal)</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired (Kedaluwarsa)</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
                    Filter
                </button>
                <a href="{{ route('payments.history') }}" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                    <tr>
                        <th class="py-3.5 px-4">Transaction ID</th>
                        <th class="py-3.5 px-4">Invoice & Pelanggan</th>
                        <th class="py-3.5 px-4">Nominal</th>
                        <th class="py-3.5 px-4">Metode</th>
                        <th class="py-3.5 px-4">Waktu Dibuat</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $pay)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-800">
                                {{ $pay->transaction_id }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 font-mono">{{ $pay->invoice?->invoice_number }}</div>
                                <div class="text-[11px] text-slate-500">{{ $pay->invoice?->customer_name }}</div>
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-900">
                                Rp {{ number_format($pay->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 font-semibold uppercase text-slate-600">
                                {{ $pay->payment_method }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ $pay->created_at->format('d M Y, H:i') }} WIB
                            </td>
                            <td class="py-3 px-4">
                                @if($pay->status === 'paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        PAID
                                    </span>
                                @elseif($pay->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        PENDING
                                    </span>
                                @elseif($pay->status === 'failed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        FAILED
                                    </span>
                                @elseif($pay->status === 'expired')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        EXPIRED
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('payments.simulate', $pay->transaction_id) }}" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                                    Lihat Simulasi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                Belum ada riwayat transaksi pembayaran.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
