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
