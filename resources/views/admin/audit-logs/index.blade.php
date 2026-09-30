@php
    $pageTitle = 'Audit Log Aktivitas';
    $pageSubtitle = 'Rekam Jejak Keamanan & Transaksi Data CRM UCIC';
@endphp

<x-app-layout :title="'Audit Logs - CRM UCIC'">

    <div class="space-y-6" x-data="{
        logSearch: '',
        currentPage: 1,
        perPage: 25,
        logsList: {{ json_encode($logs) }},
        get filteredLogs() {
            if (!this.logSearch) return this.logsList;
            return this.logsList.filter(l => 
                l.user.toLowerCase().includes(this.logSearch.toLowerCase()) || 
                l.action.toLowerCase().includes(this.logSearch.toLowerCase()) || 
                l.target.toLowerCase().includes(this.logSearch.toLowerCase())
            );
        },
        get paginatedLogs() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredLogs.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filteredLogs.length / this.perPage) || 1;
        }
    }" x-effect="logSearch; currentPage = 1">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Audit Trail & Security Logs</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Transparansi dan riwayat keamanan seluruh interaksi data prospek, takeover, dan ekspor sistem.</p>
            </div>
            <div>
                <button 
                    type="button" 
                    @click="$store.crm.showToast('Audit log berhasil diunduh sebagai file CSV')"
                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center gap-1.5 bg-white cursor-pointer"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    <span>Download Log CSV</span>
                </button>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="crm-card bg-white p-4">
            <div class="relative">
                <input 
                    type="text" 
                    x-model="logSearch"
                    placeholder="Cari nama personil, jenis aksi, atau nama prospek terkait..." 
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 text-xs sm:text-sm transition bg-slate-50/50"
                >
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Pengguna</th>
                            <th class="py-3.5 px-3">Role</th>
                            <th class="py-3.5 px-3">Aktivitas / Event</th>
                            <th class="py-3.5 px-3">Target</th>
                            <th class="py-3.5 px-3">Rincian Perubahan</th>
                            <th class="py-3.5 px-3">Alamat IP</th>
                            <th class="py-3.5 px-4 text-right">Waktu Eksekusi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(log, index) in paginatedLogs" :key="log.id + '-' + currentPage">
                            <tr class="hover:bg-slate-50/80 transition crm-table-slide">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-3.5 px-4 font-bold text-slate-900" x-text="log.user"></td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700" x-text="log.role"></span>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-purple-700" x-text="log.action"></td>
                                <td class="py-3.5 px-3 font-medium text-slate-900" x-text="log.target"></td>
                                <td class="py-3.5 px-3 text-slate-500 text-[11px]" x-text="log.detail"></td>
                                <td class="py-3.5 px-3 font-mono text-[11px] text-slate-400" x-text="log.ip"></td>
                                <td class="py-3.5 px-4 text-right text-slate-500 text-[11px]" x-text="log.time"></td>
                            </tr>
                        </template>
                        <tr x-show="filteredLogs.length === 0">
                            <td colspan="8" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada log aktivitas yang cocok.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Control -->
            <x-table-pagination
                total="filteredLogs.length"
                page="currentPage"
                perPage="perPage"
                totalPages="totalPages"
                color="purple"
            />
        </div>

    </div>

</x-app-layout>
