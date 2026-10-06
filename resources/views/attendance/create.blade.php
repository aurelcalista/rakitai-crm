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
            <form action="{{ route('attendance.store') }}" method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto space-y-6">
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
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/jpg" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                    <p class="mt-1 text-xs text-slate-500" id="photo_helper">Mohon unggah foto bukti absensi Anda. (Format: JPG/PNG, Max: 5MB)</p>
                    @error('photo')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                <div id="notes_container" x-data="{ notesLength: document.getElementById('notes')?.value.length || 0 }">
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
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-sm hover:shadow">
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
                photoHelper.innerHTML = 'Mohon unggah foto selfie/kehadiran Anda hari ini. (Format: JPG/PNG, Max: 5MB)';
                notesInput.removeAttribute('required');
                notesStar.classList.add('hidden');
            } else if (val === 'Sakit') {
                photoLabel.innerHTML = 'Foto Surat Dokter <span class="text-red-500">*</span>';
                photoHelper.innerHTML = 'Mohon unggah foto bukti Surat Dokter. (Format: JPG/PNG, Max: 5MB)';
                notesInput.setAttribute('required', 'required');
                notesStar.classList.remove('hidden');
            } else if (val === 'Izin') {
                photoLabel.innerHTML = 'Foto Bukti Izin <span class="text-red-500">*</span>';
                photoHelper.innerHTML = 'Mohon unggah foto bukti perizinan jika ada atau foto pendukung. (Format: JPG/PNG, Max: 5MB)';
                notesInput.setAttribute('required', 'required');
                notesStar.classList.remove('hidden');
            } else {
                photoLabel.innerHTML = 'Foto Absensi <span class="text-red-500">*</span>';
                photoHelper.innerHTML = 'Mohon unggah foto bukti absensi Anda. (Format: JPG/PNG, Max: 5MB)';
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
