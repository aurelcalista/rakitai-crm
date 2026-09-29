@php
    $pageTitle = 'Rekening Bank';
    $pageSubtitle = 'Master Data Rekening Bank Institusi';
@endphp

<x-app-layout :title="'Rekening Bank - Admin CRM UCIC'">
    <div class="space-y-6" x-data="{ modalAdd: false, modalEdit: false, editData: {}, modalDelete: false, deleteId: null }">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">🏦 Master Rekening Bank</h2>
                <p class="text-xs text-slate-500 mt-0.5">Kelola rekening bank institusi yang digunakan Sales untuk informasi transfer pembayaran.</p>
            </div>
            <button @click="modalAdd = true" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Tambah Rekening
            </button>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="px-5 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">✅ {{ session('success') }}</div>
        @endif

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Daftar Rekening Bank ({{ $bankAccounts->count() }})</h3>
            </div>

            @if($bankAccounts->isEmpty())
                <div class="py-16 text-center">
                    <div class="text-4xl mb-3">🏦</div>
                    <p class="text-sm font-semibold text-slate-700">Belum ada rekening bank yang ditambahkan.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase">Bank</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase">No. Rekening</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase">Atas Nama</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase">Catatan</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($bankAccounts as $rek)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-4 py-3 font-bold text-slate-800">{{ $rek->bank_name }}</td>
                                    <td class="px-4 py-3 font-mono text-slate-700">{{ $rek->account_number }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $rek->account_name }}</td>
                                    <td class="px-4 py-3 text-slate-500 max-w-xs truncate">{{ $rek->notes ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($rek->is_active)
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-bold">Aktif</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-bold">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1.5">
                                            {{-- Edit --}}
                                            <button type="button"
                                                @click="modalEdit = true; editData = {{ json_encode($rek) }}"
                                                class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-100 text-[10px] font-bold transition cursor-pointer">
                                                ✏️ Edit
                                            </button>
                                            {{-- Toggle --}}
                                            <form action="{{ route('admin.bank-accounts.toggle-status', $rek->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg {{ $rek->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 border-amber-100' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-100' }} border text-[10px] font-bold transition cursor-pointer">
                                                    {{ $rek->is_active ? '⏸ Nonaktifkan' : '▶ Aktifkan' }}
                                                </button>
                                            </form>
                                            {{-- Delete --}}
                                            <form action="{{ route('admin.bank-accounts.destroy', $rek->id) }}" method="POST"
                                                  onsubmit="return confirm('Yakin hapus rekening {{ $rek->bank_name }} - {{ $rek->account_number }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-100 text-[10px] font-bold transition cursor-pointer">
                                                    🗑 Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

    {{-- MODAL TAMBAH --}}
    <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">🏦 Tambah Rekening Bank</h3>
                    <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('admin.bank-accounts.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Bank *</label>
                        <input type="text" name="bank_name" required placeholder="Mandiri, BCA, BNI..." class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">No. Rekening *</label>
                        <input type="text" name="account_number" required placeholder="1234567890" class="w-full text-sm font-mono px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Atas Nama *</label>
                        <input type="text" name="account_name" required placeholder="Nama Pemilik Rekening" class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan (Opsional)</label>
                        <textarea name="notes" rows="2" placeholder="Informasi tambahan..." class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>
                    <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                        <button type="button" @click="modalAdd = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs cursor-pointer">Simpan Rekening</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">✏️ Edit Rekening Bank</h3>
                    <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
                </div>
                <template x-if="editData.id">
                    <form :action="`/admin/bank-accounts/${editData.id}`" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Bank *</label>
                            <input type="text" name="bank_name" :value="editData.bank_name" required class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">No. Rekening *</label>
                            <input type="text" name="account_number" :value="editData.account_number" required class="w-full text-sm font-mono px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Atas Nama *</label>
                            <input type="text" name="account_name" :value="editData.account_name" required class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan (Opsional)</label>
                            <textarea name="notes" rows="2" :value="editData.notes" class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                        </div>
                        <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs cursor-pointer">Simpan Perubahan</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

</x-app-layout>
