@php
    $pageTitle = 'Kelola Voucher Diskon';
    $pageSubtitle = 'Pengaturan Kode Voucher Potongan Biaya PMB & Rekapitulasi Pemakaian';
    $userRole = strtolower(auth()->user()->role ?? '');
@endphp

<x-app-layout :title="'Kelola Voucher Diskon PMB - CRM UCIC'">
    <div class="space-y-6" x-data="{
        activeTab: 'vouchers', // 'vouchers' atau 'usages'
        voucherSearch: '',
        voucherFilter: 'all', // 'all', 'active', 'depleted', 'inactive'
        usageSearch: '',
        formOpen: {{ ($errors->has('name') || $errors->has('code') || $errors->has('discount_amount') || $errors->has('quota') || $errors->has('is_active')) ? 'true' : 'false' }},
        voucherEditModalOpen: false,
        voucherUsersModalOpen: false,
        editingVoucher: { id: null, code: '', name: '', discount_amount: '', quota: 0, is_active: '1' },
        editingVoucherUrl: '',
        selectedVoucherForUsers: { code: '', name: '', discount_amount: 0, discount_formatted: '', quota: 0, used_quota: 0, remaining_quota: 0, is_active: true, usages: [] },
        formatRupiahInput(val) {
            if (val === null || val === undefined || val === '') return '';
            const clean = String(val).replace(/\D/g, '');
            return clean ? clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        },
        maskRupiah(event) {
            const raw = event.target.value.replace(/\D/g, '');
            event.target.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        },
        openEditVoucher(v) {
            this.editingVoucher = {
                id: v.id,
                code: v.code,
                name: v.name || '',
                discount_amount: this.formatRupiahInput(v.discount_amount),
                quota: v.quota,
                is_active: v.is_active ? '1' : '0'
            };
            this.editingVoucherUrl = '{{ url('pmb/pembayaran/vouchers') }}/' + v.id;
            this.voucherEditModalOpen = true;
        },
        openVoucherUsers(v) {
            this.selectedVoucherForUsers = v;
            this.voucherUsersModalOpen = true;
        },
        matchesVoucher(code, name, isActive, remainingQuota) {
            const q = this.voucherSearch.toLowerCase().trim();
            const matchesQuery = !q || (code && code.toLowerCase().includes(q)) || (name && name.toLowerCase().includes(q));
            let matchesStatus = true;
            if (this.voucherFilter === 'active') {
                matchesStatus = isActive && remainingQuota > 0;
            } else if (this.voucherFilter === 'depleted') {
                matchesStatus = isActive && remainingQuota <= 0;
            } else if (this.voucherFilter === 'inactive') {
                matchesStatus = !isActive;
            }
            return matchesQuery && matchesStatus;
        },
        matchesUsage(name, regNo, code) {
            const q = this.usageSearch.toLowerCase().trim();
            if (!q) return true;
            return (name && name.toLowerCase().includes(q)) ||
                   (regNo && regNo.toLowerCase().includes(q)) ||
                   (code && code.toLowerCase().includes(q));
        }
    }">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-xs sm:text-sm shadow-xs transition-all">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="font-bold">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 p-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center justify-between text-xs sm:text-sm shadow-xs transition-all">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <span class="font-bold">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 p-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl space-y-1 text-xs shadow-xs">
                <strong class="font-bold block">Terdapat kesalahan pengisian data voucher:</strong>
                <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header Card -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="{{ route('pmb.dashboard') }}" class="hover:text-blue-600 transition">PMB UCIC</a>
                    <span>/</span>
                    <a href="{{ route('pmb.pembayaran.index') }}" class="hover:text-blue-600 transition">Data Pembayaran</a>
                    <span>/</span>
                    <span class="text-slate-700">Kelola Voucher</span>
                </div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight font-display">
                    Kelola Voucher Diskon CAMABA
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Pengaturan kode voucher potongan DPP, batas kuota penggunaan, dan monitoring penerima diskon.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="formOpen = !formOpen"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
                    :class="formOpen ? 'bg-slate-200 text-slate-800 hover:bg-slate-300' : 'bg-[#0b3d82] text-white hover:bg-[#082a5c]'"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span x-text="formOpen ? 'Tutup Form Voucher' : '+ Buat Voucher Baru'">+ Buat Voucher Baru</span>
                </button>

                <a
                    href="{{ route('pmb.pembayaran.index') }}"
                    class="px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Ke Data Pembayaran</span>
                </a>
            </div>
        </div>

        <!-- 4 Metric Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            {{-- Card 1: Total Voucher --}}
            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs space-y-1">
                <div class="flex items-center justify-between text-slate-500 text-xs">
                    <span class="font-bold">Total Voucher</span>
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#0b3d82] flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display">{{ $totalVouchers }}</div>
                <div class="text-[11px] text-slate-400">Kode voucher terdaftar</div>
            </div>

            {{-- Card 2: Voucher Aktif --}}
            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs space-y-1">
                <div class="flex items-center justify-between text-slate-500 text-xs">
                    <span class="font-bold">Voucher Aktif</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-extrabold text-emerald-700 font-display">{{ $activeVouchersCount }}</div>
                <div class="text-[11px] text-emerald-600">Siap diklaim CAMABA</div>
            </div>

            {{-- Card 3: Total Pemakaian --}}
            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs space-y-1">
                <div class="flex items-center justify-between text-slate-500 text-xs">
                    <span class="font-bold">Kuota Terpakai</span>
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-extrabold text-indigo-700 font-display">{{ $totalUsagesCount }}</div>
                <div class="text-[11px] text-slate-400">CAMABA telah menggunakan</div>
            </div>

            {{-- Card 4: Akumulasi Potongan --}}
            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs space-y-1">
                <div class="flex items-center justify-between text-slate-500 text-xs">
                    <span class="font-bold">Total Potongan</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-extrabold text-amber-700 font-display">Rp {{ number_format($totalDiscountGiven, 0, ',', '.') }}</div>
                <div class="text-[11px] text-slate-400">Total nilai diskon diberikan</div>
            </div>
        </div>

        <!-- Tab Switcher: Daftar Voucher vs Log Pemakaian CAMABA -->
        <div class="flex items-center gap-2 border-b border-slate-200/80 pb-2">
            <button
                type="button"
                @click="activeTab = 'vouchers'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                :class="activeTab === 'vouchers' ? 'bg-[#0b3d82] text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200'"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span>Daftar Kode Voucher ({{ $totalVouchers }})</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'usages'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                :class="activeTab === 'usages' ? 'bg-[#0b3d82] text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200'"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Riwayat Pemakaian CAMABA ({{ $totalUsagesCount }})</span>
            </button>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 1: DAFTAR KODE VOUCHER & FORM PENGATURAN                   -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'vouchers'" class="space-y-4">

            {{-- Form Tambah Voucher Baru (Collapsible) --}}
            <div x-show="formOpen" x-cloak class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4 transition-all">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0b3d82] flex items-center justify-center font-bold">
                            +
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 font-display">Tambah Kode Voucher Baru</h3>
                            <p class="text-[11px] text-slate-500">Tentukan nama, kode unik, nominal potongan harga, dan batas kuota klaim.</p>
                        </div>
                    </div>
                    <button type="button" @click="formOpen = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('pmb.pembayaran.vouchers.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 items-end">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nama Voucher</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name') }}"
                               maxlength="150"
                               placeholder="Contoh: Beasiswa Prestasi Gelombang 1"
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Kode Voucher <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="code"
                               value="{{ old('code') }}"
                               required
                               placeholder="Contoh: UCIC300"
                               class="w-full text-xs font-bold uppercase rounded-xl border border-slate-200 px-3.5 py-2.5 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nominal Diskon (Rp) <span class="text-rose-500">*</span></label>
                        <input type="text"
                               inputmode="numeric"
                               name="discount_amount"
                               value="{{ old('discount_amount') ? number_format((int) preg_replace('/[^0-9]/', '', old('discount_amount')), 0, ',', '.') : '' }}"
                               @input="maskRupiah($event)"
                               required
                               placeholder="Contoh: 300.000"
                               class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Kuota Penggunaan <span class="text-rose-500">*</span></label>
                        <input type="number"
                               name="quota"
                               value="{{ old('quota') }}"
                               min="1"
                               step="1"
                               required
                               placeholder="Contoh: 20"
                               class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Status Voucher</label>
                        <select name="is_active" class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-white text-slate-800 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Langsung Aktif</option>
                            <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Nonaktif (Draft)</option>
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="w-full h-[42px] px-6 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs transition cursor-pointer flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Simpan Voucher</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Live Search & Filter Bar --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 p-3.5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="relative flex-1 max-w-md">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        x-model="voucherSearch"
                        placeholder="Cari kode atau nama voucher..."
                        class="w-full text-xs rounded-xl border border-slate-200 pl-9 pr-8 py-2 bg-slate-50 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition"
                    >
                    <button
                        type="button"
                        x-show="voucherSearch"
                        @click="voucherSearch = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold p-1 cursor-pointer"
                    >
                        &times;
                    </button>
                </div>

                {{-- Status Pills Filter --}}
                <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
                    <button
                        type="button"
                        @click="voucherFilter = 'all'"
                        class="px-3 py-1.5 rounded-lg transition cursor-pointer"
                        :class="voucherFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        @click="voucherFilter = 'active'"
                        class="px-3 py-1.5 rounded-lg transition cursor-pointer"
                        :class="voucherFilter === 'active' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200'"
                    >
                        Aktif
                    </button>
                    <button
                        type="button"
                        @click="voucherFilter = 'depleted'"
                        class="px-3 py-1.5 rounded-lg transition cursor-pointer"
                        :class="voucherFilter === 'depleted' ? 'bg-amber-600 text-white' : 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200'"
                    >
                        Kuota Habis
                    </button>
                    <button
                        type="button"
                        @click="voucherFilter = 'inactive'"
                        class="px-3 py-1.5 rounded-lg transition cursor-pointer"
                        :class="voucherFilter === 'inactive' ? 'bg-slate-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
                    >
                        Nonaktif
                    </button>
                </div>
            </div>

            {{-- Tabel Voucher --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-slate-600 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200/90">
                            <tr>
                                <th class="py-3 px-3.5">Kode Voucher</th>
                                <th class="py-3 px-3.5">Nama Voucher</th>
                                <th class="py-3 px-3.5">Potongan Diskon</th>
                                <th class="py-3 px-3.5 text-center">Total Kuota</th>
                                <th class="py-3 px-3.5 text-center">Terpakai</th>
                                <th class="py-3 px-3.5 text-center">Sisa Kuota</th>
                                <th class="py-3 px-3.5">Status</th>
                                <th class="py-3 px-3.5">Pengguna CAMABA</th>
                                <th class="py-3 px-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($vouchers as $v)
                                @php
                                    $vPayload = [
                                        'id'                   => $v->id,
                                        'code'                 => $v->code,
                                        'name'                 => $v->name ?? '',
                                        'discount_amount'      => (int) $v->discount_amount,
                                        'discount_formatted'   => 'Rp ' . number_format($v->discount_amount, 0, ',', '.'),
                                        'quota'                => (int) $v->quota,
                                        'used_quota'           => (int) $v->used_quota,
                                        'remaining_quota'      => (int) $v->remaining_quota,
                                        'is_active'            => (bool) $v->is_active,
                                        'usages'               => $v->usages->map(fn ($u) => [
                                            'camaba_name'          => $u->prospek?->name ?? '-',
                                            'no_registrasi'        => $u->prospek?->no_registrasi ?? '-',
                                            'voucher_code'         => $u->voucher_code,
                                            'voucher_name'         => $u->voucher_name ?? $v->name ?? '',
                                            'discount_amount'      => 'Rp ' . number_format($u->discount_amount, 0, ',', '.'),
                                            'total_before'         => 'Rp ' . number_format($u->total_before_discount ?? $u->dpp_before_discount, 0, ',', '.'),
                                            'total_after'          => 'Rp ' . number_format($u->total_after_discount ?? $u->dpp_after_discount, 0, ',', '.'),
                                            'status_label'         => $u->status_label,
                                            'is_used'              => $u->status === \App\Models\VoucherUsage::STATUS_USED,
                                            'time'                 => ($u->used_at ?? $u->created_at)?->format('d M Y, H:i'),
                                        ])->values()->all(),
                                    ];
                                @endphp
                                <tr
                                    x-show="matchesVoucher('{{ addslashes($v->code) }}', '{{ addslashes($v->name ?? '') }}', {{ $v->is_active ? 'true' : 'false' }}, {{ (int) $v->remaining_quota }})"
                                    class="hover:bg-slate-50/80 transition"
                                >
                                    <td class="py-3 px-3.5 font-mono font-extrabold text-[#0b3d82] text-xs">
                                        {{ $v->code }}
                                    </td>
                                    <td class="py-3 px-3.5 font-bold text-slate-800">
                                        {{ $v->name ?: '-' }}
                                    </td>
                                    <td class="py-3 px-3.5 font-bold text-emerald-700">
                                        Rp {{ number_format($v->discount_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3.5 text-center font-semibold text-slate-700">
                                        {{ $v->quota }}
                                    </td>
                                    <td class="py-3 px-3.5 text-center font-bold text-blue-700">
                                        {{ $v->used_quota }}
                                    </td>
                                    <td class="py-3 px-3.5 text-center">
                                        @if($v->remaining_quota <= 0)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Habis (0)</span>
                                        @else
                                            <span class="font-bold text-emerald-700">{{ $v->remaining_quota }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3.5">
                                        @if(!$v->is_active)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Nonaktif</span>
                                        @elseif($v->remaining_quota <= 0)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Kuota Habis</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3.5">
                                        <button
                                            type="button"
                                            @click="openVoucherUsers({{ json_encode($vPayload) }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-[#0b3d82] border border-blue-200 transition cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>Lihat CAMABA ({{ $v->usages->count() }})</span>
                                        </button>
                                    </td>
                                    <td class="py-3 px-3.5 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="openEditVoucher({{ json_encode($vPayload) }})"
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
                                            >
                                                Ubah
                                            </button>
                                            <form action="{{ route('pmb.pembayaran.vouchers.toggle', $v->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition cursor-pointer {{ $v->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200' }}"
                                                >
                                                    {{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-slate-500 text-xs">
                                        Belum ada kode voucher yang dibuat. Silakan klik tombol <strong>+ Buat Voucher Baru</strong> di atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: RIWAYAT PEMAKAIAN VOUCHER PER CAMABA                     -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'usages'" class="space-y-4">
            {{-- Search Bar Riwayat --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 p-3.5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="relative flex-1 max-w-md">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        x-model="usageSearch"
                        placeholder="Cari nama CAMABA, no. registrasi, atau kode voucher..."
                        class="w-full text-xs rounded-xl border border-slate-200 pl-9 pr-8 py-2 bg-slate-50 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition"
                    >
                    <button
                        type="button"
                        x-show="usageSearch"
                        @click="usageSearch = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold p-1 cursor-pointer"
                    >
                        &times;
                    </button>
                </div>

                <div class="text-xs text-slate-500 font-medium">
                    Total: <strong class="text-slate-900">{{ $totalUsagesCount }} CAMABA</strong> terdaftar penerima voucher diskon.
                </div>
            </div>

            {{-- Tabel Riwayat Pemakaian --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-slate-600 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200/90">
                            <tr>
                                <th class="py-3 px-3.5">Calon Mahasiswa</th>
                                <th class="py-3 px-3.5">Kode Voucher</th>
                                <th class="py-3 px-3.5">Nama Voucher</th>
                                <th class="py-3 px-3.5">Nominal Potongan</th>
                                <th class="py-3 px-3.5">Total Tagihan Awal</th>
                                <th class="py-3 px-3.5">Setelah Diskon</th>
                                <th class="py-3 px-3.5">Status Klaim</th>
                                <th class="py-3 px-3.5">Waktu Pemakaian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($voucherSummaryUsages as $vu)
                                @php
                                    $camabaName = $vu->prospek?->name ?? '-';
                                    $regNo = $vu->prospek?->no_registrasi ?? '-';
                                    $vCode = $vu->voucher_code;
                                @endphp
                                <tr
                                    x-show="matchesUsage('{{ addslashes($camabaName) }}', '{{ addslashes($regNo) }}', '{{ addslashes($vCode) }}')"
                                    class="hover:bg-slate-50/80 transition"
                                >
                                    <td class="py-3 px-3.5">
                                        <div class="font-bold text-slate-900">{{ $camabaName }}</div>
                                        <div class="font-mono text-[10px] text-slate-400">{{ $regNo }}</div>
                                    </td>
                                    <td class="py-3 px-3.5">
                                        <span class="px-2 py-0.5 rounded font-mono font-extrabold text-[11px] bg-blue-50 text-[#0b3d82] border border-blue-200">
                                            {{ $vu->voucher_code }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 font-semibold text-slate-700">
                                        {{ $vu->voucher_name ?? $vu->voucher?->name ?? '-' }}
                                    </td>
                                    <td class="py-3 px-3.5 font-bold text-emerald-700">
                                        -Rp {{ number_format($vu->discount_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3.5 text-slate-600">
                                        Rp {{ number_format($vu->total_before_discount ?? $vu->dpp_before_discount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3.5 font-bold text-[#0b3d82]">
                                        Rp {{ number_format($vu->total_after_discount ?? $vu->dpp_after_discount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $vu->status === \App\Models\VoucherUsage::STATUS_USED ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                            {{ $vu->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 text-slate-400 text-[11px]">
                                        {{ ($vu->used_at ?? $vu->created_at)?->format('d M Y, H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-500 text-xs">
                                        Belum ada CAMABA yang menggunakan voucher diskon.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL: Ubah Nominal Diskon, Kuota & Status Voucher             -->
        <!-- ============================================================== -->
        <div x-show="voucherEditModalOpen" x-cloak class="fixed inset-0 z-60 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="voucherEditModalOpen = false"></div>
                <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl max-w-md w-full border border-slate-100">
                    <form :action="editingVoucherUrl" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 font-display">Pengaturan Voucher</h3>
                                    <p class="text-xs text-slate-500">Kode: <strong class="font-mono text-[#0b3d82]" x-text="editingVoucher.code"></strong></p>
                                </div>
                                <button type="button" @click="voucherEditModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Voucher</label>
                                <input type="text" name="name" x-model="editingVoucher.name" maxlength="150" placeholder="Contoh: Beasiswa Gelombang 1"
                                       class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nominal Diskon (Rp) <span class="text-rose-500">*</span></label>
                                <input type="text"
                                       inputmode="numeric"
                                       name="discount_amount"
                                       x-model="editingVoucher.discount_amount"
                                       @input="editingVoucher.discount_amount = formatRupiahInput($event.target.value)"
                                       required
                                       placeholder="Contoh: 300.000"
                                       class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kuota Penggunaan <span class="text-rose-500">*</span></label>
                                <input type="number" name="quota" x-model="editingVoucher.quota" min="1" step="1" required
                                       class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Status Voucher</label>
                                <select name="is_active" x-model="editingVoucher.is_active"
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="bg-slate-50/80 px-6 py-3.5 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button type="button" @click="voucherEditModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-[#0b3d82] hover:bg-[#082a5c] rounded-xl cursor-pointer">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL: Daftar CAMABA Pengguna Voucher Tertentu                 -->
        <!-- ============================================================== -->
        <div x-show="voucherUsersModalOpen" x-cloak class="fixed inset-0 z-60 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="voucherUsersModalOpen = false"></div>
                <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl max-w-2xl w-full border border-slate-100">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 font-display">
                                Pengguna Voucher <span class="font-mono text-[#0b3d82]" x-text="selectedVoucherForUsers.code"></span>
                            </h3>
                            <p class="text-xs text-slate-500">
                                <span x-show="selectedVoucherForUsers.name" class="font-semibold text-slate-700" x-text="selectedVoucherForUsers.name + ' • '"></span>
                                Potongan: <strong class="text-emerald-700" x-text="selectedVoucherForUsers.discount_formatted"></strong> • Kuota: <strong x-text="selectedVoucherForUsers.used_quota + ' / ' + selectedVoucherForUsers.quota + ' Terpakai (Sisa ' + selectedVoucherForUsers.remaining_quota + ')'"></strong>
                            </p>
                        </div>
                        <button type="button" @click="voucherUsersModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-5 max-h-[60vh] overflow-y-auto space-y-2.5">
                        <template x-if="!selectedVoucherForUsers.usages || selectedVoucherForUsers.usages.length === 0">
                            <p class="text-xs text-slate-500 text-center py-6">Belum ada CAMABA yang menggunakan voucher ini.</p>
                        </template>
                        <template x-for="(u, i) in (selectedVoucherForUsers.usages || [])" :key="i">
                            <div class="p-3 rounded-xl border border-slate-200/80 bg-slate-50/60 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <div class="font-bold text-slate-900" x-text="u.camaba_name"></div>
                                    <div class="text-[11px] text-slate-500">
                                        <span class="font-mono" x-text="u.no_registrasi"></span> •
                                        Potongan <strong class="text-emerald-700" x-text="u.discount_amount"></strong>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        Total Awal: <strong x-text="u.total_before || u.dpp_before"></strong> &rarr; Setelah Diskon: <strong class="text-[#0b3d82]" x-text="u.total_after || u.dpp_after"></strong>
                                    </div>
                                </div>
                                <div class="sm:text-right shrink-0">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                          :class="u.is_used ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                          x-text="u.status_label"></span>
                                    <div class="text-[10px] text-slate-400 mt-1" x-text="u.time"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
