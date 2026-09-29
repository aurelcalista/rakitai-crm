@extends('layouts.app', ['title' => 'Detail Tagihan ' . $invoice->invoice_number])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold font-mono text-slate-900 tracking-tight">{{ $invoice->invoice_number }}</h1>
                @if($invoice->status === 'paid')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Lunas (PAID)
                    </span>
                @elseif($invoice->status === 'pending')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Pending
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ strtoupper($invoice->status) }}
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">Dibuat pada {{ $invoice->created_at->format('d M Y, H:i') }} WIB oleh {{ $invoice->creator?->name ?? 'Sistem' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('payments.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                Kembali ke Daftar Tagihan
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

    <!-- Invoice Details Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Informasi Tagihan</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block text-[10px] font-semibold uppercase">Nama Pelanggan</span>
                <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $invoice->customer_name }}</span>
                <span class="text-slate-500 text-[11px]">{{ $invoice->customer_phone ?? 'Tidak ada nomor telp' }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block text-[10px] font-semibold uppercase">Total Tagihan</span>
                <span class="font-bold text-slate-900 text-sm mt-0.5 block">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                <span class="text-slate-500 text-[11px]">Jatuh Tempo: {{ $invoice->due_date->format('d M Y') }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block text-[10px] font-semibold uppercase">Catatan / Prospek</span>
                <span class="font-medium text-slate-800 block mt-0.5">{{ $invoice->notes ?: '-' }}</span>
                @if($invoice->prospek)
                    <span class="inline-block mt-1 text-[11px] text-blue-600 font-semibold">Prospek: {{ $invoice->prospek->name }}</span>
                @endif
            </div>
        </div>
    </div>

    @if($invoice->status !== 'paid')
        <!-- Initiate New Payment Attempt -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Mulai Pembayaran Baru (Simulasi)</h2>
            <form action="{{ route('payments.create-payment', $invoice->id) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Pilih Metode Pembayaran</label>
                    <select name="payment_method" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 bg-slate-50/50" required>
                        <option value="qris">QRIS (Masa Berlaku 5 Menit Realtime)</option>
                        <option value="bank_transfer">Virtual Account / Bank Transfer</option>
                        <option value="e_wallet">E-Wallet (GoPay / OVO)</option>
                    </select>
                </div>
                <div class="sm:self-end">
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-xs transition cursor-pointer">
                        Buka Halaman Simulasi Pembayaran
                    </button>
                </div>
            </form>
            <p class="text-[11px] text-slate-400">
                Membuat transaksi pembayaran baru menghasilkan ID Transaksi unik dengan masa kedaluwarsa 5 menit untuk QRIS.
            </p>
        </div>
    @endif

    <!-- Payment Transactions History Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Riwayat Transaksi Pembayaran</h2>
            <span class="text-xs text-slate-400">{{ count($invoice->payments) }} Transaksi Tercatat</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                    <tr>
                        <th class="py-3 px-4">Transaction ID</th>
                        <th class="py-3 px-4">Metode</th>
                        <th class="py-3 px-4">Nominal</th>
                        <th class="py-3 px-4">Waktu Dibuat</th>
                        <th class="py-3 px-4">Masa Berlaku</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoice->payments as $pay)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-800">
                                {{ $pay->transaction_id }}
                            </td>
                            <td class="py-3 px-4 uppercase font-semibold text-slate-700">
                                {{ $pay->payment_method }}
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-900">
                                Rp {{ number_format($pay->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ $pay->created_at->format('d M, H:i:s') }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                @if($pay->expired_at)
                                    {{ $pay->expired_at->format('H:i:s') }}
                                    @if($pay->status === 'pending')
                                        <span class="text-[10px] text-amber-600 font-bold block">
                                            ({{ $pay->remaining_seconds }}s tersisa)
                                        </span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($pay->status === 'paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        PAID (Lunas)
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
                                    Buka Halaman Simulasi
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                Belum ada transaksi pembayaran. Silakan pilih metode pembayaran di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
