@php $pageTitle = 'Kelola Kalender Kerja'; @endphp

<x-app-layout :title="'Kelola Kalender Kerja - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: false,
        modalEdit: false,
        selectedDay: null,
        yearFilter: '{{ $year }}',
        specialDays: {{ $specialDays->toJson() }},
        changeYear() {
            window.location.href = '?year=' + this.yearFilter;
        },
        formatDate(dateStr) {
            if (!dateStr) return '-';
            try {
                const d = new Date(dateStr);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}/${month}/${year}`;
            } catch (e) {
                return dateStr;
            }
        },
        getDayName(dateStr) {
            if (!dateStr) return '-';
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            try {
                const d = new Date(dateStr);
                return days[d.getDay()];
            } catch (e) {
                return '-';
            }
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kelola Kalender Kerja</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Atur hari libur mingguan, hari libur khusus, dan hari kerja khusus untuk absensi.</p>
            </div>
            <div class="flex items-center gap-3">
                <select x-model="yearFilter" @change="changeYear" class="text-sm px-3 py-2 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-purple-200">
                    @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
                <button type="button" @click="modalAdd = true" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Hari Khusus</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Weekly Holidays Settings -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Hari Libur Mingguan</h3>
                    <p class="text-xs text-slate-500 mb-4">Pilih hari yang secara rutin dianggap sebagai hari libur. Pada hari tersebut, absensi tidak dapat dilakukan.</p>
                    
                    <form action="{{ route('admin.work-calendar.update-weekly') }}" method="POST">
                        @csrf
                        <div class="space-y-3 mb-6">
                            @foreach($days as $day)
                                @php
                                    $dayIndo = str_replace(
                                        ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                                        ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                                        $day
                                    );
                                @endphp
                                <label class="flex items-center gap-3 p-3 rounded-xl border {{ !empty($weeklyHolidays[$day]) ? 'border-purple-200 bg-purple-50/50' : 'border-slate-100 hover:bg-slate-50' }} cursor-pointer transition">
                                    <input type="checkbox" name="holidays[]" value="{{ $day }}" class="w-4 h-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500" {{ !empty($weeklyHolidays[$day]) ? 'checked' : '' }}>
                                    <span class="text-sm font-medium {{ !empty($weeklyHolidays[$day]) ? 'text-purple-900 font-bold' : 'text-slate-700' }}">{{ $dayIndo }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow-xs transition">Simpan Pengaturan</button>
                    </form>
                </div>
            </div>

            <!-- Special Days Table -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="text-lg font-bold text-slate-900">Daftar Hari Khusus Tahun {{ $year }}</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-white text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100 text-xs">
                                    <th class="py-3.5 px-4">Tanggal</th>
                                    <th class="py-3.5 px-4">Hari</th>
                                    <th class="py-3.5 px-4">Keterangan</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($specialDays as $day)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4 font-semibold text-slate-900" x-text="formatDate('{{ $day->date }}')"></td>
                                        <td class="py-3.5 px-4 text-slate-600 text-xs font-medium" x-text="getDayName('{{ $day->date }}')"></td>
                                        <td class="py-3.5 px-4 text-slate-700">{{ $day->description }}</td>
                                        <td class="py-3.5 px-4 text-center">
                                            @if($day->status === 'holiday')
                                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Hari Libur</span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Hari Kerja</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="selectedDay = {{ $day->toJson() }}; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition cursor-pointer">Edit</button>
                                                <form action="{{ route('admin.work-calendar.destroy', $day->id) }}" method="POST" class="inline" data-confirm="Hapus hari khusus ini?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition cursor-pointer">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-slate-400 text-sm">
                                            Belum ada hari khusus pada tahun {{ $year }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: TAMBAH HARI KHUSUS -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Hari Khusus</h3>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.work-calendar.store') }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal *</label>
                            <input type="date" name="date" required class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan *</label>
                            <input type="text" name="description" required placeholder="Contoh: Hari Natal / Cuti Bersama" class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status *</label>
                            <select name="status" required class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none bg-white">
                                <option value="holiday">Hari Libur</option>
                                <option value="working_day">Hari Kerja (Khusus)</option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">Hari Kerja Khusus akan override hari libur mingguan pada tanggal tersebut.</p>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex gap-2">
                            <button type="button" @click="modalAdd = false" class="flex-1 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="flex-1 py-2.5 text-sm font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white shadow-xs">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: EDIT HARI KHUSUS -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Hari Khusus</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form :action="'/admin/work-calendar/' + (selectedDay ? selectedDay.id : '')" method="POST" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal *</label>
                            <input type="date" name="date" :value="selectedDay ? selectedDay.date : ''" required class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan *</label>
                            <input type="text" name="description" :value="selectedDay ? selectedDay.description : ''" required class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status *</label>
                            <select name="status" :value="selectedDay ? selectedDay.status : ''" required class="w-full text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 outline-none bg-white">
                                <option value="holiday">Hari Libur</option>
                                <option value="working_day">Hari Kerja (Khusus)</option>
                            </select>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex gap-2">
                            <button type="button" @click="modalEdit = false" class="flex-1 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="flex-1 py-2.5 text-sm font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white shadow-xs">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
