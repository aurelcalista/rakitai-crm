import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Swal = Swal;

window.confirmLogout = function() {
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari sistem CRM?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5',
            cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('logout-form')?.submit();
        }
    });
};

// ──────────────────────────────────────────────────────────────────
// School Duplicate Detection & Confirmation Modal Helper
// (Pre-Submit Validation: Zero visual clutter on the form,
//  checks on submit and prompts if a similar school exists in database)
// ──────────────────────────────────────────────────────────────────
export function normalizeSchoolName(str) {
    if (!str) return '';
    let s = String(str).toUpperCase();
    // Replace brackets and punctuation with spaces
    s = s.replace(/[()\/\\.,_-]/g, ' ');
    s = s.replace(/\s+/g, ' ').trim();

    // Standardize Indonesian school abbreviations
    s = s.replace(/\bSMKN\b/g, 'SMK NEGERI');
    s = s.replace(/\bSMAN\b/g, 'SMA NEGERI');
    s = s.replace(/\bSMAS\b/g, 'SMA SWASTA');
    s = s.replace(/\bSMKS\b/g, 'SMK SWASTA');
    s = s.replace(/\bMAN\b/g, 'MA NEGERI');
    s = s.replace(/\bMAS\b/g, 'MA SWASTA');
    s = s.replace(/\bSMPN\b/g, 'SMP NEGERI');
    s = s.replace(/\bSMPS\b/g, 'SMP SWASTA');
    s = s.replace(/\bMTSN\b/g, 'MTS NEGERI');
    s = s.replace(/\bMTSS\b/g, 'MTS SWASTA');

    s = s.replace(/\bSMK N\b/g, 'SMK NEGERI');
    s = s.replace(/\bSMA N\b/g, 'SMA NEGERI');
    s = s.replace(/\bSMP N\b/g, 'SMP NEGERI');

    // Roman numerals conversion
    s = s.replace(/\bXII\b/g, '12');
    s = s.replace(/\bXI\b/g, '11');
    s = s.replace(/\bX\b/g, '10');
    s = s.replace(/\bIX\b/g, '9');
    s = s.replace(/\bVIII\b/g, '8');
    s = s.replace(/\bVII\b/g, '7');
    s = s.replace(/\bVI\b/g, '6');
    s = s.replace(/\bV\b/g, '5');
    s = s.replace(/\bIV\b/g, '4');
    s = s.replace(/\bIII\b/g, '3');
    s = s.replace(/\bII\b/g, '2');
    s = s.replace(/\bI\b/g, '1');

    // Remove administrative terms that commonly differ in user inputs
    s = s.replace(/\b(KOTA|KABUPATEN|KAB)\b/g, '');
    return s.replace(/\s+/g, ' ').trim();
}

export function findSimilarSchool(inputName, schoolList) {
    if (!inputName || !Array.isArray(schoolList) || schoolList.length === 0) return null;

    const normInput = normalizeSchoolName(inputName);
    if (!normInput) return null;

    const getNumbers = (str) => (str.match(/\b\d+\b/g) || []).join(' ');
    const getType = (str) => {
        const m = str.match(/\b(SMK|SMA|MA|SMP|MTS|SD|MI|UNIVERSITAS|INSTITUT|POLITEKNIK|STMIK)\b/);
        return m ? m[1] : '';
    };

    const inputNum = getNumbers(normInput);
    const inputType = getType(normInput);

    const stripStatus = (s) => s.replace(/\b(NEGERI|SWASTA)\b/g, '').replace(/\s+/g, ' ').trim();
    const inputCore = stripStatus(normInput);

    let bestMatch = null;
    let highestScore = 0;

    for (const school of schoolList) {
        const targetName = school.nama || school.label || school.name || '';
        const normTarget = normalizeSchoolName(targetName);
        if (!normTarget) continue;

        // 1. Direct match after acronym normalization (e.g. "SMKN 1 CIREBON" -> "SMK NEGERI 1 CIREBON")
        if (normInput === normTarget) {
            return school;
        }

        const targetNum = getNumbers(normTarget);
        const targetType = getType(normTarget);

        // Strict constraints: Type (SMA vs SMK) & Numbers (1 vs 2) MUST match if present
        if (inputType && targetType && inputType !== targetType) {
            continue;
        }
        if (inputNum !== targetNum) {
            continue;
        }

        // 2. Core match (e.g., "SMK 1 CIREBON" vs "SMK NEGERI 1 CIREBON")
        const targetCore = stripStatus(normTarget);
        if (inputCore && inputCore === targetCore) {
            return school;
        }

        // 3. Token similarity
        const inputTokens = new Set(normInput.split(' ').filter(t => t.length > 1));
        const targetTokens = new Set(normTarget.split(' ').filter(t => t.length > 1));

        let intersection = 0;
        for (const token of inputTokens) {
            if (targetTokens.has(token)) intersection++;
        }

        const totalTokens = Math.max(inputTokens.size, targetTokens.size);
        const score = totalTokens > 0 ? intersection / totalTokens : 0;

        if (score >= 0.75 && score > highestScore) {
            highestScore = score;
            bestMatch = school;
        }
    }

    return bestMatch;
}

export async function checkSchoolDuplicate({ manualName, schoolList, onUseExisting, onProceedManual }) {
    if (!manualName || !manualName.trim()) return true;

    const matched = findSimilarSchool(manualName, schoolList);
    if (!matched) {
        return true;
    }

    const matchedName = matched.nama || matched.label || matched.name || '';
    const matchedWilayah = matched.wilayah || matched.sub || '';

    const escapeHtml = (unsafe) => {
        return String(unsafe || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const result = await Swal.fire({
        title: 'Sekolah Serupa Ditemukan',
        html: `
            <div class="text-left text-xs sm:text-sm text-slate-600 space-y-3">
                <p>Anda memasukkan: <strong class="text-slate-900 font-bold font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">${escapeHtml(manualName)}</strong></p>
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl">
                    <p class="text-[11px] font-bold text-blue-700 uppercase tracking-wider mb-1">Sekolah Terdaftar di Database:</p>
                    <p class="text-sm font-bold text-blue-950 font-mono">${escapeHtml(matchedName)}</p>
                    ${matchedWilayah ? `<p class="text-[11px] text-blue-600 mt-1 font-medium">Wilayah: ${escapeHtml(matchedWilayah)}</p>` : ''}
                </div>
                <p class="text-slate-700 text-xs">Apakah sekolah yang Anda masukkan sama dengan sekolah terdaftar di atas?</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb', // Blue-600
        cancelButtonColor: '#64748b',  // Slate-500
        confirmButtonText: '✓ Gunakan Sekolah yang Sudah Ada',
        cancelButtonText: 'Bukan Sekolah yang Sama',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-2xl shadow-2xl p-6',
            confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2.5',
            cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2.5'
        }
    });

    if (result.isConfirmed) {
        if (typeof onUseExisting === 'function') {
            onUseExisting(matched);
        }
        return 'existing';
    } else if (result.dismiss === Swal.DismissReason.cancel) {
        if (typeof onProceedManual === 'function') {
            onProceedManual();
        }
        return 'manual';
    } else {
        // User closed modal (e.g. clicked outside or Esc)
        return false;
    }
}

window.normalizeSchoolName = normalizeSchoolName;
window.findSimilarSchool = findSimilarSchool;
window.checkSchoolDuplicate = checkSchoolDuplicate;


// ──────────────────────────────────────────────────────────────────
// Web Audio API Notification Chime Synthesizer
// ──────────────────────────────────────────────────────────────────
let audioCtx = null;
function getAudioContext() {
    try {
        if (!audioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                audioCtx = new AudioContextClass();
            }
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume().catch(() => {});
        }
        return audioCtx;
    } catch (e) {
        return null;
    }
}

// User-gesture unlock for AudioContext on any interaction
const unlockAudio = () => {
    try {
        const ctx = getAudioContext();
        if (ctx && ctx.state === 'suspended') {
            ctx.resume().catch(() => {});
        }
    } catch (e) {}
};

['click', 'touchstart', 'keydown', 'mousedown', 'pointerdown'].forEach(evt => {
    window.addEventListener(evt, unlockAudio, { passive: true });
});

window.playNotificationChime = function(type = 'info') {
    // Check if user has muted notifications
    if (localStorage.getItem('crm_sound_enabled') === 'false') {
        return;
    }

    try {
        const ctx = getAudioContext();
        if (!ctx) return;

        const playNotes = () => {
            const now = ctx.currentTime;
            let notes = [587.33, 880.00]; // D5 -> A5 (gentle bell chime)
            if (type === 'success') {
                notes = [523.25, 659.25, 783.99, 1046.50]; // C5 -> E5 -> G5 -> C6
            } else if (type === 'warning') {
                notes = [659.25, 587.33, 659.25]; // E5 -> D5 -> E5
            } else if (type === 'danger' || type === 'error') {
                notes = [784.00, 587.33]; // G5 -> D5
            }

            const noteInterval = type === 'success' ? 0.09 : 0.12;

            notes.forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                // Triangle wave creates a resonant bell tone that cuts through laptop speakers
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + idx * noteInterval);

                // Envelope
                gain.gain.setValueAtTime(0.001, now + idx * noteInterval);
                gain.gain.exponentialRampToValueAtTime(0.45, now + idx * noteInterval + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + idx * noteInterval + 0.5);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(now + idx * noteInterval);
                osc.stop(now + idx * noteInterval + 0.55);
            });
        };

        if (ctx.state === 'suspended') {
            ctx.resume().then(() => playNotes()).catch(() => {});
        } else {
            playNotes();
        }
    } catch (e) {
        console.warn('Audio chime playback failed:', e);
    }
};

// ──────────────────────────────────────────────────────────────────
// Global CRM Store & State Management
// ──────────────────────────────────────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.store('crm', {
        activeRole: window.__INITIAL_ROLE__ || 'sales', // sales, cs, spv, hm
        activeState: 'normal', // normal, loading, empty, error
        toasts: [],
        soundEnabled: localStorage.getItem('crm_sound_enabled') !== 'false',

        init() {
            this.initPolling();
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            localStorage.setItem('crm_sound_enabled', this.soundEnabled ? 'true' : 'false');
            if (this.soundEnabled) {
                window.playNotificationChime('info');
                this.showToast('Suara notifikasi diaktifkan 🔔', 'info');
            } else {
                this.showToast('Suara notifikasi dimatikan 🔇', 'info');
            }
        },

        testSound() {
            window.playNotificationChime('success');
            this.showToast('Tes bunyi notifikasi 🔔', 'success');
        },

        showToast(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });

            // Automatically chime for toasts
            if (typeof window.playNotificationChime === 'function') {
                window.playNotificationChime(type);
            }

            setTimeout(() => {
                this.removeToast(id);
            }, 4500);
        },

        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },

        notifications: window.__NOTIFICATIONS__ || [
            {
                id: 1,
                title: 'Follow Up Terjadwal: SMK N 1 Cirebon',
                message: 'Jadwal audiensi dengan Kepala Sekolah & BK pukul 10:00 WIB hari ini.',
                time: '10 menit lalu',
                type: 'warning',
                read: false,
                link: '/follow-up'
            },
            {
                id: 2,
                title: 'Takeover CS Masuk: SMA N 2 Majalengka',
                message: 'Dina Marlina melimpahkan prospek Beli Formulir (35 Siswa).',
                time: '25 menit lalu',
                type: 'info',
                read: false,
                link: '/prospek'
            },
            {
                id: 3,
                title: 'Pembayaran Termin 1 Tervalidasi',
                message: 'Faris Akbar (S1 Bisnis Digital) telah menyelesaikan pembayaran termin 1.',
                time: '1 jam lalu',
                type: 'success',
                read: false,
                link: '/prospek/4'
            },
            {
                id: 4,
                title: 'Sesi WhatsApp Gateway Diperbarui',
                message: 'Koneksi WhatsApp Node-01 tervalidasi dan dalam kondisi optimal.',
                time: '3 jam lalu',
                type: 'info',
                read: true,
                link: '/admin/settings'
            }
        ],

        unreadCountVal: null,

        get unreadCount() {
            if (this.unreadCountVal !== null) {
                return this.unreadCountVal;
            }
            return this.notifications.filter(n => !n.read).length;
        },

        set unreadCount(val) {
            this.unreadCountVal = val;
        },

        markAsRead(id) {
            const notif = this.notifications.find(n => n.id === id);
            if (notif && !notif.read) {
                notif.read = true;
                if (this.unreadCountVal !== null && this.unreadCountVal > 0) {
                    this.unreadCountVal--;
                }
                this.showToast('Notifikasi ditandai dibaca');
                
                // Send AJAX to backend
                fetch(`/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    }
                });
            }
        },

        markAllAsRead() {
            this.notifications.forEach(n => n.read = true);
            this.unreadCountVal = 0;
            this.showToast('Semua notifikasi ditandai telah dibaca');
            
            // Send AJAX to backend
            fetch('/notifications/read-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json'
                }
            });
        },

        deleteNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
            this.showToast('Notifikasi disembunyikan');
        },

        setRole(role) {
            this.activeRole = role;
            if (role === 'sales') window.location.href = '/dashboard/sales';
            else if (role === 'cs') window.location.href = '/dashboard/cs';
            else if (role === 'spv') window.location.href = '/dashboard/spv';
            else if (role === 'hm') window.location.href = '/dashboard/hm';
        },

        setState(state) {
            this.activeState = state;
        },

        initPolling() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) return;

            const checkNewNotifications = async () => {
                try {
                    const res = await fetch('/notifications/latest', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    
                    if (Array.isArray(data.notifications)) {
                        const existingIds = new Set(this.notifications.map(n => String(n.id)));
                        const newItems = data.notifications.filter(n => !existingIds.has(String(n.id)) && !n.read);

                        if (newItems.length > 0) {
                            // Prepend new incoming notifications
                            this.notifications = [...newItems, ...this.notifications];
                            this.unreadCount = data.unreadCount;

                            // Play chime for the first new notification!
                            const newest = newItems[0];
                            window.playNotificationChime(newest.type || 'info');

                            // Show toast popup
                            this.showToast(`${newest.title}: ${newest.message}`, newest.type || 'info');
                        } else if (typeof data.unreadCount === 'number') {
                            this.unreadCount = data.unreadCount;
                        }
                    }
                } catch (e) {
                    // Silently fail on network disruption
                }
            };

            // Fast polling every 3.5 seconds for instant cross-role sync
            setInterval(checkNewNotifications, 3500);

            // Immediate check when window regains focus or tab becomes active
            window.addEventListener('focus', checkNewNotifications);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    checkNewNotifications();
                }
            });
        }
    });
});

Alpine.start();
