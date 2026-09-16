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

// Global CRM Store & State Management
document.addEventListener('alpine:init', () => {
    Alpine.store('crm', {
        activeRole: window.__INITIAL_ROLE__ || 'sales', // sales, cs, spv, hm
        activeState: 'normal', // normal, loading, empty, error
        toasts: [],

        showToast(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.removeToast(id);
            }, 4000);
        },

        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },

        notifications: [
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

        get unreadCount() {
            return this.notifications.filter(n => !n.read).length;
        },

        markAsRead(id) {
            const notif = this.notifications.find(n => n.id === id);
            if (notif && !notif.read) {
                notif.read = true;
                this.showToast('Notifikasi ditandai dibaca');
            }
        },

        markAllAsRead() {
            this.notifications.forEach(n => n.read = true);
            this.showToast('Semua notifikasi ditandai telah dibaca');
        },

        deleteNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
            this.showToast('Notifikasi dihapus');
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
        }
    });
});

Alpine.start();
