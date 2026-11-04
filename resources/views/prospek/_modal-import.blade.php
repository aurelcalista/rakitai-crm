{{-- Modal Import Massal Data Prospek dari Google Sheets / Excel --}}
<div 
    x-show="modalImportProspek" 
    x-cloak 
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog" 
    aria-modal="true"
    @keydown.escape.window="if (!isUploading && !isProcessing) modalImportProspek = false"
    x-data="modalImportProspekData()"
>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        {{-- Backdrop --}}
        <div 
            x-show="modalImportProspek" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            @click="if (!isUploading && !isProcessing) modalImportProspek = false"
        ></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Dialog Box --}}
        <div 
            x-show="modalImportProspek" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block w-full max-w-4xl p-6 my-8 text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-slate-200 z-10 max-h-[90vh] flex flex-col"
        >
            {{-- Header --}}
            <div class="flex items-start justify-between pb-4 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-lg font-bold text-slate-900">Import Massal Data Prospek</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">Khusus CS</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Unggah berkas Google Sheets / Excel (.xlsx, .csv) untuk memasukkan ratusan prospek sekaligus.</p>
                    </div>
                </div>
                <button 
                    type="button"
                    @click="if (!isUploading && !isProcessing) modalImportProspek = false" 
                    class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Error Alert Banner --}}
            <div x-show="errorMessage" x-cloak class="mt-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2">
                <svg class="w-4 h-4 shrink-0 mt-0.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1" x-text="errorMessage"></div>
                <button type="button" @click="errorMessage = null" class="text-rose-400 hover:text-rose-600">✕</button>
            </div>

            {{-- Body Content --}}
            <div class="overflow-y-auto py-4 flex-1 space-y-5 custom-scrollbar pr-1">

                {{-- STEP 1: UPLOAD BERKAS --}}
                <div x-show="step === 1" class="space-y-4">
                    {{-- Dropzone --}}
                    <div 
                        class="border-2 border-dashed rounded-2xl p-6 sm:p-8 text-center transition flex flex-col items-center justify-center cursor-pointer"
                        :class="isDragging ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-400 bg-slate-50/50 hover:bg-indigo-50/30'"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false; handleFileDrop($event)"
                        @click="$refs.fileInput.click()"
                    >
                        <input 
                            type="file" 
                            x-ref="fileInput" 
                            class="hidden" 
                            accept=".xlsx, .xls, .csv, text/csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                            @change="handleFileChange($event)"
                        />

                        <div class="w-14 h-14 rounded-2xl bg-indigo-100/70 text-indigo-600 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800">Tarik & Letakkan berkas spreadsheet ke sini</h4>
                        <p class="text-xs text-slate-500 mt-1">atau <span class="text-indigo-600 font-semibold underline">pilih berkas dari komputer</span></p>
                        <div class="mt-3 flex items-center gap-2 text-[11px] text-slate-400 font-medium">
                            <span class="px-2 py-0.5 bg-white border border-slate-200 rounded-md font-semibold">.XLSX</span>
                            <span class="px-2 py-0.5 bg-white border border-slate-200 rounded-md font-semibold">.XLS</span>
                            <span class="px-2 py-0.5 bg-white border border-slate-200 rounded-md font-semibold">.CSV</span>
                            <span>Maksimal 15 MB</span>
                        </div>
                    </div>

                    {{-- Selected File Info --}}
                    <div x-show="selectedFile" x-cloak class="p-3.5 bg-indigo-50/60 rounded-2xl border border-indigo-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
                                📊
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-bold text-slate-800 truncate max-w-xs sm:max-w-md" x-text="selectedFile ? selectedFile.name : ''"></p>
                                <p class="text-[11px] text-slate-500" x-text="selectedFile ? formatFileSize(selectedFile.size) : ''"></p>
                            </div>
                        </div>
                        <button type="button" @click="selectedFile = null; $refs.fileInput.value = ''" class="text-slate-400 hover:text-slate-600 text-xs px-2 py-1">
                            Ganti
                        </button>
                    </div>

                    {{-- Feature Helper Notes --}}
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-2.5 text-xs text-slate-600 text-left">
                        <div class="flex items-center gap-2 font-bold text-slate-800">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Panduan & Kecocokan Format Otomatis:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-slate-500 text-[11px] leading-relaxed">
                            <li><strong>Mendukung Berbagai Format Spreadsheet:</strong> Export Google Forms (Survey Alumni, Miniclass, Bootcamp, dsb.), Sheet Kunjungan Sekolah, maupun Leads Marketing.</li>
                            <li><strong>Pemetaan Kolom Cerdas:</strong> Nama, No. WhatsApp, WA Ortu, Asal Sekolah, Jurusan/Kelas, dan Minat Studi akan dipetakan otomatis.</li>
                            <li><strong>Tidak Ada Data Yang Dibuang:</strong> Kolom pertanyaan formulir, email, alamat, pekerjaan, dan catatan tambahan otomatis dirangkum ke field <em>Notes</em> Prospek.</li>
                            <li><strong>Pencegahan Duplikat:</strong> Sistem secara otomatis memeriksa nomor WhatsApp agar tidak terjadi duplikasi kontak prospek.</li>
                        </ul>
                        <div class="pt-1 flex items-center justify-between border-t border-slate-200/60 mt-2">
                            <span class="text-[11px] text-slate-400">Ingin melihat contoh susunan kolom standar?</span>
                            <a 
                                href="{{ route('cs.prospek.import.template') }}" 
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-700 underline"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Unduh Template Contoh (.xlsx)</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: PREVIEW & MAPPING REVIEW --}}
                <div x-show="step === 2" class="space-y-5" x-cloak>
                    {{-- Summary Statistics Cards --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-left">
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[11px] font-semibold text-slate-500 block">Total Baris File</span>
                            <span class="text-xl font-bold text-slate-900" x-text="previewData.total_rows || 0"></span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-100">
                            <span class="text-[11px] font-semibold text-emerald-700 block">🟢 Siap Diimpor</span>
                            <span class="text-xl font-bold text-emerald-800" x-text="previewData.valid_count || 0"></span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-100">
                            <span class="text-[11px] font-semibold text-amber-700 block">🟡 Duplikat (Lewati)</span>
                            <span class="text-xl font-bold text-amber-800" x-text="previewData.duplicate_count || 0"></span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-100">
                            <span class="text-[11px] font-semibold text-rose-700 block">🔴 Tidak Lengkap</span>
                            <span class="text-xl font-bold text-rose-800" x-text="previewData.invalid_count || 0"></span>
                        </div>
                    </div>

                    {{-- Default Import Options --}}
                    <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 space-y-3 text-left">
                        <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <span>Opsi Penanganan Data & Default Penyimpanan</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Sumber Prospek (Default)</label>
                                <select x-model="defaultSource" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white font-medium focus:ring-2 focus:ring-indigo-500/20">
                                    <option value="Google Sheets Import">Google Sheets Import</option>
                                    <option value="MGBK/Miniclass">MGBK/Miniclass</option>
                                    <option value="Pameran/Expo/University Day">Pameran/Expo/University Day</option>
                                    <option value="Sekolah">Sekolah</option>
                                    <option value="Sosial Media (Facebook, Instagram, X)">Sosial Media</option>
                                    <option value="Website CIC">Website CIC</option>
                                    <option value="Alumni">Alumni</option>
                                    <option value="Presensi Siswa">Presensi Siswa</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Status Awal Pipeline</label>
                                <select x-model="defaultStatus" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white font-medium focus:ring-2 focus:ring-indigo-500/20">
                                    <option value="BARU">BARU (Tahap 1)</option>
                                    <option value="KONTAK">KONTAK (Tahap 2)</option>
                                    <option value="PROSPEK">PROSPEK (Tahap 3)</option>
                                    <option value="HOT PROSPEK">HOT PROSPEK (Tahap 4)</option>
                                    <option value="FORMULIR">FORMULIR (Tahap 5)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Jika No. WhatsApp Duplikat</label>
                                <select x-model="onDuplicate" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white font-medium focus:ring-2 focus:ring-indigo-500/20">
                                    <option value="skip">Lewati Duplikat (Rekomendasi)</option>
                                    <option value="update">Perbarui Catatan Yang Ada</option>
                                    <option value="import_all">Tetap Impor Semua (Izinkan Duplikat)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Jika Baris Tanpa No. WhatsApp</label>
                                <select x-model="importIncomplete" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white font-medium focus:ring-2 focus:ring-indigo-500/20">
                                    <option value="skip">Lewati Baris Tanpa No. WA</option>
                                    <option value="import_valid_name">Tetap Impor Jika Ada Nama (No. WA Kosong)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Mapping Accordion --}}
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white text-left">
                        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex items-center justify-between cursor-pointer" @click="showMappingDetails = !showMappingDetails">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-800">Penyesuaian Pemetaan Kolom Spreadsheet</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700" x-text="(previewData.headers ? previewData.headers.length : 0) + ' Kolom'"></span>
                            </div>
                            <span class="text-xs text-indigo-600 font-semibold flex items-center gap-1">
                                <span x-text="showMappingDetails ? 'Tutup Rincian' : 'Buka & Sesuaikan'"></span>
                                <svg class="w-4 h-4 transition-transform" :class="showMappingDetails ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </div>

                        <div x-show="showMappingDetails" class="p-4 space-y-3 max-h-60 overflow-y-auto custom-scrollbar">
                            <p class="text-[11px] text-slate-500 mb-2">Sistem telah mencocokkan setiap kolom spreadsheet secara otomatis. Anda dapat menyesuaikan pasangan field CRM di bawah jika diperlukan:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <template x-for="headerItem in (previewData.headers || [])" :key="headerItem.index">
                                    <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/50 flex flex-col gap-1.5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-800 truncate" :title="headerItem.header" x-text="headerItem.header"></span>
                                            <span class="text-[10px] text-slate-400 font-medium">Kolom #<span x-text="headerItem.index + 1"></span></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-400 text-xs">➔</span>
                                            <select 
                                                class="w-full text-xs py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white font-medium focus:ring-1 focus:ring-indigo-500"
                                                x-model="columnMappings[headerItem.index]"
                                            >
                                                <template x-for="(label, key) in (previewData.crm_fields || {})" :key="key">
                                                    <option :value="key" x-text="label" :selected="columnMappings[headerItem.index] === key"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Preview First Rows Table --}}
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white text-left">
                        <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800">Contoh Baris Hasil Pembacaan (Maksimal 15 Baris Pertama)</span>
                            <span class="text-[11px] text-slate-500">Periksa kecocokan data sebelum menyimpan</span>
                        </div>
                        <div class="overflow-x-auto max-h-56 custom-scrollbar">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-100 text-slate-600 font-semibold sticky top-0 border-b border-slate-200">
                                    <tr>
                                        <th class="py-2 px-3">#</th>
                                        <th class="py-2 px-3">Status</th>
                                        <th class="py-2 px-3">Nama</th>
                                        <th class="py-2 px-3">Asal Sekolah</th>
                                        <th class="py-2 px-3">WhatsApp</th>
                                        <th class="py-2 px-3">Keterangan Validasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="row in (previewData.sample_preview || [])" :key="row.row_num">
                                        <tr class="hover:bg-slate-50 transition" :class="row.status === 'invalid' ? 'bg-rose-50/30' : (row.status === 'duplicate' ? 'bg-amber-50/30' : '')">
                                            <td class="py-2 px-3 text-slate-400 font-mono text-[11px]" x-text="row.row_num"></td>
                                            <td class="py-2 px-3 whitespace-nowrap">
                                                <span 
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-800': row.status === 'valid',
                                                        'bg-amber-100 text-amber-800': row.status === 'duplicate' || row.status === 'duplicate_file',
                                                        'bg-rose-100 text-rose-800': row.status === 'invalid'
                                                    }"
                                                    x-text="row.status === 'valid' ? 'Siap' : (row.status.includes('duplicate') ? 'Duplikat' : 'Tidak Valid')"
                                                ></span>
                                            </td>
                                            <td class="py-2 px-3 font-semibold text-slate-900 truncate max-w-[170px]" x-text="row.clean_name || '-'"></td>
                                            <td class="py-2 px-3 text-slate-700 text-xs truncate max-w-[160px]">
                                                <span x-text="row.school_name || '-'" :class="row.school_name ? 'font-semibold text-slate-800' : 'text-slate-400'"></span>
                                            </td>
                                            <td class="py-2 px-3 font-mono text-slate-700 whitespace-nowrap" x-text="row.clean_phone || '-'"></td>
                                            <td class="py-2 px-3 text-[11px] text-slate-500 truncate max-w-[180px]" x-text="row.status_reason"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: HASIL IMPORT SUKSES --}}
                <div x-show="step === 3" class="space-y-4 py-6 text-center" x-cloak>
                    <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-2xl">
                        ✓
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">Import Data Prospek Berhasil!</h4>
                        <p class="text-xs text-slate-500 mt-1">Data telah berhasil dimasukkan ke dalam database dan langsung dapat Anda kelola.</p>
                    </div>

                    <div class="max-w-md mx-auto grid grid-cols-3 gap-3 text-center pt-2">
                        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-100">
                            <span class="text-xl font-bold text-emerald-700 block" x-text="importResult.imported_count || 0"></span>
                            <span class="text-[11px] text-emerald-800 font-semibold">Berhasil Ditambah</span>
                        </div>
                        <div class="p-3 bg-amber-50 rounded-2xl border border-amber-100">
                            <span class="text-xl font-bold text-amber-700 block" x-text="importResult.skipped_count || 0"></span>
                            <span class="text-[11px] text-amber-800 font-semibold">Duplikat Dilewati</span>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-2xl border border-blue-100">
                            <span class="text-xl font-bold text-blue-700 block" x-text="importResult.updated_count || 0"></span>
                            <span class="text-[11px] text-blue-800 font-semibold">Catatan Diperbarui</span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer Controls --}}
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        x-show="step === 1"
                        @click="modalImportProspek = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        x-show="step === 2"
                        @click="step = 1; previewData = {}"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                        :disabled="isProcessing"
                    >
                        Ganti Berkas
                    </button>
                </div>

                <div>
                    {{-- Button Step 1: Upload & Preview --}}
                    <button 
                        type="button"
                        x-show="step === 1"
                        @click="uploadAndPreview()"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        :disabled="!selectedFile || isUploading"
                    >
                        <svg x-show="!isUploading" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <svg x-show="isUploading" x-cloak class="animate-spin h-4 w-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isUploading ? 'Menganalisis Berkas...' : 'Lanjutkan ke Pratinjau'">Lanjutkan ke Pratinjau</span>
                    </button>

                    {{-- Button Step 2: Confirm & Execute Import --}}
                    <button 
                        type="button"
                        x-show="step === 2"
                        @click="executeImport()"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        :disabled="isProcessing || calculateImportCount === 0"
                    >
                        <svg x-show="!isProcessing" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg x-show="isProcessing" x-cloak class="animate-spin h-4 w-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isProcessing ? 'Menyimpan ke CRM...' : 'Simpan & Import ' + calculateImportCount + ' Prospek'">Simpan & Import Prospek</span>
                    </button>

                    {{-- Button Step 3: Finish & Reload --}}
                    <button 
                        type="button"
                        x-show="step === 3"
                        @click="window.location.reload()"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold shadow-xs transition cursor-pointer"
                    >
                        Lihat Data di Tabel Prospek
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function modalImportProspekData() {
        return {
            step: 1,
            isDragging: false,
            isUploading: false,
            isProcessing: false,
            errorMessage: null,
            selectedFile: null,
            previewData: {},
            columnMappings: {},
            showMappingDetails: false,
            defaultSource: 'Google Sheets Import',
            defaultStatus: 'BARU',
            defaultType: 'Individu',
            onDuplicate: 'skip',
            importIncomplete: 'skip',
            importResult: {},

            get calculateImportCount() {
                let count = this.previewData.valid_count || 0;
                if (this.onDuplicate === 'import_all') {
                    count += (this.previewData.duplicate_count || 0);
                }
                if (this.importIncomplete === 'import_valid_name') {
                    count += (this.previewData.name_only_count || 0);
                }
                return count;
            },

            formatFileSize(bytes) {
                if (!bytes) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },

            handleFileChange(e) {
                if (e.target.files && e.target.files[0]) {
                    this.selectedFile = e.target.files[0];
                    this.errorMessage = null;
                }
            },

            handleFileDrop(e) {
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    this.selectedFile = e.dataTransfer.files[0];
                    this.errorMessage = null;
                }
            },

            async uploadAndPreview() {
                if (!this.selectedFile) return;

                this.isUploading = true;
                this.errorMessage = null;

                const formData = new FormData();
                formData.append('file', this.selectedFile);

                try {
                    const response = await fetch('{{ route('cs.prospek.import.preview') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: formData
                    });

                    const json = await response.json();

                    if (!response.ok || !json.success) {
                        throw new Error(json.message || 'Gagal membaca berkas spreadsheet.');
                    }

                    this.previewData = json.data;
                    this.columnMappings = {};
                    if (this.previewData.headers) {
                        this.previewData.headers.forEach(h => {
                            this.columnMappings[h.index] = h.target_field;
                        });
                    }

                    this.step = 2;
                } catch (err) {
                    this.errorMessage = err.message || 'Terjadi kesalahan saat mengunggah berkas.';
                } finally {
                    this.isUploading = false;
                }
            },

            async executeImport() {
                if (!this.previewData.token) return;

                this.isProcessing = true;
                this.errorMessage = null;

                try {
                    const response = await fetch('{{ route('cs.prospek.import.process') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            token: this.previewData.token,
                            mapping: this.columnMappings,
                            on_duplicate: this.onDuplicate,
                            import_incomplete: this.importIncomplete,
                            default_source: this.defaultSource,
                            default_status: this.defaultStatus,
                            default_type: this.defaultType
                        })
                    });

                    const json = await response.json();

                    if (!response.ok || !json.success) {
                        throw new Error(json.message || 'Gagal memproses import data.');
                    }

                    this.importResult = json.data;
                    this.step = 3;
                } catch (err) {
                    this.errorMessage = err.message || 'Terjadi kesalahan saat memproses import data.';
                } finally {
                    this.isProcessing = false;
                }
            }
        };
    }
</script>

