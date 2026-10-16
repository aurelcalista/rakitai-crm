@php
    $pageTitle    = 'Detail Kunjungan (SPV)';
    $pageSubtitle = $visit['name'];
@endphp

<x-app-layout :title="'Detail Kunjungan - ' . $visit['name']">

    <div class="max-w-4xl mx-auto space-y-6" x-data="{ modalEdit: false, confirmDelete: false }">

        <!-- Back Button & Breadcrumbs -->
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('spv.kunjungan.index') }}" class="hover:text-blue-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Kembali ke Daftar Kunjungan Tim
            </a>
            <span>/</span>
            <span class="text-slate-900">{{ $visit['name'] }}</span>
        </div>

        <!-- Detail Card -->
        <div class="crm-card bg-white p-6 sm:p-8 space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $visit['type'] === 'Sekolah' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                            {{ $visit['type'] }}
                        </span>
                        @if($visit['is_outside_radius'] || $visit['status_verifikasi'] === 'Perlu Verifikasi')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                <svg class="w-3.5 h-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Perlu Verifikasi (Di Luar Radius)</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Terverifikasi Valid</span>
                            </span>
                        @endif
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mt-2">{{ $visit['name'] }}</h2>
                    <p class="text-xs text-slate-500 mt-1">Dilaporkan oleh Sales: <strong class="text-slate-800">{{ $visit['sales'] }}</strong></p>
                </div>
                <div class="text-right flex flex-col items-end gap-2">
                    <div>
                        <div class="text-xs font-bold text-slate-800">{{ $visit['date'] }}</div>
                        <div class="text-[11px] text-slate-400">Pukul: {{ $visit['time'] }} WIB</div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($visit['is_outside_radius'] || $visit['status_verifikasi'] === 'Perlu Verifikasi')
                            <form action="{{ route('spv.kunjungan.verifikasi', $visit['id']) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition cursor-pointer">
                                    Verifikasi Kunjungan Ini (SPV)
                                </button>
                            </form>
                        @endif
                        <button 
                            type="button" 
                            @click="modalEdit = true" 
                            class="px-3.5 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit</span>
                        </button>
                        <button 
                            type="button" 
                            @click="confirmDelete = true" 
                            class="px-3.5 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Grid Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">

                <div class="space-y-4">
                    <div>
                        <span class="text-slate-400 block font-semibold text-[10px] uppercase">Nama PIC Institusi</span>
                        <p class="font-bold text-slate-800 mt-0.5">{{ $visit['pic'] }}</p>
                    </div>

                    <div>
                        <span class="text-slate-400 block font-semibold text-[10px] uppercase">Kontak WhatsApp</span>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $visit['whatsapp']) }}" target="_blank"
                           class="font-bold text-emerald-600 hover:text-emerald-700 mt-0.5 inline-flex items-center gap-1">
                            <span>{{ $visit['whatsapp'] }}</span>
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        </a>
                    </div>

                    <div>
                        <span class="text-slate-400 block font-semibold text-[10px] uppercase">Alamat / Lokasi</span>
                        <p class="font-medium text-slate-700 mt-0.5">{{ $visit['address'] }}</p>
                    </div>

                    <div>
                        <span class="text-slate-400 block font-semibold text-[10px] uppercase">Dosen Pemateri</span>
                        @if(!empty($visit['dosen_pemateri']) && $visit['dosen_pemateri'] !== '-')
                            <p class="font-bold text-purple-700 mt-0.5 inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                </svg>
                                <span>{{ $visit['dosen_pemateri'] }}</span>
                            </p>
                        @elseif(!empty($visit['dosen']) && $visit['dosen'] !== '-')
                            <p class="font-bold text-purple-700 mt-0.5 inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                </svg>
                                <span>{{ $visit['dosen'] }}</span>
                            </p>
                        @else
                            <p class="font-medium text-slate-400 mt-0.5 italic">Tidak ada penugasan dosen pemateri</p>
                        @endif
                    </div>
                </div>

                <div class="space-y-4">
                    @if($visit['type'] === 'Sekolah')
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi Mahasiswa</span>
                            <p class="font-bold text-blue-600 mt-0.5">{{ $visit['potensi_mahasiswa'] }}</p>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Workshop AI / Robotik</span>
                            <p class="font-bold mt-0.5 {{ $visit['kesediaan_training_ai'] ? 'text-emerald-600' : 'text-slate-500' }}">
                                {{ $visit['kesediaan_training_ai'] ? 'Bersedia' : 'Belum Bersedia' }}
                            </p>
                        </div>
                        @php
                            $detailPotensi = $visit['detail_potensi_mahasiswa'] ?? $visit['detail_beasiswa'] ?? null;
                        @endphp
                        @if($detailPotensi && $detailPotensi !== '-')
                        <div class="mt-4 pt-4 border-t border-blue-200/50">
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Detail Potensi Mahasiswa</span>
                            <p class="font-medium text-slate-700 mt-0.5">{{ $detailPotensi }}</p>
                        </div>
                        @endif
                    @else
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Bidang Usaha</span>
                            <p class="font-bold text-slate-800 mt-0.5">{{ $visit['bidang_usaha'] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi S1</span>
                                <p class="font-bold text-purple-600 mt-0.5">{{ $visit['potensi_s1'] }}</p>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi S2</span>
                                <p class="font-bold text-purple-600 mt-0.5">{{ $visit['potensi_s2'] }}</p>
                            </div>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi CSR</span>
                            <p class="font-bold text-slate-800 mt-0.5">{{ $visit['potensi_csr'] }}</p>
                        </div>
                    @endif

                    <div>
                        <span class="text-slate-400 block font-semibold text-[10px] uppercase">Waktu Submit Laporan</span>
                        <p class="font-medium text-slate-700 mt-0.5">{{ $visit['created_at'] }}</p>
                    </div>
                </div>

            </div>

            <!-- Catatan Laporan -->
            <div class="space-y-2 text-xs">
                <span class="text-slate-400 block font-semibold text-[10px] uppercase">Catatan & Hasil Audiensi Sales</span>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-slate-800 leading-relaxed whitespace-pre-line">
                    {{ $visit['notes'] }}
                </div>
            </div>

            <!-- Dokumentasi Foto -->
            @if($visit['photo'])
                <div class="space-y-2 text-xs">
                    <span class="text-slate-400 block font-semibold text-[10px] uppercase">Dokumentasi Foto Lapangan</span>
                    <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 max-h-96 flex items-center justify-center">
                        <img src="{{ $visit['photo'] }}" alt="Foto Dokumentasi Kunjungan" class="w-full h-full object-cover">
                    </div>
                    <a href="{{ $visit['photo'] }}" target="_blank"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-blue-600 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Buka foto ukuran penuh
                    </a>
                </div>
            @endif

        </div>

        <!-- MODAL EDIT KUNJUNGAN -->
        <div 
            x-show="modalEdit" 
            class="fixed inset-0 z-50 overflow-y-auto"
            x-cloak
            @keydown.escape.window="modalEdit = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalEdit = false"></div>

                <div class="relative z-10 bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-base font-bold text-slate-900">Edit Data Kunjungan</h3>
                        <button type="button" @click="modalEdit = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form 
                        action="{{ route('spv.kunjungan.update', $visit['id']) }}" 
                        method="POST" 
                        enctype="multipart/form-data" 
                        class="space-y-4 text-xs"
                    >
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Kunjungan</label>
                                <input type="date" name="tanggal" required value="{{ $visit['raw_date'] ?? '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Waktu (WIB)</label>
                                <input type="time" name="waktu" value="{{ $visit['raw_time'] ?? $visit['time'] ?? '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Institusi / Tujuan</label>
                            <input type="text" name="nama_institusi" required value="{{ $visit['name'] }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama PIC</label>
                                <input type="text" name="pic_name" value="{{ $visit['pic'] !== '-' ? $visit['pic'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">WhatsApp PIC</label>
                                <input type="text" name="pic_whatsapp" value="{{ $visit['whatsapp'] !== '-' ? $visit['whatsapp'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Alamat Institusi</label>
                            <input type="text" name="alamat" value="{{ $visit['address'] !== '-' ? $visit['address'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Dosen / Pemateri <span class="text-slate-400 font-normal">(Opsional)</span></label>
                            <input type="text" name="dosen_pemateri" value="{{ $visit['dosen_pemateri'] ?? ($visit['dosen'] !== '-' ? $visit['dosen'] : '') }}" placeholder="Contoh: Dr. Ir. H. Ahmad, M.T." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <p class="text-[11px] text-slate-400 mt-1">Tambahkan nama dosen pemateri / narasumber jika saat awal input belum tercatat.</p>
                        </div>

                        @if($visit['type'] === 'Sekolah')
                            <div class="space-y-3 p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Potensi Mahasiswa</label>
                                    <input type="text" name="potensi_mahasiswa" value="{{ $visit['potensi_mahasiswa'] !== '-' ? $visit['potensi_mahasiswa'] : '' }}" placeholder="Contoh: 50 Siswa Kelas XII" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Detail Potensi</label>
                                    <input type="text" name="detail_potensi_mahasiswa" value="{{ $visit['detail_potensi_mahasiswa'] !== '-' ? $visit['detail_potensi_mahasiswa'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                            </div>
                        @else
                            <div class="space-y-3 p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Bidang Usaha</label>
                                    <input type="text" name="bidang_usaha" value="{{ $visit['bidang_usaha'] !== '-' ? $visit['bidang_usaha'] : '' }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Potensi S1</label>
                                        <input type="text" name="potensi_s1" value="{{ $visit['potensi_s1'] !== '-' ? $visit['potensi_s1'] : '' }}" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Potensi S2</label>
                                        <input type="text" name="potensi_s2" value="{{ $visit['potensi_s2'] !== '-' ? $visit['potensi_s2'] : '' }}" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">CSR</label>
                                        <input type="text" name="potensi_csr" value="{{ $visit['potensi_csr'] !== '-' ? $visit['potensi_csr'] : '' }}" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan & Hasil Kunjungan</label>
                            <textarea name="catatan" rows="3" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none">{{ $visit['notes'] !== '-' ? $visit['notes'] : '' }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 items-center">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Status Kunjungan</label>
                                <select name="status" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                    <option value="Selesai" {{ ($visit['status'] ?? '') === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                    <option value="Proses" {{ ($visit['status'] ?? '') === 'Proses' ? 'selected' : '' }}>Proses</option>
                                    <option value="Menunggu" {{ ($visit['status'] ?? '') === 'Menunggu' ? 'selected' : '' }}>Menunggu</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Ganti Foto (Opsional)</label>
                                <input type="file" name="foto" accept="image/*" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold cursor-pointer shadow-xs">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS -->
        <div 
            x-show="confirmDelete" 
            class="fixed inset-0 z-50 overflow-y-auto"
            x-cloak
            @keydown.escape.window="confirmDelete = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="confirmDelete = false"></div>

                <div class="relative z-10 bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-sm p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Hapus Kunjungan Ini?</h3>
                    <p class="text-sm text-slate-500 mb-6">Data kunjungan ini akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
                    <div class="flex items-center gap-3 justify-center">
                        <button
                            type="button"
                            @click="confirmDelete = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <form
                            action="{{ route('spv.kunjungan.destroy', $visit['id']) }}"
                            method="POST"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition cursor-pointer shadow-xs"
                            >
                                Ya, Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>