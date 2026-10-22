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
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-sm shadow-xs space-y-1">
                @if(session('error'))
                    <div class="flex items-center gap-2.5 font-medium">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <ul class="list-disc list-inside text-xs text-rose-700 pl-7 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <!-- Top Header & Search Toolbar (Screenshot 1) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <!-- Search Form -->
            <form action="{{ route('pmb.admin.index') }}" method="GET" class="flex items-center gap-2 max-w-md w-full">
                <div class="relative flex-1">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Cari admin..." 
                        class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 bg-white text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 shadow-2xs transition"
                    >
                </div>
                <!-- Blue Search Button -->
                <button 
                    type="submit" 
                    class="w-10 h-10 rounded-xl bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center shrink-0 shadow-xs transition"
                    title="Cari"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                @if(request('search'))
                    <a href="{{ route('pmb.admin.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-1">
                        Reset
                    </a>
                @endif
            </form>

            <!-- + Tambah Admin Button (Screenshot 1) -->
            <button 
                type="button" 
                @click="addModalOpen = true"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition shrink-0"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Admin</span>
            </button>
        </div>

        <!-- Table Card: Data Admin (Screenshot 1) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-800">Data Admin</h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar pengguna dengan hak akses administratif PMB UCIC</p>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50/80 text-slate-600 font-bold border-b border-slate-100 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-6 text-center w-16">No</th>
                            <th class="py-3.5 px-6">Nama</th>
                            <th class="py-3.5 px-6">Email</th>
                            <th class="py-3.5 px-6">Role</th>
                            <th class="py-3.5 px-6">Tanggal Dibuat</th>
                            <th class="py-3.5 px-6 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($adminList as $index => $admin)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <!-- No -->
                                <td class="py-4 px-6 text-center font-medium text-slate-500">
                                    {{ $adminList->firstItem() + $index }}
                                </td>

                                <!-- Nama -->
                                <td class="py-4 px-6 font-semibold text-slate-800">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center shrink-0 text-xs">
                                            {{ strtoupper(substr($admin->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <span>{{ $admin->name }}</span>
                                            @if($admin->id === $authUser->id)
                                                <span class="ml-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-200">Anda</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="py-4 px-6 text-slate-600 font-mono">
                                    {{ $admin->email }}
                                </td>

                                <!-- Role -->
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold {{ $admin->role === 'Admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                        {{ $admin->role }}
                                    </span>
                                </td>

                                <!-- Tanggal Dibuat (Screenshot format e.g. 12 Aug 2025) -->
                                <td class="py-4 px-6 text-slate-600">
                                    {{ $admin->created_at ? $admin->created_at->format('d M Y') : '-' }}
                                </td>

                                <!-- Aksi (Screenshot 1: Yellow/Orange Edit Pencil & Red Trash Can) -->
                                <td class="py-4 px-6 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Edit Pencil Button (Orange border) -->
                                        <button 
                                            type="button" 
                                            @click="openEdit({{ json_encode([
                                                'id' => $admin->id,
                                                'name' => $admin->name,
                                                'email' => $admin->email,
                                                'role' => $admin->role,
                                                'status' => $admin->status ?? 'Aktif'
                                            ]) }})"
                                            class="w-7 h-7 rounded-lg border border-amber-400 text-amber-500 hover:bg-amber-50 flex items-center justify-center transition"
                                            title="Edit Admin"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>

                                        <!-- Delete Trash Button (Red border) -->
                                        @if($admin->id !== $authUser->id && $admin->id !== 1 && !in_array($admin->email, ['admin@cic.ac.id', 'admin@ucic.ac.id']))
                                            <button 
                                                type="button" 
                                                @click="openDelete({{ json_encode([
                                                    'id' => $admin->id,
                                                    'name' => $admin->name,
                                                    'email' => $admin->email
                                                ]) }})"
                                                class="w-7 h-7 rounded-lg border border-rose-300 text-rose-500 hover:bg-rose-50 flex items-center justify-center transition"
                                                title="Hapus Admin"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @else
                                            <span class="w-7 h-7 rounded-lg border border-slate-200 text-slate-300 flex items-center justify-center cursor-not-allowed" title="Akun dilindungi">
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
                                    <p class="font-semibold text-slate-600">Tidak ada akun admin ditemukan</p>
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
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="addModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="addModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div 
                    x-show="addModalOpen" 
                    x-transition.scale.origin.bottom
                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100"
                >
                    <form action="{{ route('pmb.admin.store') }}" method="POST">
                        @csrf
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-800">Tambah Admin Baru</h3>
                            <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- Nama -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Admin <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" required placeholder="Contoh: Admin PMB UCIC" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" required placeholder="admin.pmb@ucic.ac.id" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Role (Admin atau HM only) -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Role Akses <span class="text-rose-500">*</span></label>
                                <select name="role" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <option value="Admin">Admin (Akses Penuh)</option>
                                    <option value="HM">Head Marketing (HM)</option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Catatan: Role CS tidak diizinkan mengelola modul admin.</p>
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                                <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Password Confirmation -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password <span class="text-rose-500">*</span></label>
                                <input type="password" name="password_confirmation" required minlength="6" placeholder="Ulangi password di atas" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition">
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
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="editModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="editModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div 
                    x-show="editModalOpen" 
                    x-transition.scale.origin.bottom
                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100"
                >
                    <form :action="editUrl" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-800">Edit Data Admin</h3>
                            <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- Nama -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Admin</label>
                                <input type="text" name="name" :value="selectedAdmin.name" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" :value="selectedAdmin.email" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Role & Status -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Role</label>
                                    <select name="role" x-model="selectedAdmin.role" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5">
                                        <option value="Admin">Admin</option>
                                        <option value="HM">HM</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                                    <select name="status" x-model="selectedAdmin.status" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5">
                                        <option value="Aktif">Aktif</option>
                                        <option value="Nonaktif">Nonaktif</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Password Baru (Opsional) -->
                            <div class="pt-2 border-t border-slate-100 space-y-3">
                                <p class="text-[11px] text-slate-400">Kosongkan kolom password jika tidak ingin mengganti password admin ini.</p>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru (Opsional)</label>
                                    <input type="password" name="password" minlength="6" placeholder="Minimal 6 karakter" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password Baru</label>
                                    <input type="password" name="password_confirmation" minlength="6" placeholder="Ulangi password baru" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition">
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
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="deleteModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="deleteModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div 
                    x-show="deleteModalOpen" 
                    x-transition.scale.origin.bottom
                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100"
                >
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="p-6">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-bold text-slate-800">Hapus Akun Admin?</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Apakah Anda yakin ingin menghapus akun admin <strong class="text-slate-800" x-text="selectedAdmin.name"></strong> (<span class="font-mono text-slate-600" x-text="selectedAdmin.email"></span>)? Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="deleteModalOpen = false" 
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition"
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
