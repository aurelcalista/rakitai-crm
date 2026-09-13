import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
