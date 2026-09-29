@extends('layouts.app', ['title' => 'Buat Tagihan Baru'])

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex items-center justify-between bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Buat Tagihan (Invoice) Baru</h1>
            <p class="text-xs text-slate-500 mt-1">Daftarkan tagihan baru untuk diproses pembayarannya oleh pelanggan/prospek.</p>
        </div>
        <a href="{{ route('payments.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
            Kembali
        </a>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-2xl space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Periksa kembali data isian formulir:
            </div>
            <ul class="list-disc list-inside ml-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <form action="{{ route('payments.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Nama Pelanggan / Siswa / Mitra <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Misal: Budi Santoso" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nomor WhatsApp / Telepon
                    </label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="081234567890" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nominal Tagihan (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="amount" value="{{ old('amount', 500000) }}" min="1000" step="1000" placeholder="500000" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500" required>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tanggal Jatuh Tempo <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="due_date" value="{{ old('due_date', now()->addDays(7)->toDateString()) }}" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Kaitkan dengan Prospek (Opsional)
                    </label>
                    <select name="prospek_id" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Tanpa Relasi Prospek --</option>
                        @foreach($prospeks as $p)
                            <option value="{{ $p->id }}" {{ old('prospek_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->status }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Catatan / Rincian Tagihan
                </label>
                <textarea name="notes" rows="3" placeholder="Misal: Pembayaran Biaya Pendaftaran / Kursus Gelombang 1" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <a href="{{ route('payments.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow-xs transition">
                    Simpan & Buat Tagihan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
