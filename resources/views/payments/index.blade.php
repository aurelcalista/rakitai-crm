@extends('layouts.app', ['title' => 'Tagihan & Pembayaran'])

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Tagihan & Pembayaran</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                    Modul CS & Finance
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Kelola data invoice tagihan, simulasi transaksi QRIS/VA, dan pantau status pembayaran.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('payments.history') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Riwayat Transaksi
            </a>
            @if($isCsOrAdmin)
                <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow-xs transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat Tagihan Baru
                </a>
            @endif
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif
    @if($errors->any())
        <x-alert type="error" title="Validasi Gagal" :message="$errors->first()" />
    @endif

    <!-- Filter Card -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('payments.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Cari Tagihan</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="No invoice / nama / telepon..." class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending (Menunggu)</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid (Lunas)</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed (Gagal)</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired (Kedaluwarsa)</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Jatuh Tempo</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
                    Filter
                </button>
                <a href="{{ route('payments.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                    <tr>
                        <th class="py-3.5 px-4">Nomor Invoice</th>
                        <th class="py-3.5 px-4">Nama Pelanggan</th>
                        <th class="py-3.5 px-4">Nominal</th>
                        <th class="py-3.5 px-4">Jatuh Tempo</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Transaksi Terakhir</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4 font-bold font-mono text-slate-900">
                                {{ $inv->invoice_number }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $inv->customer_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $inv->customer_phone ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-900">
                                Rp {{ number_format($inv->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $inv->due_date->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($inv->status === 'paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Lunas (PAID)
                                    </span>
                                @elseif($inv->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Pending
                                    </span>
                                @elseif($inv->status === 'failed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Gagal
                                    </span>
                                @elseif($inv->status === 'expired')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Kedaluwarsa
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($inv->latestPayment)
                                    <div class="font-mono text-[11px] text-slate-700">{{ $inv->latestPayment->transaction_id }}</div>
                                    <span class="text-[10px] uppercase font-semibold text-slate-400">{{ $inv->latestPayment->payment_method }}</span>
                                @else
                                    <span class="text-slate-400 text-[11px]">Belum ada</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('payments.show', $inv->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                                    Lihat Detail
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                Belum ada tagihan. Silakan buat tagihan baru untuk memulai transaksi simulasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
