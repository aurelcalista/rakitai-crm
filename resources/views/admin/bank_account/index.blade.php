@php
    $pageTitle = 'Master Rekening Bank';
    $pageSubtitle = 'Kelola rekening bank institusi untuk penerimaan transfer pembayaran calon mahasiswa';
@endphp

<x-app-layout :title="'Rekening Bank - Admin CRM UCIC'">
    <div class="space-y-6" x-data="{
        modalAdd: false,
        modalEdit: false,
        modalDelete: false,
        selectedBank: { id: null, bank_name: '', account_number: '', account_name: '', notes: '', is_active: 1 },
        deleteBank: null,
        searchQuery: '',
        filterStatus: 'all',
        currentPage: 1,
        perPage: 10,
        copiedId: null,
        bankAccounts: {{ json_encode($bankAccounts) }},

        get filtered() {
            return this.bankAccounts.filter(item => {
                const q = (this.searchQuery || '').toLowerCase().trim();
                const matchQ = !q || 
                    (item.bank_name && item.bank_name.toLowerCase().includes(q)) ||
                    (item.account_number && item.account_number.toLowerCase().includes(q)) ||
                    (item.account_name && item.account_name.toLowerCase().includes(q)) ||
                    (item.notes && item.notes.toLowerCase().includes(q));

                let matchStatus = true;
                if (this.filterStatus === 'aktif') {
                    matchStatus = item.is_active === true || item.is_active === 1 || item.is_active === '1';
                } else if (this.filterStatus === 'nonaktif') {
                    matchStatus = item.is_active === false || item.is_active === 0 || item.is_active === '0';
                }

                return matchQ && matchStatus;
            });
        },

        get paginated() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },

        get totalPages() {
            return Math.ceil(this.filtered.length / this.perPage) || 1;
        },

        openAdd() {
            this.modalAdd = true;
        },

        openEdit(bank) {
            this.selectedBank = {
                id: bank.id,
                bank_name: bank.bank_name || '',
                account_number: bank.account_number || '',
                account_name: bank.account_name || '',
                notes: bank.notes || '',
                is_active: bank.is_active ? 1 : 0
            };
            this.modalEdit = true;
        },

        openDelete(bank) {
            this.deleteBank = bank;
            this.modalDelete = true;
        },

        copyText(text, id) {
            if (!text) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text);
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                textArea.remove();
            }
            this.copiedId = id;
            setTimeout(() => {
                if (this.copiedId === id) this.copiedId = null;
            }, 2000);
        }
    }" x-effect="searchQuery; filterStatus; currentPage = 1">

        {{-- Header Card --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Master Rekening Bank</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola rekening bank institusi untuk transfer pembayaran formulir dan termin PMB.</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="openAdd()" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer active:scale-95">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Rekening</span>
                </button>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="px-5 py-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center gap-2.5 shadow-2xs animate-fade-in">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="px-5 py-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-semibold flex items-center gap-2.5 shadow-2xs animate-fade-in">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="px-5 py-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-2xs space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Terjadi kesalahan input:
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] font-normal pl-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Total Rekening</div>
                    <div class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-0.5" x-text="bankAccounts.length">{{ $bankAccounts->count() }}</div>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Rekening Aktif</div>
                    <div class="text-xl sm:text-2xl font-extrabold text-emerald-600 mt-0.5" x-text="bankAccounts.filter(b => b.is_active).length">{{ $bankAccounts->where('is_active', true)->count() }}</div>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center border border-slate-200 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Nonaktif / Arsip</div>
                    <div class="text-xl sm:text-2xl font-extrabold text-slate-700 mt-0.5" x-text="bankAccounts.filter(b => !b.is_active).length">{{ $bankAccounts->where('is_active', false)->count() }}</div>
                </div>
            </div>
        </div>

        {{-- Filters & Search --}}
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" x-model="searchQuery" placeholder="Cari nama bank, no. rekening, pemilik (A/N)..." class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
            </div>
            <select x-model="filterStatus" class="text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 sm:w-44">
                <option value="all">Semua Status</option>
                <option value="aktif">Status: Aktif</option>
                <option value="nonaktif">Status: Nonaktif</option>
            </select>
        </div>

        {{-- Table Container --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Rekening Bank</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Menampilkan rekening yang dapat dipilih oleh Sales saat input prospek pembayaran.</p>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 text-[11px] font-bold border border-purple-100" x-text="filtered.length + ' Rekening'"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-bold uppercase tracking-wider text-[11px] border-b border-slate-100">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Bank</th>
                            <th class="py-3.5 px-4">Nomor Rekening</th>
                            <th class="py-3.5 px-4">Atas Nama (A/N)</th>
                            <th class="py-3.5 px-4">Catatan</th>
                            <th class="py-3.5 px-4 text-center w-28">Status</th>
                            <th class="py-3.5 px-4 text-center w-48">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(rek, index) in paginated" :key="rek.id">
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-purple-700 border border-slate-200 flex items-center justify-center font-black text-[10px] shrink-0 uppercase tracking-tighter" x-text="rek.bank_name ? rek.bank_name.substring(0, 3) : 'BNK'"></div>
                                        <span class="text-sm font-bold text-slate-900" x-text="rek.bank_name"></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="inline-flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200/80 group">
                                        <span class="font-mono font-bold text-slate-800 text-xs select-all tracking-wider" x-text="rek.account_number"></span>
                                        <button type="button" @click="copyText(rek.account_number, rek.id)" class="text-slate-400 hover:text-purple-600 transition p-0.5 rounded cursor-pointer" :title="copiedId === rek.id ? 'Tersalin!' : 'Salin Nomor'">
                                            <template x-if="copiedId === rek.id">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                            <template x-if="copiedId !== rek.id">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </template>
                                        </button>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-800" x-text="rek.account_name"></td>
                                <td class="py-3.5 px-4 text-slate-500 max-w-xs">
                                    <span class="truncate block" x-text="rek.notes || '-'"></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                        :class="rek.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="rek.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                                        <span x-text="rek.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- Edit Button --}}
                                        <button type="button"
                                            @click="openEdit(rek)"
                                            class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer active:scale-95"
                                            title="Edit Rekening">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit</span>
                                        </button>

                                        {{-- Toggle Status Button --}}
                                        <form :action="'{{ url('admin/bank-accounts') }}/' + rek.id + '/toggle-status'" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="px-2.5 py-1.5 rounded-lg border text-[11px] font-bold transition flex items-center gap-1 cursor-pointer active:scale-95"
                                                :class="rek.is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200'"
                                                :title="rek.is_active ? 'Nonaktifkan Rekening' : 'Aktifkan Rekening'">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                                <span x-text="rek.is_active ? 'Nonaktifkan' : 'Aktifkan'"></span>
                                            </button>
                                        </form>

                                        {{-- Delete Button --}}
                                        <button type="button"
                                            @click="openDelete(rek)"
                                            class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer active:scale-95"
                                            title="Hapus Rekening">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        {{-- Empty State --}}
                        <tr x-show="filtered.length === 0">
                            <td colspan="7" class="py-14 text-center">
                                <div class="max-w-xs mx-auto text-center space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 mx-auto flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">Tidak ada rekening bank ditemukan</p>
                                    <p class="text-xs text-slate-500">Coba ubah kata kunci pencarian atau tambah rekening baru.</p>
                                    <button type="button" @click="openAdd()" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition shadow-xs">
                                        Tambah Rekening Sekarang
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Control -->
            <x-table-pagination
                total="filtered.length"
                page="currentPage"
                perPage="perPage"
                totalPages="totalPages"
                color="purple"
            />
        </div>

        {{-- MODAL TAMBAH REKENING --}}
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalAdd = false"></div>
                
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10 border border-slate-100 animate-scale-up">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">Tambah Rekening Bank</h3>
                        </div>
                        <button type="button" @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form action="{{ route('admin.bank-accounts.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Bank <span class="text-rose-500">*</span></label>
                            <input type="text" name="bank_name" list="bankListSuggestions" required placeholder="Contoh: Bank Mandiri, BCA, BNI..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
                            <datalist id="bankListSuggestions">
                                <option value="Bank Mandiri">
                                <option value="BCA">
                                <option value="BNI">
                                <option value="BRI">
                                <option value="BSI (Bank Syariah Indonesia)">
                                <option value="Bank Danamon">
                                <option value="CIMB Niaga">
                                <option value="Permata Bank">
                                <option value="Bank BTN">
                                <option value="Bank Jabar Banten (BJB)">
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Rekening <span class="text-rose-500">*</span></label>
                            <input type="text" name="account_number" required placeholder="Contoh: 1380010015599" class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Atas Nama (A/N) <span class="text-rose-500">*</span></label>
                            <input type="text" name="account_name" required placeholder="Contoh: Universitas Catur Insan Cendekia" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Rekening <span class="text-rose-500">*</span></label>
                            <select name="is_active" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
                                <option value="1">Aktif (Dapat dipilih Sales)</option>
                                <option value="0">Nonaktif (Disembunyikan)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Keterangan (Opsional)</label>
                            <textarea name="notes" rows="2" placeholder="Contoh: Rekening utama transfer formulir & registrasi PMB UCIC..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition"></textarea>
                        </div>

                        <div class="flex gap-2 justify-end pt-3 border-t border-slate-100">
                            <button type="button" @click="modalAdd = false" class="px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl bg-purple-600 hover:bg-purple-700 text-white shadow-xs transition cursor-pointer active:scale-95">Simpan Rekening</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL EDIT REKENING --}}
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalEdit = false"></div>
                
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10 border border-slate-100 animate-scale-up">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">Edit Rekening Bank</h3>
                        </div>
                        <button type="button" @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form :action="'{{ url('admin/bank-accounts') }}/' + (selectedBank ? selectedBank.id : '')" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Bank <span class="text-rose-500">*</span></label>
                            <input type="text" name="bank_name" list="bankListSuggestions" x-model="selectedBank.bank_name" required placeholder="Contoh: Bank Mandiri" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Rekening <span class="text-rose-500">*</span></label>
                            <input type="text" name="account_number" x-model="selectedBank.account_number" required placeholder="Nomor rekening" class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Atas Nama (A/N) <span class="text-rose-500">*</span></label>
                            <input type="text" name="account_name" x-model="selectedBank.account_name" required placeholder="Nama pemilik rekening" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Rekening <span class="text-rose-500">*</span></label>
                            <select name="is_active" x-model="selectedBank.is_active" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition">
                                <option :value="1">Aktif (Dapat dipilih Sales)</option>
                                <option :value="0">Nonaktif (Disembunyikan)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Keterangan (Opsional)</label>
                            <textarea name="notes" rows="2" x-model="selectedBank.notes" placeholder="Informasi tambahan..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition"></textarea>
                        </div>

                        <div class="flex gap-2 justify-end pt-3 border-t border-slate-100">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition cursor-pointer active:scale-95">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL KONFIRMASI HAPUS --}}
        <div x-show="modalDelete" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalDelete = false"></div>
                
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10 border border-slate-100 animate-scale-up text-center">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Rekening Bank?</h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Apakah Anda yakin ingin menghapus rekening <strong class="text-slate-800" x-text="deleteBank ? deleteBank.bank_name + ' (' + deleteBank.account_number + ')' : ''"></strong>? Tindakan ini tidak dapat dibatalkan.
                    </p>

                    <form :action="'{{ url('admin/bank-accounts') }}/' + (deleteBank ? deleteBank.id : '')" method="POST" class="flex gap-2 justify-center">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="modalDelete = false" class="flex-1 px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition cursor-pointer active:scale-95">Ya, Hapus</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
