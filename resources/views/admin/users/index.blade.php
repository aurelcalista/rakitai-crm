@php $pageTitle = 'Kelola Pengguna'; @endphp

<x-app-layout :title="'Kelola Pengguna - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: false,
        modalEdit: false,
        modalDetail: false,
        selectedUser: null,
        searchQuery: '',
        roleFilter: 'all',
        statusFilter: 'all',
        users: {{ json_encode($users) }},
        get filtered() {
            return this.users.filter(u => {
                const q = this.searchQuery.toLowerCase();
                const matchSearch = !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || u.phone.includes(q);
                const matchRole   = this.roleFilter === 'all' || u.role.toLowerCase() === this.roleFilter.toLowerCase();
                const matchStatus = this.statusFilter === 'all' || u.status.toLowerCase() === this.statusFilter.toLowerCase();
                return matchSearch && matchRole && matchStatus;
            });
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kelola Pengguna</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Tambah, edit, aktif/nonaktifkan akun, dan reset password pengguna CRM.</p>
            </div>
            <button type="button" @click="modalAdd = true" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>+ Tambah User</span>
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="crm-card bg-white p-4 space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search -->
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="searchQuery" placeholder="Cari nama, email, nomor HP..." class="w-full text-xs pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-200 focus:border-purple-400">
                </div>
                <!-- Status Filter -->
                <select x-model="statusFilter" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-purple-200 sm:w-40">
                    <option value="all">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <!-- Role Filter Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase">Role:</span>
                @foreach([['val'=>'all','label'=>'Semua'],['val'=>'HM','label'=>'HM'],['val'=>'SPV','label'=>'SPV'],['val'=>'Sales','label'=>'Sales'],['val'=>'CS','label'=>'CS'],['val'=>'Admin','label'=>'Admin']] as $rf)
                <button type="button"
                    @click="roleFilter = '{{ $rf['val'] }}'"
                    :class="roleFilter === '{{ $rf['val'] }}' ? 'bg-purple-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg text-xs transition">
                    {{ $rf['label'] }}
                </button>
                @endforeach
                <span class="ml-auto text-[11px] text-slate-400">Menampilkan <span class="font-bold text-slate-700" x-text="filtered.length"></span> user</span>
            </div>
        </div>

        <!-- Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-4">Nama Pengguna</th>
                            <th class="py-3.5 px-3">Role</th>
                            <th class="py-3.5 px-3">Nomor HP</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-3">Tgl Dibuat</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="user in filtered" :key="user.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xs shrink-0" x-text="user.avatar"></div>
                                        <div>
                                            <div class="font-bold text-slate-900" x-text="user.name"></div>
                                            <div class="text-[11px] text-slate-400" x-text="user.email"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border"
                                        :class="{
                                            'bg-blue-50 text-blue-700 border-blue-200': user.role === 'Sales',
                                            'bg-teal-50 text-teal-800 border-teal-200': user.role === 'CS',
                                            'bg-indigo-50 text-indigo-700 border-indigo-200': user.role === 'SPV',
                                            'bg-purple-50 text-purple-700 border-purple-200': user.role === 'HM' || user.role === 'Admin'
                                        }"
                                        x-text="user.role"></span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-700 font-medium" x-text="user.phone"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="user.status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="user.status === 'Aktif' ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        <span x-text="user.status"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400 text-[11px]" x-text="user.created_at"></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="selectedUser = user; modalDetail = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer" title="Detail">Lihat</button>
                                        <button @click="selectedUser = user; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition cursor-pointer" title="Edit">Edit</button>
                                        <form :action="'/admin/users/' + user.id + '/reset-password'" method="POST" class="inline" @submit="if(!confirm('Reset password user ini ke default?')) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition cursor-pointer" title="Reset Password">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                            </button>
                                        </form>
                                        <form :action="'/admin/users/' + user.id + '/toggle-status'" method="POST" class="inline" @submit="if(!confirm(user.status === 'Aktif' ? 'Nonaktifkan user ini?' : 'Aktifkan user ini?')) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer" :title="user.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        </form>
                                        <form :action="'/admin/users/' + user.id" method="POST" class="inline" @submit="if(!confirm('Hapus pengguna ini?')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-700 hover:bg-red-50 transition cursor-pointer" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL: TAMBAH USER -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Pengguna Baru</h3>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.users.store') }}" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                            <input type="text" name="name" required placeholder="Masukkan Nama Lengkap ..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email *</label>
                                <input type="email" name="email" required placeholder="Masukkan Email ..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor HP *</label>
                                <input type="tel" name="phone" required placeholder="Masukkan Nomor HP ..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Role *</label>
                                <select name="role" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-purple-200 outline-none">
                                    <option value="Sales">Sales</option><option value="CS">CS</option><option value="SPV">SPV</option><option value="HM">HM</option><option value="Admin">Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Akun</label>
                                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-purple-200 outline-none">
                                    <option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Password *</label>
                                <input type="password" name="password" required placeholder="Min. 8 karakter" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password *</label>
                                <input type="password" name="password_confirmation" required placeholder="Ulangi password" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                            </div>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalAdd = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white shadow-xs">Simpan User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: EDIT USER -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-lg p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Data Pengguna</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form :action="selectedUser ? '/admin/users/' + selectedUser.id : ''" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="name" :value="selectedUser ? selectedUser.name : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" :value="selectedUser ? selectedUser.email : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor HP</label>
                                <input type="tel" name="phone" :value="selectedUser ? selectedUser.phone : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Role</label>
                                <select name="role" :value="selectedUser ? selectedUser.role : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-purple-200 outline-none">
                                    <option value="Sales">Sales</option><option value="CS">CS</option><option value="SPV">SPV</option><option value="HM">HM</option><option value="Admin">Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Akun</label>
                                <select name="status" :value="selectedUser ? selectedUser.status : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-purple-200 outline-none">
                                    <option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option>
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

        <!-- MODAL: DETAIL USER -->
        <div x-show="modalDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalDetail = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Detail Pengguna</h3>
                        <button @click="modalDetail = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <template x-if="selectedUser">
                        <div class="mt-4 space-y-4 text-xs">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xl" x-text="selectedUser.avatar"></div>
                                <div>
                                    <div class="text-lg font-extrabold text-slate-900" x-text="selectedUser.name"></div>
                                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border bg-purple-50 text-purple-700 border-purple-200" x-text="selectedUser.role"></span>
                                </div>
                            </div>
                            <div class="space-y-2 p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="flex justify-between"><span class="text-slate-500">Email</span><span class="font-semibold text-slate-800" x-text="selectedUser.email"></span></div>
                                <div class="flex justify-between"><span class="text-slate-500">No. HP</span><span class="font-semibold text-slate-800" x-text="selectedUser.phone"></span></div>
                                <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="font-semibold" :class="selectedUser.status === 'Aktif' ? 'text-emerald-600' : 'text-red-600'" x-text="selectedUser.status"></span></div>
                                <div class="flex justify-between"><span class="text-slate-500">Tgl Dibuat</span><span class="font-semibold text-slate-800" x-text="selectedUser.created_at"></span></div>
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button @click="modalDetail = false; modalEdit = true" class="flex-1 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold cursor-pointer">Edit User</button>
                                <button @click="modalDetail = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 cursor-pointer">Tutup</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
