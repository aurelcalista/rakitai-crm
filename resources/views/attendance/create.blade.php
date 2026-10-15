@extends('layouts.app')

@section('title', 'Input Absensi')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Input Absensi</h1>
    </div>

    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <div class="crm-card bg-white p-6 md:p-8">
        @if($hasAttended)
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Anda Sudah Absen Hari Ini</h3>
                <p class="text-slate-600">Terima kasih, data kehadiran Anda untuk tanggal {{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }} telah tersimpan.</p>
            </div>
        @elseif(isset($isHoliday) && $isHoliday)
            <div class="text-center py-8 border border-slate-200 rounded-2xl bg-slate-50">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-rose-100 text-rose-600 mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-rose-600 mb-2">HARI LIBUR</h3>
                <p class="text-slate-600 font-medium">Hari ini merupakan hari libur. Absensi tidak dapat dilakukan.</p>
            </div>
        @else
            <form action="{{ route('attendance.store') }}" method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto space-y-6" x-data="{
                photoPreview: null,
                notesLength: {{ old('notes') ? strlen(old('notes')) : 0 }},
                handleFile(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = ev => this.photoPreview = ev.target.result;
                        reader.readAsDataURL(file);
                    }
                },
                removePhoto() {
                    this.photoPreview = null;
                    if (this.$refs.photoInput) {
                        this.$refs.photoInput.value = '';
                    }
                }
            }">
                @csrf
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal Absensi</label>
                    <input type="text" value="{{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }}" readonly
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 font-medium cursor-not-allowed">
                    <p class="mt-1 text-xs text-slate-500">Tanggal otomatis menyesuaikan hari ini.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Jenis Kehadiran <span class="text-red-500">*</span></label>
                    <select name="status" id="status" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                        <option value="">Pilih Kehadiran</option>
                        <option value="Hadir">Hadir</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1" id="photo_label">Foto Absensi <span class="text-red-500">*</span></label>
                    
                    <!-- Preview Container / Sneak Peek -->
                    <div class="relative mt-1">
                        <!-- Dropzone when no photo chosen -->
                        <div 
                            x-show="!photoPreview"
                            @click="$refs.photoInput.click()"
                            class="border-2 border-dashed border-slate-200 hover:border-blue-400 bg-slate-50/60 hover:bg-blue-50/20 rounded-2xl p-6 sm:p-8 text-center cursor-pointer transition group"
                        >
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-105 transition shadow-2xs">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-slate-800 mb-1">Pilih File Foto dari Perangkat</p>
                            <p class="text-xs text-slate-500" id="photo_helper">Pilih file foto bukti absensi dari galeri atau folder Anda. (Format: JPG/PNG, Max: 5MB)</p>
                        </div>

                        <!-- Sneak Peek Preview when photo selected -->
                        <div x-show="photoPreview" x-cloak class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 group shadow-md">
                            <img :src="photoPreview" alt="Sneak Peek Foto Absensi" class="w-full h-64 sm:h-80 object-cover">
                            
                            <!-- Overlay Bar -->
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/90 via-slate-900/60 to-transparent p-4 sm:p-5 flex items-center justify-between">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500 text-white shadow-xs backdrop-blur-xs">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Foto Terpilih
                                </span>
                                <div class="flex items-center gap-2">
                                    <button 
                                        type="button" 
                                        @click="$refs.photoInput.click()" 
                                        class="px-3.5 py-2 rounded-xl bg-white/95 hover:bg-white text-slate-800 text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        Ganti Foto
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="removePhoto()" 
                                        class="p-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-white shadow-xs transition cursor-pointer" 
                                        title="Hapus Foto"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden File Input -->
                        <input 
                            type="file" 
                            name="photo" 
                            id="photo" 
                            accept="image/jpeg,image/png,image/jpg" 
                            required 
                            x-ref="photoInput" 
                            @change="handleFile($event)"
                            class="sr-only"
                        >
                    </div>
                    @error('photo')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div id="notes_container">
                    <label class="block text-sm font-semibold text-slate-700 mb-1" id="notes_label">Keterangan <span id="notes_star" class="text-red-500 hidden">*</span></label>
                    <textarea name="notes" id="notes" rows="3" maxlength="300" x-on:input="notesLength = $event.target.value.length"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                        placeholder="Masukkan keterangan kehadiran (wajib jika Izin/Sakit, opsional untuk Hadir)">{{ old('notes') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500 text-right"><span x-text="notesLength">0</span>/300</p>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" :disabled="!photoPreview" :class="!photoPreview ? 'opacity-50 cursor-not-allowed' : ''" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-sm hover:shadow cursor-pointer">
                        Simpan Absensi
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.getElementById('status');
        const photoLabel = document.getElementById('photo_label');
        const photoHelper = document.getElementById('photo_helper');
        const notesInput = document.getElementById('notes');
        const notesStar = document.getElementById('notes_star');
        const charCount = document.getElementById('char_count');

        statusSelect.addEventListener('change', function() {
            const val = this.value;
            if (val === 'Hadir') {
                photoLabel.innerHTML = 'Foto Kehadiran <span class="text-red-500">*</span>';
                photoHelper.innerHTML = 'Pilih file foto bukti kehadiran Anda dari galeri/folder. (Format: JPG/PNG, Max: 5MB)';
                notesInput.removeAttribute('required');
                notesStar.classList.add('hidden');
            } else if (val === 'Sakit') {
                photoLabel.innerHTML = 'Foto Surat Dokter <span class="text-red-500">*</span>';
                photoHelper.innerHTML = 'Pilih file foto bukti Surat Dokter dari galeri/folder. (Format: JPG/PNG, Max: 5MB)';
                notesInput.setAttribute('required', 'required');
                notesStar.classList.remove('hidden');
            } else if (val === 'Izin') {
                photoLabel.innerHTML = 'Foto Bukti Izin <span class="text-red-500">*</span>';
                photoHelper.innerHTML = 'Pilih file foto bukti perizinan dari galeri/folder jika ada. (Format: JPG/PNG, Max: 5MB)';
                notesInput.setAttribute('required', 'required');
                notesStar.classList.remove('hidden');
            } else {
                photoLabel.innerHTML = 'Foto Absensi <span class="text-red-500">*</span>';
                photoHelper.innerHTML = 'Pilih file foto bukti absensi dari galeri atau folder Anda. (Format: JPG/PNG, Max: 5MB)';
                notesInput.removeAttribute('required');
                notesStar.classList.add('hidden');
            }
        });

        notesInput.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
        
        // trigger change event on load if there's old input
        if (statusSelect.value) {
            statusSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
@endsection
