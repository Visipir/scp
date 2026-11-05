

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Theme Store (System / Light / Dark)
Alpine.store('theme', {
    mode: localStorage.getItem('theme') || 'system',
    isDark: document.documentElement.classList.contains('dark'),

    init() {
        this.sync();
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        media.addEventListener('change', (e) => {
            if (this.mode === 'system') {
                this.apply(e.matches);
            }
        });
    },

    set(newMode) {
        this.mode = newMode;
        localStorage.setItem('theme', newMode);
        this.sync();
        window.dispatchEvent(new CustomEvent('theme-changed', { 
            detail: { mode: newMode, isDark: this.isDark } 
        }));
    },

    sync() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dark = this.mode === 'dark' || (this.mode === 'system' && prefersDark);
        this.apply(dark);
    },

    apply(dark) {
        this.isDark = dark;
        if (dark) {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        }
    }
});

Alpine.start();
