<x-app-layout title="Kalender Internal">
<div x-data="calendarApp()" x-init="init()" class="h-full flex flex-col">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kalender Internal</h1>
            <p class="text-sm text-slate-500 mt-1">Jadwal event dan kegiatan operasional tim.</p>
        </div>
        <div class="flex items-center gap-3">
            <select x-model="agendaFilter" class="text-xs sm:text-sm border-slate-200 rounded-xl bg-slate-50 text-slate-700 py-1.5 focus:ring-blue-500 focus:border-blue-500">
                <option value="Semua">Semua Agenda</option>
                <option value="Pribadi">Agenda Pribadi</option>
                <option value="Tim">Agenda Tim</option>
            </select>
            <button @click="modalAddAgenda = true" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                <svg class="w-4 h-4 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Tambah Agenda</span>
            </button>
            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
            <button @click="prevMonth()" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition shadow-sm text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <h2 class="text-sm sm:text-lg font-bold text-slate-800 w-28 sm:w-40 text-center" x-text="monthNames[month] + ' ' + year"></h2>
            <button @click="nextMonth()" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition shadow-sm text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex-1 flex flex-col overflow-hidden relative">
        
        <!-- Loading Overlay -->
        <div x-show="loading" class="absolute inset-0 z-10 bg-white/50 backdrop-blur-sm flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Days Header -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            <template x-for="day in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']">
                <div class="py-2.5 text-center text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider" x-text="day"></div>
            </template>
        </div>

        <!-- Days Grid -->
        <div class="flex-1 grid grid-cols-7 grid-rows-6 bg-slate-200 gap-px overflow-hidden">
            <template x-for="(dateObj, index) in calendarDays" :key="index">
                <div 
                    class="bg-white p-1 sm:p-2 flex flex-col min-h-[80px] transition group hover:bg-slate-50 min-w-0"
                    :class="{'opacity-50': !dateObj.isCurrentMonth, 'bg-blue-50/20': isToday(dateObj.date)}"
                >
                    <div class="flex justify-between items-center mb-1">
                        <span 
                            class="text-xs sm:text-sm font-semibold w-6 h-6 sm:w-7 sm:h-7 flex items-center justify-center rounded-full shrink-0"
                            :class="isToday(dateObj.date) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-700 group-hover:text-blue-600'"
                            x-text="dateObj.day"
                        ></span>
                    </div>

                    <!-- Events Container -->
                    <div class="flex-1 overflow-y-auto space-y-1 hide-scrollbar">
                        <template x-for="evt in getEventsForDate(dateObj.date)" :key="evt.id">
                            <div 
                                @click="openEventDetails(evt)"
                                class="flex flex-col gap-0.5 text-[10px] sm:text-xs p-1.5 sm:p-2 rounded-lg border cursor-pointer transition hover:opacity-80"
                                :class="getEventColor(evt.status)"
                                :title="evt.title"
                            >
                                <div class="font-bold truncate flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="getEventDotColor(evt.status)"></span>
                                    <span class="truncate" x-text="evt.title"></span>
                                </div>
                                <div class="text-[9px] sm:text-[10px] opacity-80 hidden sm:block" x-text="evt.waktu_mulai + ' - ' + evt.waktu_selesai"></div>
                                <template x-if="evt.sales && evt.sales.length > 0">
                                    <div class="text-[9px] sm:text-[10px] font-medium truncate opacity-90 hidden sm:block" x-text="'Sales: ' + evt.sales.map(s => s.name).join(', ')"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Event Detail Modal -->
    <div x-show="modalEventDetail" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-0">
        <div x-show="modalEventDetail" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="modalEventDetail = false"></div>
        <div x-show="modalEventDetail" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg transform transition-all">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-900">Detail Event</h3>
                <button @click="modalEventDetail = false" class="text-slate-400 hover:text-slate-600 transition p-1 rounded-lg hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6" x-show="selectedEvent">
                <div class="mb-4">
                    <div class="text-xl font-bold text-slate-900 mb-1" x-text="selectedEvent?.title"></div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200" x-text="selectedEvent?.jenis"></span>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <div>
                            <div class="font-medium text-slate-900" x-text="selectedEvent ? formatDate(selectedEvent.tanggal) : ''"></div>
                            <div class="text-slate-500" x-text="selectedEvent?.waktu_mulai + ' - ' + selectedEvent?.waktu_selesai"></div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <div class="text-slate-700" x-text="selectedEvent?.lokasi"></div>
                    </div>
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <div class="text-slate-700">
                            <span class="text-xs text-slate-500 block mb-1">Dikelola oleh (EO):</span>
                            <span class="font-medium" x-text="selectedEvent?.eo_name"></span>
                        </div>
                    </div>
                </div>

                <!-- Assignment Section -->
                <div class="mt-6 border-t border-slate-100 pt-4">
                    <h4 class="text-sm font-bold text-slate-900 mb-3">Penugasan Tim</h4>
                    
                    <div class="space-y-4">
                        <template x-for="spv in selectedEvent?.spvs" :key="spv.id">
                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div class="font-semibold text-slate-800 text-sm mb-2" x-text="spv.name + ' (SPV)'"></div>
                                <div class="pl-2 border-l-2 border-blue-200 space-y-1">
                                    <template x-for="sales in getSalesForSpv(spv.id)" :key="sales.id">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                            <span class="text-xs text-slate-600" x-text="sales.name"></span>
                                        </div>
                                    </template>
                                    <div x-show="getSalesForSpv(spv.id).length === 0" class="text-xs text-slate-400 italic">Belum ada Sales ditugaskan.</div>
                                </div>
                            </div>
                        </template>
                        <div x-show="!selectedEvent?.spvs?.length" class="text-xs text-slate-500 italic">Belum ada SPV ditugaskan.</div>
                    </div>
                </div>

                <div x-show="selectedEvent?.deskripsi" class="mt-6 border-t border-slate-100 pt-4">
                    <h4 class="text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Catatan Tambahan</h4>
                    <p class="text-sm text-slate-700 whitespace-pre-line" x-text="selectedEvent?.deskripsi"></p>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl">
                <button type="button" @click="modalEventDetail = false" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Agenda -->
    <div x-show="modalAddAgenda" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="modalAddAgenda" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="modalAddAgenda = false"></div>
        <div x-show="modalAddAgenda" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-xl w-full max-w-xl flex flex-col max-h-[90vh]">
            <form action="{{ route('calendar.meetings.store') }}" method="POST" class="flex flex-col h-full">
                @csrf
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl">
                    <h3 class="text-lg font-bold text-slate-900">Buat Agenda Baru</h3>
                    <button type="button" @click="modalAddAgenda = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Agenda <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required class="w-full rounded-xl border-slate-200 focus:ring-blue-500 focus:border-blue-500 text-sm placeholder:text-slate-400" placeholder="Contoh: Kunjungan SMA N 1">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal <span class="text-rose-500">*</span></label>
                            <input type="date" name="tanggal" required class="w-full rounded-xl border-slate-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Jenis Agenda <span class="text-rose-500">*</span></label>
                            <select name="jenis" required class="w-full rounded-xl border-slate-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="Kunjungan Sekolah">Kunjungan Sekolah</option>
                                <option value="Rapat Internal">Rapat Internal</option>
                                <option value="Koordinasi">Koordinasi</option>
                                <option value="Follow Up">Follow Up</option>
                                <option value="Event">Event</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Mulai <span class="text-rose-500">*</span></label>
                            <input type="time" name="waktu_mulai" required class="w-full rounded-xl border-slate-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Selesai <span class="text-rose-500">*</span></label>
                            <input type="time" name="waktu_selesai" required class="w-full rounded-xl border-slate-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Lokasi / Instansi Tujuan</label>
                        <input type="text" name="lokasi" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 focus:border-blue-500 text-sm placeholder:text-slate-400" placeholder="Contoh: SMA N 1 Cirebon">
                    </div>
                    
                    @if(isset($bawahan) && count($bawahan) > 0)
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Anggota Tim (Opsional)</label>
                        <p class="text-[10px] text-slate-500 mb-2">Anggota Tim dikosongkan = Agenda Pribadi.</p>
                        
                        <div class="relative" @click.away="dropdownOpen = false">
                            <div class="w-full border border-slate-200 rounded-xl bg-white p-1.5 flex flex-wrap gap-1.5 min-h-[42px] cursor-text focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition-shadow" @click="dropdownOpen = true; $refs.searchInput.focus()">
                                <template x-for="id in selectedUserIds" :key="id">
                                    <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs px-2 py-1 rounded-lg">
                                        <span x-text="getUser(id)?.name"></span>
                                        <button type="button" @click.stop="removeUser(id)" class="text-blue-400 hover:text-blue-600 focus:outline-none">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                        <input type="hidden" name="assigned_users[]" :value="id">
                                    </span>
                                </template>
                                <input x-ref="searchInput" x-model="searchUser" @focus="dropdownOpen = true" type="text" class="flex-1 min-w-[100px] border-none focus:ring-0 text-sm p-1 placeholder:text-slate-400 bg-transparent" placeholder="Cari & pilih anggota...">
                            </div>
                            
                            <div x-show="dropdownOpen" x-transition class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                <template x-for="user in filteredUsers" :key="user.id">
                                    <div @click.stop="toggleUser(user.id)" class="px-3 py-2 cursor-pointer hover:bg-slate-50 flex items-center justify-between border-b border-slate-100 last:border-0">
                                        <div>
                                            <div class="text-sm font-medium text-slate-700" x-text="user.name"></div>
                                            <div class="text-[10px] text-slate-500" x-text="user.role"></div>
                                        </div>
                                        <div x-show="selectedUserIds.includes(user.id)" class="text-blue-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filteredUsers.length === 0" class="px-3 py-4 text-center text-sm text-slate-500">
                                    Tidak ada anggota ditemukan.
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi Tambahan</label>
                        <textarea name="deskripsi" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 focus:border-blue-500 text-sm placeholder:text-slate-400" placeholder="Catatan opsional..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" @click="modalAddAgenda = false" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition shadow-sm">Simpan Agenda</button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<script>
    function calendarApp() {
        return {
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            month: new Date().getMonth(),
            year: new Date().getFullYear(),
            calendarDays: [],
            events: [],
            loading: false,
            agendaFilter: 'Semua',
            modalEventDetail: false,
            modalAddAgenda: false,
            selectedEvent: null,

            usersList: {!! isset($bawahan) ? json_encode($bawahan) : '[]' !!},
            selectedUserIds: [],
            searchUser: '',
            dropdownOpen: false,

            get filteredUsers() {
                if (this.searchUser === '') return this.usersList;
                return this.usersList.filter(u => u.name.toLowerCase().includes(this.searchUser.toLowerCase()) || u.role.toLowerCase().includes(this.searchUser.toLowerCase()));
            },
            
            toggleUser(id) {
                if (this.selectedUserIds.includes(id)) {
                    this.selectedUserIds = this.selectedUserIds.filter(i => i !== id);
                } else {
                    this.selectedUserIds.push(id);
                }
            },
            
            removeUser(id) {
                this.selectedUserIds = this.selectedUserIds.filter(i => i !== id);
            },
            
            getUser(id) {
                return this.usersList.find(u => u.id === id);
            },

            init() {
                this.generateCalendar();
                this.fetchEvents();
            },

            generateCalendar() {
                this.calendarDays = [];
                let firstDay = new Date(this.year, this.month, 1).getDay();
                firstDay = firstDay === 0 ? 6 : firstDay - 1; // Adjust for Monday start
                
                let daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                let daysInPrevMonth = new Date(this.year, this.month, 0).getDate();

                // Previous month overflow
                for (let i = firstDay - 1; i >= 0; i--) {
                    this.calendarDays.push({
                        day: daysInPrevMonth - i,
                        isCurrentMonth: false,
                        date: this.formatDateString(this.year, this.month - 1, daysInPrevMonth - i)
                    });
                }

                // Current month
                for (let i = 1; i <= daysInMonth; i++) {
                    this.calendarDays.push({
                        day: i,
                        isCurrentMonth: true,
                        date: this.formatDateString(this.year, this.month, i)
                    });
                }

                // Next month overflow
                let remaining = 42 - this.calendarDays.length;
                for (let i = 1; i <= remaining; i++) {
                    this.calendarDays.push({
                        day: i,
                        isCurrentMonth: false,
                        date: this.formatDateString(this.year, this.month + 1, i)
                    });
                }
            },

            formatDateString(year, month, day) {
                let d = new Date(year, month, day);
                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            },

            isToday(dateString) {
                const today = new Date();
                return dateString === this.formatDateString(today.getFullYear(), today.getMonth(), today.getDate());
            },

            prevMonth() {
                this.month--;
                if (this.month < 0) {
                    this.month = 11;
                    this.year--;
                }
                this.generateCalendar();
            },

            nextMonth() {
                this.month++;
                if (this.month > 11) {
                    this.month = 0;
                    this.year++;
                }
                this.generateCalendar();
            },

            async fetchEvents() {
                this.loading = true;
                try {
                    const response = await fetch('/api/calendar/events');
                    if (response.ok) {
                        this.events = await response.json();
                    }
                } catch (error) {
                    console.error('Failed to fetch events', error);
                } finally {
                    this.loading = false;
                }
            },

            getEventsForDate(dateString) {
                return this.events.filter(e => {
                    if (e.tanggal !== dateString) return false;
                    if (this.agendaFilter === 'Pribadi') return e.is_personal;
                    if (this.agendaFilter === 'Tim') return !e.is_personal;
                    return true;
                }).sort((a, b) => a.waktu_mulai.localeCompare(b.waktu_mulai));
            },

            getEventColor(status) {
                if (status === 'Cancelled') return 'bg-rose-50 text-rose-700 border-rose-200';
                if (status === 'Completed') return 'bg-slate-50 text-slate-600 border-slate-200';
                return 'bg-blue-50 text-blue-700 border-blue-200';
            },

            getEventDotColor(status) {
                if (status === 'Cancelled') return 'bg-rose-500';
                if (status === 'Completed') return 'bg-slate-500';
                return 'bg-blue-500';
            },

            openEventDetails(evt) {
                this.selectedEvent = evt;
                this.modalEventDetail = true;
            },

            getSalesForSpv(spvId) {
                if (!this.selectedEvent || !this.selectedEvent.sales) return [];
                return this.selectedEvent.sales.filter(s => s.assigned_by_spv_id === spvId);
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return `${d.getDate()} ${this.monthNames[d.getMonth()]} ${d.getFullYear()}`;
            }
        }
    }
</script>
</x-app-layout>
