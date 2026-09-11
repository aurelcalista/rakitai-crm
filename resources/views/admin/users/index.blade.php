@php
    $pageTitle = 'Manajemen Pengguna & Tim';
    $pageSubtitle = 'Kelola Akun, Penugasan Role, dan Target Personil CRM UCIC';
@endphp

<x-app-layout :title="'Kelola Pengguna - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalUser: false,
        modalEdit: false,
        selectedUser: null,
        userRoleFilter: 'all',
        usersList: {{ json_encode($users) }},
        
        get filteredUsers() {
            if (this.userRoleFilter === 'all') return this.usersList;
            return this.usersList.filter(u => u.role.toLowerCase() === this.userRoleFilter.toLowerCase());
        }
    }">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Daftar Pengguna & Hak Akses</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Tambah akun personil baru, atur target bulanan sales, dan konfigurasi peran CRM.</p>
            </div>
            <div>
                <button 
                    type="button" 
                    @click="modalUser = true"
                    class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>+ Tambah Pengguna</span>
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="crm-card bg-white p-4 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-slate-400 font-semibold uppercase text-[11px]">Filter Role:</span>
                <button 
                    type="button" 
                    @click="userRoleFilter = 'all'"
                    :class="userRoleFilter === 'all' ? 'bg-purple-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition"
                >
                    Semua Role ({{ count($users) }})
                </button>
                <button 
                    type="button" 
                    @click="userRoleFilter = 'sales'"
                    :class="userRoleFilter === 'sales' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition"
                >
                    Sales
                </button>
                <button 
                    type="button" 
                    @click="userRoleFilter = 'cs'"
                    :class="userRoleFilter === 'cs' ? 'bg-teal-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition"
                >
                    CS
                </button>
                <button 
                    type="button" 
                    @click="userRoleFilter = 'supervisor'"
                    :class="userRoleFilter === 'supervisor' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition"
                >
                    Supervisor
                </button>
                <button 
                    type="button" 
                    @click="userRoleFilter = 'head marketing'"
                    :class="userRoleFilter === 'head marketing' ? 'bg-purple-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition"
                >
                    Head Marketing
                </button>
            </div>

            <div class="text-slate-400 font-medium text-[11px]">
                Menampilkan <span class="font-bold text-slate-700" x-text="filteredUsers.length"></span> akun
            </div>
        </div>

        <!-- Users Table (Desktop & Responsive) -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-4">Nama Pengguna</th>
                            <th class="py-3.5 px-3">Role Akses</th>
                            <th class="py-3.5 px-3">Kontak WhatsApp</th>
                            <th class="py-3.5 px-3 text-center">Target Bulanan</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-3">Login Terakhir</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="user in filteredUsers" :key="user.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xs shrink-0" x-text="user.avatar"></div>
                                        <div>
                                            <div class="font-bold text-slate-900" x-text="user.name"></div>
                                            <div class="text-[11px] text-slate-400 font-normal" x-text="user.email"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span 
                                        :class="{
                                            'bg-blue-50 text-blue-700 border-blue-200': user.role === 'Sales',
                                            'bg-teal-50 text-teal-800 border-teal-200': user.role === 'CS',
                                            'bg-indigo-50 text-indigo-700 border-indigo-200': user.role === 'Supervisor',
                                            'bg-purple-50 text-purple-700 border-purple-200': user.role === 'Head Marketing' || user.role === 'Admin'
                                        }"
                                        class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-bold border"
                                        x-text="user.role"
                                    ></span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-700 font-medium" x-text="user.phone"></td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800" x-text="user.target > 0 ? user.target + ' Prospek' : '-'"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400 text-[11px]" x-text="user.last_login"></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            @click="selectedUser = user; modalEdit = true"
                                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            @click="$store.crm.showToast('Link reset password berhasil dikirim ke ' + user.email)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                                            title="Reset Password"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL: TAMBAH PENGGUNA BARU -->
        <div x-show="modalUser" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="modalUser" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalUser = false"></div>

                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Akun Pengguna Baru</h3>
                        <button @click="modalUser = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form @submit.prevent="modalUser = false; $store.crm.showToast('Akun pengguna baru berhasil dibuat!')" class="mt-4 space-y-4 text-xs">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                            <input type="text" required placeholder="Contoh: Siti Rahmawati, S.Tr" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email UCIC *</label>
                                <input type="email" required placeholder="nama@cic.ac.id" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Role / Peran *</label>
                                <select class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                    <option value="Sales">Sales Inbound</option>
                                    <option value="CS">Customer Service</option>
                                    <option value="Supervisor">Supervisor (SPV)</option>
                                    <option value="Head Marketing">Head Marketing</option>
                                    <option value="Admin">Administrator</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp *</label>
                                <input type="tel" required placeholder="081234567890" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Target Closing (Jika Sales)</label>
                                <input type="number" placeholder="50" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Password Awal *</label>
                            <input type="password" required value="UCIC2026!#" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalUser = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white shadow-xs">Simpan Pengguna</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: EDIT PENGGUNA -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="modalEdit" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalEdit = false"></div>

                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Data Pengguna</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form @submit.prevent="modalEdit = false; $store.crm.showToast('Perubahan data pengguna berhasil disimpan!')" class="mt-4 space-y-4 text-xs">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pengguna</label>
                            <input type="text" :value="selectedUser ? selectedUser.name : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Role / Peran</label>
                                <select class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                    <option value="Sales">Sales Inbound</option>
                                    <option value="CS">Customer Service</option>
                                    <option value="Supervisor">Supervisor (SPV)</option>
                                    <option value="Head Marketing">Head Marketing</option>
                                    <option value="Admin">Administrator</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Akun</label>
                                <select class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white shadow-xs">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
