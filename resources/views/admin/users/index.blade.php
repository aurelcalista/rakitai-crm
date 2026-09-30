@php $pageTitle = 'Kelola Pengguna'; @endphp

<x-app-layout :title="'Kelola Pengguna - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: {{ ($errors->hasAny(['name', 'email', 'phone', 'role', 'status', 'password', 'password_confirmation', 'wilayah_id', 'supervisor_id']) && !old('_method')) ? 'true' : 'false' }},
        modalEdit: false,
        modalDetail: false,
        selectedUser: null,
        searchQuery: '',
        roleFilter: 'all',
        statusFilter: 'all',
        currentPage: 1,
        perPage: 25,
        users: {{ json_encode($users) }},
        formatDate(dateStr) {
            if (!dateStr) return '-';
            if (typeof dateStr === 'string' && /^\d{2}-\d{2}-\d{4}/.test(dateStr)) {
                return dateStr;
            }
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}-${month}-${year}`;
            } catch (e) {
                return dateStr;
            }
        },
        get filtered() {
            return this.users.filter(u => {
                const q = this.searchQuery.toLowerCase();
                const matchSearch = !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || u.phone.includes(q);
                const matchRole   = this.roleFilter === 'all' || u.role.toLowerCase() === this.roleFilter.toLowerCase();
                const matchStatus = this.statusFilter === 'all' || u.status.toLowerCase() === this.statusFilter.toLowerCase();
                return matchSearch && matchRole && matchStatus;
            });
        },
        get paginated() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filtered.length / this.perPage) || 1;
        }
    }" x-effect="searchQuery; roleFilter; statusFilter; currentPage = 1">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kelola Pengguna</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Tambah, edit, aktif/nonaktifkan akun, dan reset password pengguna CRM.</p>
            </div>
            <button type="button" @click="modalAdd = true" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Tambah User</span>
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
                    <option value="pending">Pending</option>
                </select>
            </div>
            <!-- Role Filter Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase">Role:</span>
                @foreach([['val'=>'all','label'=>'Semua'],['val'=>'HM','label'=>'HM'],['val'=>'SPV','label'=>'SPV'],['val'=>'Sales','label'=>'Sales'],['val'=>'CS','label'=>'CS'],['val'=>'EO','label'=>'EO'],['val'=>'Admin','label'=>'Admin']] as $rf)
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

        <!-- Pending Alert Banner -->
        @php $pendingCount = $users->where('status', 'Pending')->count(); @endphp
        @if($pendingCount > 0)
        <div class="flex items-center gap-4 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-3.5">
            <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-amber-800">{{ $pendingCount }} Pendaftaran Menunggu Persetujuan</p>
                <p class="text-xs text-amber-600 mt-0.5">Gunakan filter <strong>Pending</strong> atau klik tombol <strong>ACC</strong> di kolom Aksi untuk menyetujui.</p>
            </div>
            <button type="button" x-on:click="statusFilter = 'pending'" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition cursor-pointer">
                Lihat Pending
            </button>
        </div>
        @endif

        <!-- Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Pengguna</th>
                            <th class="py-3.5 px-3">Role</th>
                            <th class="py-3.5 px-3">Nomor HP</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-3">Tgl Dibuat</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(user, index) in paginated" :key="user.id + '-' + currentPage">
                            <tr class="hover:bg-slate-50/80 transition crm-table-slide">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xs shrink-0 overflow-hidden">
                                            <template x-if="user.avatar_url">
                                                <img :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!user.avatar_url">
                                                <span x-text="user.avatar"></span>
                                            </template>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-slate-900" x-text="user.name"></span>
                                                <span class="px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200" x-show="user.kode" x-text="'🆔 ' + user.kode"></span>
                                            </div>
                                            <div class="text-[11px] text-slate-400" x-text="user.email"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border"
                                        :class="{
                                            'bg-blue-50 text-blue-700 border-blue-200': user.role === 'Sales',
                                            'bg-teal-50 text-teal-800 border-teal-200': user.role === 'CS',
                                            'bg-orange-50 text-orange-700 border-orange-200': user.role === 'EO',
                                            'bg-indigo-50 text-indigo-700 border-indigo-200': user.role === 'SPV',
                                            'bg-purple-50 text-purple-700 border-purple-200': user.role === 'HM' || user.role === 'Admin'
                                        }"
                                        x-text="user.role"></span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-700 font-medium" x-text="user.phone"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': user.status === 'Aktif',
                                            'bg-red-50 text-red-700 border border-red-200': user.status === 'Nonaktif',
                                            'bg-amber-50 text-amber-700 border border-amber-300': user.status === 'Pending'
                                        }">
                                        <span class="w-1.5 h-1.5 rounded-full animate-pulse"
                                            :class="{
                                                'bg-emerald-500': user.status === 'Aktif',
                                                'bg-red-500': user.status === 'Nonaktif',
                                                'bg-amber-400': user.status === 'Pending'
                                            }"></span>
                                        <span x-text="user.status"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-500 font-medium text-[11px]" x-text="formatDate(user.formatted_created_at || user.created_at)"></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">

                                        <!-- Pending: ACC + Reject buttons -->
                                        <template x-if="user.status === 'Pending'">
                                            <div class="flex items-center gap-2">
                                                <form :action="'/admin/users/' + user.id + '/approve'" method="POST" class="inline"
                                                    :data-confirm="'Setujui dan aktifkan akun ' + user.name + '?'">
                                                    @csrf
                                                    <button type="submit" title="Setujui / ACC"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold
                                                               bg-emerald-500 hover:bg-emerald-600 active:scale-95
                                                               text-white shadow-sm shadow-emerald-200/60
                                                               transition-all duration-150 cursor-pointer">
                                                        <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                        ACC
                                                    </button>
                                                </form>
                                                <form :action="'/admin/users/' + user.id + '/reject'" method="POST" class="inline"
                                                    :data-confirm="'Tolak pendaftaran ' + user.name + '? Akun akan dinonaktifkan.'">
                                                    @csrf
                                                    <button type="submit" title="Tolak"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold
                                                               bg-white hover:bg-red-50 active:scale-95
                                                               text-red-500 border border-red-200 hover:border-red-300
                                                               transition-all duration-150 cursor-pointer">
                                                        <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                                        Tolak
                                                    </button>
                                                </form>
                                            </div>
                                        </template>

                                        <!-- Non-Pending: normal actions -->
                                        <template x-if="user.status !== 'Pending'">
                                            <div class="flex items-center gap-1">
                                                <button @click="selectedUser = user; modalDetail = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer" title="Detail">Lihat</button>
                                                <button @click="selectedUser = user; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition cursor-pointer" title="Edit">Edit</button>
                                                <form :action="'/admin/users/' + user.id + '/reset-password'" method="POST" class="inline"
                                                    :data-confirm="'Reset password ' + user.name + ' ke password default?'">
                                                    @csrf
                                                    <button type="submit" class="p-1.5 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition cursor-pointer" title="Reset Password">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                    </button>
                                                </form>
                                                <form :action="'/admin/users/' + user.id + '/toggle-status'" method="POST" class="inline"
                                                    :data-confirm="user.status === 'Aktif' ? 'Nonaktifkan akun ' + user.name + '?' : 'Aktifkan kembali akun ' + user.name + '?'">
                                                    @csrf
                                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer" :title="user.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                    </button>
                                                </form>
                                                <form :action="'/admin/users/' + user.id" method="POST" class="inline"
                                                    :data-confirm="'Hapus akun ' + user.name + '? Tindakan ini tidak bisa dibatalkan.'">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-700 hover:bg-red-50 transition cursor-pointer" title="Hapus">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </template>

                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filtered.length === 0">
                            <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada data pengguna yang ditemukan.
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

        <!-- MODAL: TAMBAH USER -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
             x-data="{
                 inputEmail: '{{ old('email') }}',
                 get existingUser() {
                     const email = (this.inputEmail || '').trim().toLowerCase();
                     if (!email) return null;
                     return users.find(u => u.email && u.email.toLowerCase() === email);
                 },
                 handleSubmit(e) {
                     if (this.existingUser) {
                         e.preventDefault();
                         if (typeof Swal !== 'undefined') {
                             Swal.fire({
                                 icon: 'error',
                                 title: 'Email Sudah Terdaftar!',
                                 html: 'Alamat email <b>' + this.inputEmail + '</b> sudah terdaftar untuk pengguna <b>' + this.existingUser.name + '</b> (Role: ' + this.existingUser.role + ').<br><br><span class=\'text-xs text-slate-500\'>Silakan gunakan alamat email lain.</span>',
                                 confirmButtonColor: '#e11d48',
                                 confirmButtonText: 'Perbaiki Email',
                                 customClass: {
                                     popup: 'rounded-2xl shadow-xl border border-rose-100',
                                     confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                                 }
                             });
                         }
                         return false;
                     }
                 }
             }">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Pengguna Baru</h3>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.users.store') }}" method="POST" @submit="handleSubmit($event)" class="mt-4 space-y-3 text-xs">
                        @csrf
                        @if ($errors->any() && !old('_method'))
                            <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-rose-800">
                                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Gagal Menyimpan Pengguna:</span>
                                </div>
                                <ul class="list-disc list-inside space-y-0.5 ml-1 text-rose-600">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Masukkan Nama Lengkap ..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border {{ $errors->has('name') && !old('_method') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200' }} focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                            @if(!old('_method'))
                                @error('name')
                                    <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email *</label>
                                <div class="relative">
                                    <input type="email" 
                                           name="email" 
                                           x-model="inputEmail"
                                           required 
                                           placeholder="Masukkan Email ..." 
                                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border outline-none transition"
                                           :class="existingUser ? 'border-rose-500 bg-rose-50/40 text-rose-900 focus:ring-2 focus:ring-rose-200' : '{{ $errors->has('email') && !old('_method') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200' }} focus:ring-2 focus:ring-purple-200 focus:border-purple-400'">
                                    
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" x-show="inputEmail && inputEmail.trim().length > 0">
                                        <template x-if="existingUser">
                                            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-rose-100 text-rose-600" title="Email sudah terdaftar">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </span>
                                        </template>
                                        <template x-if="!existingUser && inputEmail && inputEmail.includes('@')">
                                            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-600" title="Email tersedia">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Live alert when duplicate email is detected -->
                                <div x-show="existingUser" x-cloak class="mt-1.5 p-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-1.5">
                                    <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <div>
                                        <p class="font-bold text-rose-800">Email sudah terdaftar!</p>
                                        <p class="text-[11px] text-rose-600">Digunakan oleh <strong x-text="existingUser?.name"></strong> (<span x-text="existingUser?.role"></span>).</p>
                                    </div>
                                </div>

                                @if(!old('_method'))
                                    @error('email')
                                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor HP *</label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="Masukkan Nomor HP ..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border {{ $errors->has('phone') && !old('_method') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200' }} focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                                @if(!old('_method'))
                                    @error('phone')
                                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Role *</label>
                                <select name="role" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-purple-200 outline-none">
                                    <option value="Sales" {{ old('role') === 'Sales' ? 'selected' : '' }}>Sales</option>
                                    <option value="CS" {{ old('role') === 'CS' ? 'selected' : '' }}>CS</option>
                                    <option value="SPV" {{ old('role') === 'SPV' ? 'selected' : '' }}>SPV</option>
                                    <option value="HM" {{ old('role') === 'HM' ? 'selected' : '' }}>HM</option>
                                    <option value="EO" {{ old('role') === 'EO' ? 'selected' : '' }}>EO</option>
                                    <option value="Admin" {{ old('role') === 'Admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Akun</label>
                                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-purple-200 outline-none">
                                    <option value="Aktif" {{ old('status', 'Aktif') === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="Nonaktif" {{ old('status') === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Password *</label>
                                <input type="password" name="password" required placeholder="Masukkan password" class="w-full text-xs px-3.5 py-2.5 rounded-xl border {{ $errors->has('password') && !old('_method') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200' }} focus:ring-2 focus:ring-purple-200 outline-none">
                                @if(!old('_method'))
                                    @error('password')
                                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                                    @enderror
                                @endif
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
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
             x-data="{
                 editEmail: '',
                 init() {
                     this.$watch('selectedUser', u => {
                         this.editEmail = u ? u.email : '';
                     });
                 },
                 get existingEditUser() {
                     if (!selectedUser || !this.editEmail) return null;
                     const email = this.editEmail.trim().toLowerCase();
                     return users.find(u => u.id !== selectedUser.id && u.email && u.email.toLowerCase() === email);
                 },
                 handleEditSubmit(e) {
                     if (this.existingEditUser) {
                         e.preventDefault();
                         if (typeof Swal !== 'undefined') {
                             Swal.fire({
                                 icon: 'error',
                                 title: 'Email Sudah Terdaftar!',
                                 html: 'Alamat email <b>' + this.editEmail + '</b> sudah terdaftar untuk pengguna <b>' + this.existingEditUser.name + '</b> (Role: ' + this.existingEditUser.role + ').<br><br><span class=\'text-xs text-slate-500\'>Silakan gunakan alamat email lain.</span>',
                                 confirmButtonColor: '#e11d48',
                                 confirmButtonText: 'Perbaiki Email',
                                 customClass: {
                                     popup: 'rounded-2xl shadow-xl border border-rose-100',
                                     confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
                                 }
                             });
                         }
                         return false;
                     }
                 }
             }">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-lg p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Data Pengguna</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form :action="selectedUser ? '/admin/users/' + selectedUser.id : ''" method="POST" @submit="handleEditSubmit($event)" class="mt-4 space-y-3 text-xs">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="name" :value="selectedUser ? selectedUser.name : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                                <input type="email" 
                                       name="email" 
                                       x-model="editEmail"
                                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border outline-none transition"
                                       :class="existingEditUser ? 'border-rose-500 bg-rose-50/40 text-rose-900 focus:ring-2 focus:ring-rose-200' : 'border-slate-200 focus:ring-2 focus:ring-purple-200'">
                                
                                <div x-show="existingEditUser" x-cloak class="mt-1.5 p-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-1.5">
                                    <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <div>
                                        <p class="font-bold text-rose-800">Email sudah terdaftar!</p>
                                        <p class="text-[11px] text-rose-600">Digunakan oleh <strong x-text="existingEditUser?.name"></strong> (<span x-text="existingEditUser?.role"></span>).</p>
                                    </div>
                                </div>
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
                                    <option value="Sales">Sales</option><option value="CS">CS</option><option value="SPV">SPV</option><option value="HM">HM</option><option value="EO">EO</option><option value="Admin">Admin</option>
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
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-xl overflow-hidden shadow-xs">
                                    <template x-if="selectedUser.avatar_url">
                                        <img :src="selectedUser.avatar_url" :alt="selectedUser.name" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!selectedUser.avatar_url">
                                        <span x-text="selectedUser.avatar"></span>
                                    </template>
                                </div>
                                <div>
                                    <div class="text-lg font-extrabold text-slate-900" x-text="selectedUser.name"></div>
                                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border bg-purple-50 text-purple-700 border-purple-200" x-text="selectedUser.role"></span>
                                </div>
                            </div>
                            <div class="space-y-2 p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="flex justify-between"><span class="text-slate-500">Email</span><span class="font-semibold text-slate-800" x-text="selectedUser.email"></span></div>
                                <div class="flex justify-between"><span class="text-slate-500">No. HP</span><span class="font-semibold text-slate-800" x-text="selectedUser.phone"></span></div>
                                <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="font-semibold" :class="selectedUser.status === 'Aktif' ? 'text-emerald-600' : 'text-red-600'" x-text="selectedUser.status"></span></div>
                                <div class="flex justify-between"><span class="text-slate-500">Tgl Dibuat</span><span class="font-semibold text-slate-800" x-text="formatDate(selectedUser?.formatted_created_at || selectedUser?.created_at)"></span></div>
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
