@extends('layouts.app', ['title' => 'Absensi Geolocation'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Presensi Kehadiran Geolocation</h1>
            <p class="text-xs text-slate-500 mt-1">Lakukan absensi dengan verifikasi GPS pada radius titik lokasi yang ditentukan.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('attendance.history') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Riwayat Absensi Saya
            </a>
        </div>
    </div>

    @if(isset($userStats))
        <!-- User Monthly Attendance Rate Banner -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $userStats['percentage'] >= 80 ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-amber-50 text-amber-600 border border-amber-200' }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-900">Persentase Kehadiran Bulan Ini:</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-black {{ $userStats['percentage'] >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $userStats['percentage'] }}%
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5">Tercatat {{ $userStats['present_days'] }} hari hadir dari {{ $userStats['work_days'] }} hari kerja berjalan (Periode {{ $userStats['month_name'] }})</p>
                </div>
            </div>
            <div class="w-full sm:w-48 bg-slate-100 rounded-full h-2 overflow-hidden shrink-0">
                <div class="{{ $userStats['percentage'] >= 80 ? 'bg-emerald-500' : 'bg-amber-500' }} h-2 rounded-full transition-all duration-700" style="width: {{ $userStats['percentage'] }}%"></div>
            </div>
        </div>
    @endif

    <!-- Alert Notifications -->
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-2xl space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Presensi gagal diproses:
            </div>
            <ul class="list-disc list-inside ml-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($hasCheckedInToday && $todayAttendance)
        <!-- Already Checked In Today Banner -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 text-emerald-900 shadow-xs">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="space-y-2 flex-1">
                    <h2 class="text-base font-bold text-emerald-950">Anda sudah melakukan absensi hari ini.</h2>
                    <p class="text-xs text-emerald-800">
                        Presensi Anda telah tercatat dengan baik dalam sistem. Setiap pengguna hanya perlu melakukan 1 kali absensi per hari.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 mt-3 border-t border-emerald-200/60 text-xs">
                        <div class="bg-white/80 p-3 rounded-xl border border-emerald-200/60">
                            <span class="text-slate-400 block text-[10px] font-semibold uppercase">Lokasi Titik</span>
                            <span class="font-bold text-slate-800">{{ $todayAttendance->location?->name ?? 'Titik Absensi' }}</span>
                        </div>
                        <div class="bg-white/80 p-3 rounded-xl border border-emerald-200/60">
                            <span class="text-slate-400 block text-[10px] font-semibold uppercase">Jarak Terverifikasi</span>
                            <span class="font-bold text-slate-800">{{ number_format($todayAttendance->distance, 2) }} meter</span>
                        </div>
                        <div class="bg-white/80 p-3 rounded-xl border border-emerald-200/60">
                            <span class="text-slate-400 block text-[10px] font-semibold uppercase">Waktu Check-in</span>
                            <span class="font-bold text-slate-800">{{ $todayAttendance->check_in_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Check-in Form Card (Alpine.js State) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden" 
             x-data="attendanceComponent({
                locations: {{ json_encode($activeLocations) }},
                submitUrl: '{{ route('attendance.store') }}'
             })">

            <form action="{{ route('attendance.store') }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit">
                @csrf
                <input type="hidden" name="latitude" :value="userLat">
                <input type="hidden" name="longitude" :value="userLng">
                <input type="hidden" name="attendance_location_id" :value="selectedLocationId">

                <div class="p-6 space-y-6">
                    <!-- Step 1: Pilih Lokasi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            1. Pilih Titik Lokasi Absensi <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            x-model="selectedLocationId" 
                            @change="onLocationChange"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 py-2.5 px-3 bg-slate-50/50"
                            required
                        >
                            <option value="">-- Pilih Lokasi Tugas / Event --</option>
                            <template x-for="loc in locations" :key="loc.id">
                                <option :value="loc.id" x-text="loc.name + ' (Radius: ' + loc.radius + ' m)'"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Hanya titik lokasi berstatus aktif yang ditampilkan dalam daftar.</p>
                        @if($activeLocations->isEmpty())
                            <div class="mt-2 p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>Belum ada titik lokasi absensi aktif. Admin dapat menambahkannya melalui menu <strong>Kelola Titik Absensi</strong>.</span>
                            </div>
                        @endif
                    </div>

                    <!-- Step 2: Ambil GPS User -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                2. Posisi GPS Perangkat
                            </span>
                            <button 
                                type="button" 
                                @click="fetchLocation" 
                                :disabled="geoLoading || !selectedLocationId"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 disabled:opacity-50 transition"
                            >
                                <svg x-show="!geoLoading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <svg x-show="geoLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span x-text="geoLoading ? 'Mengambil GPS...' : (userLat ? 'Perbarui Lokasi' : 'Deteksi Lokasi Saya')"></span>
                            </button>
                        </div>

                        <!-- Status Alert GPS -->
                        <div x-show="geoError" class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-700" x-text="geoError"></div>

                        <!-- Coordinates & Distance Preview -->
                        <template x-if="userLat && userLng">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-2">
                                <div class="p-2.5 rounded-lg bg-white border border-slate-200">
                                    <span class="text-slate-400 block text-[10px]">Koordinat Anda:</span>
                                    <span class="font-mono font-medium text-slate-800" x-text="formatCoord(userLat) + ', ' + formatCoord(userLng)"></span>
                                </div>
                                <div class="p-2.5 rounded-lg bg-white border border-slate-200">
                                    <span class="text-slate-400 block text-[10px]">Jarak ke Titik:</span>
                                    <span class="font-bold text-slate-900" x-text="distanceMeters !== null ? distanceMeters + ' meter' : '-'"></span>
                                </div>
                                <div class="p-2.5 rounded-lg border flex items-center justify-between"
                                     :class="isWithinRadius ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'">
                                    <div>
                                        <span class="block text-[10px] uppercase font-bold" x-text="isWithinRadius ? 'Dalam Radius' : 'Di Luar Radius'"></span>
                                        <span class="text-[11px]" x-text="isWithinRadius ? 'Boleh Presensi' : 'Jarak Terlalu Jauh'"></span>
                                    </div>
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"
                                          :class="isWithinRadius ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900'">
                                        <span x-text="isWithinRadius ? '✓' : '✕'"></span>
                                    </span>
                                </div>
                            </div>
                        </template>

                        <div x-show="!userLat && !geoLoading" class="text-xs text-slate-400">
                            Silakan pilih lokasi di atas, lalu klik "Deteksi Lokasi Saya" untuk membaca koordinat GPS perangkat Anda.
                        </div>
                    </div>

                    <!-- Step 3: Bukti Foto Selfie (Kamera / Galeri) -->
                    <div x-show="isWithinRadius" x-transition.opacity.duration.300ms class="space-y-4">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            3. Bukti Foto Selfie Absensi <span class="text-rose-500">*</span>
                        </label>

                        <!-- Pilihan Input Kamera atau Galeri -->
                        <div class="flex flex-wrap gap-3">
                            <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-xs transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Buka Kamera Langsung
                                <input type="file" name="foto" accept="image/*" capture="user" @change="handlePhotoSelect" class="hidden" id="cameraInput">
                            </label>

                            <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Pilih dari Galeri
                                <input type="file" accept="image/jpeg,image/png,image/webp" @change="handlePhotoSelect" class="hidden" id="galleryInput">
                            </label>
                        </div>

                        <!-- Preview Foto -->
                        <div x-show="photoPreview" class="relative inline-block border border-slate-200 rounded-xl overflow-hidden bg-slate-50 p-2 shadow-xs">
                            <img :src="photoPreview" alt="Preview Selfie" class="max-h-64 rounded-lg object-cover">
                            <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                                <span class="text-slate-500 font-medium" x-text="photoName"></span>
                                <button type="button" @click="clearPhoto" class="text-rose-600 hover:text-rose-700 font-semibold">
                                    Ganti Foto
                                </button>
                            </div>
                        </div>

                        <!-- Catatan Opsional -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Catatan Tambahan (Opsional)</label>
                            <input type="text" name="notes" placeholder="Misal: Hadir briefing pagi, standby stand kampus" class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 py-2 px-3">
                        </div>
                    </div>

                    <!-- Outside Radius Warning -->
                    <div x-show="userLat && !isWithinRadius" x-transition class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Anda berada di luar area absensi.
                        </div>
                        <p>
                            Jarak posisi Anda saat ini adalah <strong x-text="distanceMeters + ' meter'"></strong> dari titik lokasi, sedangkan batas maksimal adalah <strong x-text="selectedRadius + ' meter'"></strong>. Absensi tidak dapat dikirimkan.
                        </p>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="p-5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Pastikan Anda berada di lokasi yang benar sebelum mengirim absensi.
                    </span>
                    <button 
                        type="submit" 
                        :disabled="!isWithinRadius || !photoPreview || isSubmitting"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed shadow-xs transition"
                    >
                        <span x-show="!isSubmitting">Kirim Absensi</span>
                        <span x-show="isSubmitting">Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>

<script>
function attendanceComponent(config) {
    return {
        locations: config.locations || [],
        selectedLocationId: '',
        selectedRadius: 100,
        userLat: null,
        userLng: null,
        geoLoading: false,
        geoError: null,
        distanceMeters: null,
        isWithinRadius: false,
        photoPreview: null,
        photoName: '',
        isSubmitting: false,

        onLocationChange() {
            const loc = this.locations.find(l => l.id == this.selectedLocationId);
            if (loc) {
                this.selectedRadius = loc.radius;
                if (this.userLat && this.userLng) {
                    this.calculateDistance();
                }
            } else {
                this.distanceMeters = null;
                this.isWithinRadius = false;
            }
        },

        fetchLocation() {
            if (!navigator.geolocation) {
                this.geoError = 'Browser perangkat Anda tidak mendukung Geolocation.';
                return;
            }

            this.geoLoading = true;
            this.geoError = null;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.userLat = pos.coords.latitude;
                    this.userLng = pos.coords.longitude;
                    this.geoLoading = false;
                    this.calculateDistance();
                },
                (err) => {
                    this.geoLoading = false;
                    switch (err.code) {
                        case err.PERMISSION_DENIED:
                            this.geoError = 'Izin akses lokasi ditolak oleh pengguna. Mohon izinkan akses GPS di pengaturan browser.';
                            break;
                        case err.POSITION_UNAVAILABLE:
                            this.geoError = 'Informasi lokasi GPS tidak tersedia pada perangkat.';
                            break;
                        case err.TIMEOUT:
                            this.geoError = 'Waktu permintaan lokasi habis (timeout). Silakan coba lagi.';
                            break;
                        default:
                            this.geoError = 'Gagal mendeteksi lokasi GPS: ' + err.message;
                    }
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        },

        calculateDistance() {
            const loc = this.locations.find(l => l.id == this.selectedLocationId);
            if (!loc || !this.userLat || !this.userLng) return;

            // Haversine client-side estimate for immediate UI guidance
            const R = 6371000;
            const dLat = (loc.latitude - this.userLat) * Math.PI / 180;
            const dLon = (loc.longitude - this.userLng) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(this.userLat * Math.PI / 180) * Math.cos(loc.latitude * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const dist = R * c;

            this.distanceMeters = Math.round(dist * 100) / 100;
            this.isWithinRadius = this.distanceMeters <= loc.radius;
        },

        formatCoord(num) {
            return Number(num).toFixed(6);
        },

        handlePhotoSelect(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Check size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran foto terlalu besar. Maksimal 5 MB.');
                e.target.value = '';
                return;
            }

            this.photoName = file.name;
            const reader = new FileReader();
            reader.onload = (event) => {
                this.photoPreview = event.target.result;
            };
            reader.readAsDataURL(file);

            // Sync file to main cameraInput if selected from gallery
            if (e.target.id === 'galleryInput') {
                const mainInput = document.getElementById('cameraInput');
                if (mainInput) {
                    mainInput.files = e.target.files;
                }
            }
        },

        clearPhoto() {
            this.photoPreview = null;
            this.photoName = '';
            const cInput = document.getElementById('cameraInput');
            const gInput = document.getElementById('galleryInput');
            if (cInput) cInput.value = '';
            if (gInput) gInput.value = '';
        },

        handleSubmit(e) {
            if (!this.isWithinRadius || !this.photoPreview) {
                e.preventDefault();
                alert('Pastikan posisi Anda berada dalam radius dan foto selfie telah diambil.');
                return;
            }
            this.isSubmitting = true;
        }
    };
}
</script>
@endsection
