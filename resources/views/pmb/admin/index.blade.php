@php
    $pageTitle = 'Data Admin';
    $pageSubtitle = 'Manajemen Akun Administrator PMB UCIC';
    $authUser = auth()->user();
    $userRole = strtolower($authUser->role ?? '');
@endphp

<x-app-layout :title="'Data Admin PMB - CRM UCIC'">
    <div class="space-y-6" x-data="{
        addModalOpen: false,
        editModalOpen: false,
        deleteModalOpen: false,
        selectedAdmin: {},
        editUrl: '',
        deleteUrl: '',
        openEdit(admin) {
            this.selectedAdmin = Object.assign({}, admin);
            this.editUrl = '{{ url('pmb/admin') }}/' + admin.id;
            this.editModalOpen = true;
        },
        openDelete(admin) {
            this.selectedAdmin = admin;
            this.deleteUrl = '{{ url('pmb/admin') }}/' + admin.id;
            this.deleteModalOpen = true;
        }
    }">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs transition-all animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="font-bold text-xs">{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-sm shadow-xs space-y-1 transition-all animate-fade-in">
                @if(session('error'))
                    <div class="flex items-center gap-2.5 font-bold text-xs">
                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <ul class="list-disc list-inside text-xs text-rose-700 pl-10 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <!-- Top Header & Search Toolbar Hero -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all duration-300 hover:shadow-sm">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 font-display">Manajemen Data Admin PMB</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Kelola akun dan hak akses pengguna dengan kewenangan administratif admisi PMB UCIC</p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <!-- Search Form -->
                <form action="{{ route('pmb.admin.index') }}" method="GET" class="flex items-center gap-2">
                    <div class="relative w-full sm:w-64">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Cari admin..." 
                            class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50/70 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 shadow-2xs transition"
                        >
                        @if(request('search'))
                            <a href="{{ route('pmb.admin.index') }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </a>
                        @endif
                    </div>
                    <!-- Blue Search Button -->
                    <button 
                        type="submit" 
                        class="w-10 h-10 rounded-xl bg-[#0b3d82] hover:bg-[#082a5c] text-white flex items-center justify-center shrink-0 shadow-xs hover:shadow-md active:scale-95 transition cursor-pointer"
                        title="Cari Admin"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>

                <!-- + Tambah Admin Button -->
                <button 
                    type="button" 
                    @click="addModalOpen = true"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs hover:shadow-md active:scale-[0.98] transition shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Admin</span>
                </button>
            </div>
        </div>

        <!-- Table Card: Data Admin -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden transition-all duration-300">
            
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 font-display">Daftar Pengguna Administrator</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pengguna aktif yang memegang hak akses role Admin & Head Marketing (HM)</p>
                </div>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $adminList->total() }} Akun Terdaftar
                </span>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap min-w-[700px]">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-100">
                            <th class="py-3.5 px-6 text-center w-16">No</th>
                            <th class="py-3.5 px-6">Nama Admin</th>
                            <th class="py-3.5 px-6">Email Akun</th>
                            <th class="py-3.5 px-6">Role Akses</th>
                            <th class="py-3.5 px-6">Tanggal Dibuat</th>
                            <th class="py-3.5 px-6 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($adminList as $index => $admin)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <!-- No -->
                                <td class="py-4 px-6 text-center font-bold text-slate-400">
                                    {{ $adminList->firstItem() + $index }}
                                </td>

                                <!-- Nama -->
                                <td class="py-4 px-6 font-semibold text-slate-800">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 font-bold flex items-center justify-center shrink-0 text-xs border border-purple-200 shadow-2xs font-display">
                                            {{ strtoupper(substr($admin->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block text-xs">{{ $admin->name }}</span>
                                            @if($admin->id === $authUser->id)
                                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-200">Akun Anda</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="py-4 px-6 text-slate-600 font-mono text-xs">
                                    {{ $admin->email }}
                                </td>

                                <!-- Role -->
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $admin->role === 'Admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $admin->role === 'Admin' ? 'bg-purple-500' : 'bg-blue-500' }}"></span>
                                        {{ $admin->role }}
                                    </span>
                                </td>

                                <!-- Tanggal Dibuat -->
                                <td class="py-4 px-6 text-slate-500 font-medium text-[11px]">
                                    {{ $admin->created_at ? $admin->created_at->format('d M Y') : '-' }}
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-6 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <!-- Edit Pencil Button -->
                                        <button 
                                            type="button" 
                                            @click="openEdit({{ json_encode([
                                                'id' => $admin->id,
                                                'name' => $admin->name,
                                                'email' => $admin->email,
                                                'role' => $admin->role,
                                                'status' => $admin->status ?? 'Aktif'
                                            ]) }})"
                                            class="w-7 h-7 rounded-lg border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-500 hover:text-white flex items-center justify-center transition shadow-2xs cursor-pointer"
                                            title="Edit Data Admin"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>

                                        <!-- Delete Trash Button -->
                                        @if($admin->id !== $authUser->id && $admin->id !== 1 && !in_array($admin->email, ['admin@cic.ac.id', 'admin@ucic.ac.id']))
                                            <button 
                                                type="button" 
                                                @click="openDelete({{ json_encode([
                                                    'id' => $admin->id,
                                                    'name' => $admin->name,
                                                    'email' => $admin->email
                                                ]) }})"
                                                class="w-7 h-7 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white flex items-center justify-center transition shadow-2xs cursor-pointer"
                                                title="Hapus Akun Admin"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @else
                                            <span class="w-7 h-7 rounded-lg border border-slate-200 bg-slate-50 text-slate-300 flex items-center justify-center cursor-not-allowed" title="Akun dilindungi">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p class="font-bold text-slate-700 text-sm">Tidak ada akun admin ditemukan</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba kata kunci pencarian yang lain.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($adminList->hasPages())
                <div class="p-4 sm:px-6 border-t border-slate-100 bg-slate-50/50">
                    {{ $adminList->links() }}
                </div>
            @endif

        </div>

        <!-- Modal: Tambah Admin -->
        <div 
            x-show="addModalOpen" 
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="addModalOpen = false"></div>

                <div 
                    x-show="addModalOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-slate-100"
                >
                    <form action="{{ route('pmb.admin.store') }}" method="POST">
                        @csrf
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 font-display">Tambah Admin Baru</h3>
                            </div>
                            <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- Nama -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Admin <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" required placeholder="Contoh: Admin PMB UCIC" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" required placeholder="admin.pmb@ucic.ac.id" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Role (Admin atau HM only) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Role Akses <span class="text-rose-500">*</span></label>
                                <select name="role" required class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                    <option value="Admin">Admin (Akses Penuh)</option>
                                    <option value="HM">Head Marketing (HM)</option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Catatan: Role CS tidak diizinkan mengelola modul admin.</p>
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                                <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Password Confirmation -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Konfirmasi Password <span class="text-rose-500">*</span></label>
                                <input type="password" name="password_confirmation" required minlength="6" placeholder="Ulangi password di atas" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>

                        <div class="bg-slate-50/80 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs hover:shadow-md transition cursor-pointer">
                                Simpan Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Edit Admin -->
        <div 
            x-show="editModalOpen" 
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="editModalOpen = false"></div>

                <div 
                    x-show="editModalOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-slate-100"
                >
                    <form :action="editUrl" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 font-display">Edit Data Admin</h3>
                            </div>
                            <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- Nama -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Admin</label>
                                <input type="text" name="name" :value="selectedAdmin.name" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" :value="selectedAdmin.email" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Role & Status -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Role</label>
                                    <select name="role" x-model="selectedAdmin.role" required class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                        <option value="Admin">Admin</option>
                                        <option value="HM">HM</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                                    <select name="status" x-model="selectedAdmin.status" required class="w-full text-xs font-bold rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                        <option value="Aktif">Aktif</option>
                                        <option value="Nonaktif">Nonaktif</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Password Baru (Opsional) -->
                            <div class="pt-2 border-t border-slate-100 space-y-3">
                                <p class="text-[11px] text-slate-400">Kosongkan kolom password jika tidak ingin mengganti password admin ini.</p>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Baru (Opsional)</label>
                                    <input type="password" name="password" minlength="6" placeholder="Minimal 6 karakter" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Konfirmasi Password Baru</label>
                                    <input type="password" name="password_confirmation" minlength="6" placeholder="Ulangi password baru" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50/80 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs hover:shadow-md transition cursor-pointer">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Konfirmasi Hapus Admin -->
        <div 
            x-show="deleteModalOpen" 
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="deleteModalOpen = false"></div>

                <div 
                    x-show="deleteModalOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-slate-100"
                >
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="p-6">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4 border border-rose-100 shadow-2xs">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 font-display">Hapus Akun Admin?</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Apakah Anda yakin ingin menghapus akun admin <strong class="text-slate-800" x-text="selectedAdmin.name"></strong> (<span class="font-mono text-slate-600" x-text="selectedAdmin.email"></span>)? Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>

                        <div class="bg-slate-50/80 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="deleteModalOpen = false" 
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs hover:shadow-md transition cursor-pointer"
                            >
                                Ya, Hapus Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
