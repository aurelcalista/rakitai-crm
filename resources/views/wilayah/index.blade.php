@php
    $pageTitle = 'Kelola Wilayah';
    $pageSubtitle = 'Manajemen Data Wilayah Provinsi, Kota, dan Kecamatan';
@endphp

<x-app-layout :title="'Kelola Wilayah - CRM UCIC'">
    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl flex items-start gap-3 relative" role="alert">
                <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div class="flex-1">
                    <span class="block sm:inline font-medium text-sm">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.style.display='none'" class="text-green-600 hover:text-green-800 ml-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-start gap-3 relative" role="alert">
                <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="flex-1">
                    <span class="block sm:inline font-medium text-sm">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.style.display='none'" class="text-red-600 hover:text-red-800 ml-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl flex items-start gap-3 relative" role="alert">
                <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div class="flex-1">
                    <span class="block sm:inline font-medium text-sm">{{ session('warning') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.style.display='none'" class="text-amber-600 hover:text-amber-800 ml-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
        @endif

        @if(session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-xl flex items-start gap-3 relative" role="alert">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="flex-1">
                    <span class="block sm:inline font-medium text-sm">{{ session('info') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.style.display='none'" class="text-blue-600 hover:text-blue-800 ml-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-start gap-3 relative" role="alert">
                <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="flex-1">
                    <ul class="list-disc list-inside text-sm font-medium">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" onclick="this.parentElement.style.display='none'" class="text-red-600 hover:text-red-800 ml-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
        @endif

        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 text-sm text-slate-700">
            <p>Kelola kode wilayah yang digunakan untuk menentukan area kerja tim. Kode dapat dibuat untuk wilayah Kota/Kabupaten dan Kecamatan.</p>
            <p class="mt-1 font-semibold text-blue-800">Contoh: Kota Cirebon = 45, Kecamatan Harjamukti = 14.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5 sm:p-6" x-data="{ level: '{{ old('level') }}' }">
            <h2 class="text-base font-bold text-slate-800 mb-4">Tambah Wilayah Baru</h2>
            <form action="{{ route('wilayah.store') }}" method="POST" class="flex flex-col lg:flex-row gap-4 lg:items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Wilayah <span class="text-red-500">*</span></label>
                    <input type="text" name="kode" value="{{ old('kode') }}" required oninvalid="this.setCustomValidity('Kode wilayah wajib diisi.')" oninput="this.setCustomValidity('')" class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10" placeholder="Cth: 45">
                    @error('kode') <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Wilayah <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama') }}" required oninvalid="this.setCustomValidity('Nama wilayah wajib diisi.')" oninput="this.setCustomValidity('')" class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10" placeholder="Cth: Kota Cirebon">
                    @error('nama') <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Wilayah <span class="text-red-500">*</span></label>
                    <select name="level" x-model="level" required oninvalid="this.setCustomValidity('Jenis wilayah wajib dipilih.')" oninput="this.setCustomValidity('')" class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Kota/Kabupaten">Kota/Kabupaten</option>
                        <option value="Kecamatan">Kecamatan</option>
                    </select>
                    @error('level') <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex-1" x-show="level === 'Kecamatan'" style="display: none;">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kota/Kabupaten Induk <span class="text-red-500">*</span></label>
                    <select name="parent_id" :required="level === 'Kecamatan'" class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                        <option value="">-- Pilih Kota/Kabupaten --</option>
                        @foreach($parents->where('level', 'Kota/Kabupaten')->where('status', 'Aktif') as $p)
                            <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                    @error('parent_id') <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="flex-shrink-0">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg text-sm shadow-sm transition h-10 flex justify-center items-center gap-2 w-full lg:w-auto">
                        <span>+ Tambah Wilayah</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-xs border border-slate-200" x-data="{ 
            editModal: false, 
            deleteModal: false,
            isSubmitting: false,
            editData: { id: '', kode: '', nama: '', level: 'Kota/Kabupaten', parent_id: '' },
            deleteData: { id: '', nama: '' }
        }">
            <div class="overflow-hidden rounded-2xl">
                <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-500 font-bold">
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Nama Wilayah</th>
                            <th class="py-3 px-4">Jenis Wilayah</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($wilayahs as $w)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-4 px-4 font-bold text-slate-700 align-top">{{ $w->kode }}</td>
                                <td class="py-4 px-4 font-semibold text-slate-800 align-top">{{ $w->nama }}</td>
                                <td class="py-4 px-4 align-top">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $w->level === 'Kota/Kabupaten' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                        {{ $w->level }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center align-top">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $w->status === 'Aktif' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $w->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 align-top">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
                                            @click="editData = { id: '{{ $w->id }}', kode: '{{ $w->kode }}', nama: '{{ $w->nama }}', level: '{{ $w->level }}', parent_id: '{{ $w->parent_id }}' }; editModal = true;"
                                            class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 transition shadow-sm border border-transparent whitespace-nowrap min-w-[70px]"
                                        >
                                            Edit
                                        </button>
                                        
                                        <form action="{{ route('wilayah.toggle', $w->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition whitespace-nowrap min-w-[95px] {{ $w->status === 'Aktif' ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                                                {{ $w->status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        
                                        <button 
                                            @click="deleteData = { id: '{{ $w->id }}', nama: '{{ $w->nama }}' }; deleteModal = true;"
                                            class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-bold bg-red-50 text-red-700 hover:bg-red-100 transition shadow-sm border border-transparent whitespace-nowrap min-w-[70px]"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 font-medium">Belum ada data wilayah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- MODAL EDIT -->
            <div x-show="editModal" style="display: none;" 
                 class="fixed inset-0 z-[9999] flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div x-show="editModal" 
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @click="editModal = false" 
                         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
                    
                    <div x-show="editModal" 
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="relative bg-white rounded-2xl text-left shadow-xl transform transition-all w-full max-w-[600px] z-10">
                        <form x-bind:action="'{{ url('wilayah') }}/' + editData.id" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="bg-white px-6 pt-6 pb-2 rounded-t-2xl">
                                <h3 class="text-lg leading-6 font-bold text-slate-900 mb-1" id="modal-title">Edit Wilayah</h3>
                                <p class="text-sm text-slate-500">Perbarui informasi kode dan nama wilayah.</p>
                            </div>
                            <div class="px-6 py-4">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Wilayah</label>
                                        <input type="text" name="kode" x-model="editData.kode" required class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Wilayah</label>
                                        <input type="text" name="nama" x-model="editData.nama" required class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Wilayah</label>
                                        <select name="level" x-model="editData.level" required class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                                            <option value="Kota/Kabupaten">Kota/Kabupaten</option>
                                            <option value="Kecamatan">Kecamatan</option>
                                        </select>
                                    </div>
                                    <div x-show="editData.level === 'Kecamatan'" style="display: none;">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kota/Kabupaten Induk</label>
                                        <select name="parent_id" x-model="editData.parent_id" :required="editData.level === 'Kecamatan'" class="w-full text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 h-10">
                                            <option value="">-- Pilih Kota/Kabupaten --</option>
                                            @foreach($parents->where('level', 'Kota/Kabupaten')->where('status', 'Aktif') as $p)
                                                <option value="{{ $p->id }}">{{ $p->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 px-6 py-4 flex flex-row items-center justify-end gap-3 border-t border-slate-200 rounded-b-2xl">
                                <button type="button" @click="editModal = false" class="inline-flex justify-center items-center rounded-lg border border-slate-300 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer min-w-[100px]">
                                    Batal
                                </button>
                                <button type="submit" class="inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 transition cursor-pointer min-w-[150px]">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL HAPUS -->
            <div x-show="deleteModal" style="display: none;" 
                 class="fixed inset-0 z-[9999] flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div x-show="deleteModal" 
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @click="deleteModal = false" 
                         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
                    
                    <div x-show="deleteModal" 
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="relative bg-white rounded-2xl text-left shadow-xl transform transition-all w-full max-w-[500px] z-10">
                        <form x-bind:action="'{{ url('wilayah') }}/' + deleteData.id" method="POST" @submit="isSubmitting = true">
                            @csrf
                            @method('DELETE')
                            <div class="bg-white px-6 pt-6 pb-5 rounded-t-2xl">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4 mt-0 text-left">
                                        <h3 class="text-lg leading-6 font-bold text-slate-900" id="modal-title">Hapus Wilayah</h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-slate-500 leading-relaxed">Apakah Anda yakin ingin menghapus wilayah <strong class="text-slate-700" x-text="deleteData.nama"></strong>?<br><br>Data akan dihapus jika wilayah tersebut belum digunakan oleh assignment tim.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 px-6 py-4 flex flex-row items-center justify-end gap-3 border-t border-slate-200 rounded-b-2xl">
                                <button type="button" @click="deleteModal = false" class="inline-flex justify-center items-center rounded-lg border border-slate-300 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer min-w-[100px]">
                                    Batal
                                </button>
                                <button type="submit" x-bind:disabled="isSubmitting" :class="'inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-sm font-semibold text-white transition min-w-[100px] ' + (isSubmitting ? 'opacity-50 cursor-wait' : 'hover:bg-red-700 cursor-pointer')">
                                    <span x-show="!isSubmitting">Ya, Hapus</span>
                                    <span x-show="isSubmitting" style="display: none;">Memproses...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
