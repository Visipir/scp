import '@fontsource/plus-jakarta-sans/400.css';
import '@fontsource/plus-jakarta-sans/500.css';
import '@fontsource/plus-jakarta-sans/600.css';
import '@fontsource/plus-jakarta-sans/700.css';
import '@fontsource/plus-jakarta-sans/800.css';
import '@fontsource/jetbrains-mono/400.css';
import '@fontsource/jetbrains-mono/500.css';
import '@fontsource/jetbrains-mono/600.css';
import '@fontsource/jetbrains-mono/700.css';

import Alpine from 'alpinejs';

// Feature-detect native <input type="checkbox" switch>; enhance inline when missing (avoids broken runtime CSS fetch)
function initSwitchControls() {
    if ('switch' in HTMLInputElement.prototype) return;
    document.querySelectorAll('input[type="checkbox"][switch]').forEach((el) => {
        el.classList.add('scp-switch-fallback');
        if (!el.hasAttribute('role')) {
            el.setAttribute('role', 'switch');
        }
    });
}

window.Alpine = Alpine;

function readSavedTheme() {
    try {
        return localStorage.getItem('theme') || 'system';
    } catch {
        return 'system';
    }
}

function writeSavedTheme(mode) {
    try {
        if (mode === 'system') {
            localStorage.removeItem('theme');
        } else {
            localStorage.setItem('theme', mode);
        }
    } catch {
        // Ignore storage errors when site data is blocked
    }
}

function syncColorSchemeMeta(mode, isDark = document.documentElement.classList.contains('dark')) {
    const meta = document.querySelector('meta[name="color-scheme"]');
    if (meta) {
        meta.content = mode === 'dark' ? 'dark' : (mode === 'light' ? 'light' : 'light dark');
    }
    const themeMeta = document.querySelector('meta[name="theme-color"]');
    if (themeMeta) {
        const isGuest = document.body?.classList.contains('auth-body');
        themeMeta.content = isDark ? '#0F1014' : (isGuest ? '#F4F4F6' : '#F0F2F5');
    }
}

// Theme Store (System / Light / Dark) synced to <meta name="color-scheme"> and Tailwind .dark class
Alpine.store('theme', {
    mode: readSavedTheme(),
    isDark: document.documentElement.classList.contains('dark'),

    init() {
        this.sync();
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        media.addEventListener('change', (e) => {
            if (this.mode === 'system') {
                this.apply(e.matches, 'system');
            }
        });
        window.addEventListener('storage', (e) => {
            if (e.key === 'theme') {
                this.mode = e.newValue || 'system';
                this.sync();
            }
        });
    },

    set(newMode) {
        this.mode = newMode;
        writeSavedTheme(newMode);
        this.sync();
        window.dispatchEvent(new CustomEvent('theme-changed', {
            detail: { mode: newMode, isDark: this.isDark }
        }));
    },

    sync() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dark = this.mode === 'dark' || (this.mode === 'system' && prefersDark);
        this.apply(dark, this.mode);
    },

    apply(dark, mode = this.mode) {
        this.isDark = dark;
        if (dark) {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        }
        syncColorSchemeMeta(mode, dark);
    }
});

// Shared accessible confirmation modal store replacing native window.confirm()
Alpine.store('confirmModal', {
    open: false,
    title: 'Konfirmasi Tindakan',
    message: '',
    confirmLabel: 'Ya, Lanjutkan',
    variant: 'danger',
    pendingForm: null,
    pendingSubmitter: null,
    returnFocusTo: null,

    ask(form, message, title = 'Konfirmasi Tindakan', variant = 'danger', confirmLabel = 'Ya, Lanjutkan', submitter = null) {
        this.returnFocusTo = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        this.pendingForm = form;
        this.pendingSubmitter = submitter;
        this.message = message;
        this.title = title;
        this.variant = variant;
        this.confirmLabel = confirmLabel;
        this.open = true;
    },

    confirm() {
        const form = this.pendingForm;
        const submitter = this.pendingSubmitter;
        this.open = false;
        this.pendingForm = null;
        this.pendingSubmitter = null;
        if (form) {
            form.dataset.confirmed = 'true';
            if (typeof form.requestSubmit === 'function') {
                if (submitter && submitter.isConnected) {
                    form.requestSubmit(submitter);
                } else {
                    form.requestSubmit();
                }
            } else {
                form.submit();
            }
        }
    },

    cancel() {
        this.open = false;
        this.pendingForm = null;
        this.pendingSubmitter = null;
        const target = this.returnFocusTo;
        this.returnFocusTo = null;
        if (target && typeof target.focus === 'function') {
            requestAnimationFrame(() => target.focus());
        }
    }
});

// Centralized polite status announcer for client-side updates
Alpine.store('announcer', {
    say(message) {
        const region = document.getElementById('app-announcer');
        if (!region || !message) return;
        region.textContent = '';
        requestAnimationFrame(() => {
            region.textContent = message;
        });
    }
});

// Intercept any form (or trigger button) with data-confirm so destructive actions use the native confirmation dialog
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
    const source = submitter?.dataset?.confirm ? submitter : (form.dataset.confirm ? form : null);
    if (!source) return;
    if (form.dataset.confirmed === 'true') {
        delete form.dataset.confirmed;
        return;
    }
    event.preventDefault();
    event.stopImmediatePropagation();
    Alpine.store('confirmModal').ask(
        form,
        source.dataset.confirm,
        source.dataset.confirmTitle || form.dataset.confirmTitle || 'Konfirmasi Tindakan',
        source.dataset.confirmVariant || form.dataset.confirmVariant || 'danger',
        source.dataset.confirmAction || form.dataset.confirmAction || 'Ya, Lanjutkan',
        submitter
    );
}, true);

// Light-dismiss polyfill fallback for <dialog closedby="any"> on Safari
if (!('closedBy' in HTMLDialogElement.prototype)) {
    const outsideDialog = (e, dialog) => {
        const r = dialog.getBoundingClientRect();
        return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
    };
    let pressedDialog = null;
    document.addEventListener('pointerdown', (e) => {
        const dialog = e.target instanceof HTMLDialogElement ? e.target : null;
        pressedDialog = (dialog && dialog.open && dialog.getAttribute('closedby') === 'any' && outsideDialog(e, dialog))
            ? dialog
            : null;
    });
    document.addEventListener('pointerup', (e) => {
        if (pressedDialog && e.target === pressedDialog && outsideDialog(e, pressedDialog)) {
            pressedDialog.close();
        }
        pressedDialog = null;
    });
}

// Login background preview handler (CSP-safe external module)
function initLoginBackgroundPreview() {
    const fileInput = document.getElementById('background_input');
    const previewContainer = document.getElementById('preview_container');
    const previewSlot = document.getElementById('preview_img_slot');
    const previewSpecs = document.getElementById('preview_specs');
    if (!fileInput || !previewContainer || !previewSlot || !previewSpecs) return;

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (!file) {
            previewContainer.classList.add('hidden');
            previewSlot.replaceChildren();
            return;
        }
        const reader = new FileReader();
        reader.onload = (event) => {
            const tempImg = new Image();
            tempImg.onload = () => {
                const img = document.createElement('img');
                img.src = event.target.result;
                img.alt = 'Pratinjau Gambar Latar Login';
                img.width = tempImg.naturalWidth || 1280;
                img.height = tempImg.naturalHeight || 720;
                img.className = 'w-full h-full object-cover';
                previewSlot.replaceChildren(img);
                previewSpecs.textContent = `${tempImg.naturalWidth} \u00D7 ${tempImg.naturalHeight} px | ${(file.size / (1024 * 1024)).toFixed(2)} MB`;
                previewContainer.classList.remove('hidden');
            };
            tempImg.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
}

// Desktop-only autofocus helper so virtual keyboards are not forced open on mobile
function initDesktopAutofocus() {
    if (!window.matchMedia('(pointer: fine) and (min-width: 1024px)').matches) return;
    const target = document.querySelector('[data-autofocus-desktop]');
    if (target instanceof HTMLElement && !document.querySelector('dialog[open]')) {
        target.focus({ preventScroll: true });
    }
}

// Sync aria-invalid, aria-describedby error links, and polite live announcements with :user-invalid
function initAccessibleErrorAnnouncement() {
    const forms = document.querySelectorAll('form[data-accessible-validation]');
    if (!forms.length) return;

    const supportsUserInvalid = typeof CSS !== 'undefined' && CSS.supports?.('selector(:user-invalid)');
    const dirtyState = new WeakMap();
    const flagged = new WeakSet();
    const announced = new WeakSet();
    const announcer = document.getElementById('app-announcer');
    let announceTimer;
    let announcedField;

    const spokenText = (node) => {
        const copy = node.cloneNode(true);
        copy.querySelectorAll('[aria-hidden="true"]').forEach((n) => n.remove());
        return (copy.textContent || '').replace(/\s+/g, ' ').trim();
    };

    const linkError = (el, shown) => {
        const errorId = el.dataset.error;
        if (!errorId) return;
        const ids = (el.getAttribute('aria-describedby') || '').split(/\s+/).filter((id) => id && id !== errorId);
        if (shown) ids.push(errorId);
        if (ids.length) el.setAttribute('aria-describedby', ids.join(' '));
        else el.removeAttribute('aria-describedby');
    };

    const announceError = (el) => {
        if (!announcer) return;
        const errorNode = el.dataset.error ? document.getElementById(el.dataset.error) : null;
        const message = (errorNode ? spokenText(errorNode) : '') || el.validationMessage;
        if (!message) return;
        const label = el.labels?.[0] ? `${spokenText(el.labels[0])}: ` : '';
        clearTimeout(announceTimer);
        announcer.textContent = '';
        announcedField = el;
        announceTimer = setTimeout(() => {
            announcer.textContent = label + message;
        }, 500);
    };

    const isFieldUserInvalid = (el) => {
        if (supportsUserInvalid) {
            return el.matches(':user-invalid');
        }
        const state = dirtyState.get(el);
        return Boolean(state?.hasBlurred && state?.hasInteracted && !el.validity.valid);
    };

    const syncAria = (el, announce = false) => {
        if (!(el instanceof HTMLElement) || !el.matches('input, select, textarea')) return;
        if (el.disabled) return;
        const invalid = isFieldUserInvalid(el);
        if (!supportsUserInvalid && dirtyState.get(el)?.hasBlurred) {
            el.classList.toggle('user-invalid-fallback', invalid);
        }
        if (invalid) {
            if (announce && !announced.has(el)) {
                announceError(el);
                announced.add(el);
            }
            el.setAttribute('aria-invalid', 'true');
            linkError(el, true);
            flagged.add(el);
        } else if (flagged.has(el) || el.getAttribute('aria-invalid') !== 'true') {
            el.setAttribute('aria-invalid', 'false');
            linkError(el, false);
            flagged.delete(el);
            announced.delete(el);
            if (announcedField === el && announcer) {
                clearTimeout(announceTimer);
                announcer.textContent = '';
            }
        }
    };

    forms.forEach((form) => {
        form.querySelectorAll('input, select, textarea').forEach((el) => {
            if (el.getAttribute('aria-invalid') !== 'true') {
                el.setAttribute('aria-invalid', 'false');
            }
        });

        form.addEventListener('blur', (e) => {
            const el = e.target;
            if (el instanceof HTMLElement && el.matches('input, select, textarea')) {
                const state = dirtyState.get(el) || { hasInteracted: false, hasBlurred: false };
                state.hasBlurred = true;
                dirtyState.set(el, state);
                syncAria(el, true);
            }
        }, true);

        form.addEventListener('input', (e) => {
            const el = e.target;
            if (el instanceof HTMLElement && el.matches('input, select, textarea')) {
                const state = dirtyState.get(el) || { hasInteracted: false, hasBlurred: false };
                state.hasInteracted = true;
                dirtyState.set(el, state);
                if (el.hasAttribute('aria-invalid')) syncAria(el);
            }
        });

        form.addEventListener('change', (e) => {
            const el = e.target;
            if (el instanceof HTMLElement && el.matches('input, select, textarea')) {
                const state = dirtyState.get(el) || { hasInteracted: false, hasBlurred: false };
                state.hasInteracted = true;
                state.hasBlurred = true;
                dirtyState.set(el, state);
                syncAria(el, true);
            }
        }, true);

        form.addEventListener('invalid', (e) => {
            const el = e.target;
            if (el instanceof HTMLElement && el.matches('input, select, textarea')) {
                dirtyState.set(el, { hasInteracted: true, hasBlurred: true });
                announced.add(el);
                syncAria(el);
            }
        }, true);

        form.addEventListener('reset', () => {
            setTimeout(() => {
                [...form.elements].forEach((el) => {
                    dirtyState.delete(el);
                    el.classList?.remove('user-invalid-fallback');
                    syncAria(el);
                });
            });
        }, true);
    });
}

// Warn before leaving a page with unsaved changes on mutating forms
function initUnsavedChangesGuard() {
    let hasUnsavedChanges = false;
    const isTrackedForm = (form) => {
        if (!(form instanceof HTMLFormElement)) return false;
        if (form.hasAttribute('data-no-unsaved-warning')) return false;
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET') return false;
        if (form.action && /\/logout\/?$/i.test(form.action)) return false;
        return true;
    };

    document.addEventListener('input', (e) => {
        const form = e.target instanceof HTMLElement ? e.target.closest('form') : null;
        if (isTrackedForm(form)) hasUnsavedChanges = true;
    });
    document.addEventListener('change', (e) => {
        const form = e.target instanceof HTMLElement ? e.target.closest('form') : null;
        if (isTrackedForm(form)) hasUnsavedChanges = true;
    });
    document.addEventListener('submit', (e) => {
        if (!e.defaultPrevented) hasUnsavedChanges = false;
    });
    window.addEventListener('beforeunload', (e) => {
        if (!hasUnsavedChanges) return;
        e.preventDefault();
        e.returnValue = '';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initSwitchControls();
        initLoginBackgroundPreview();
        initDesktopAutofocus();
        initAccessibleErrorAnnouncement();
        initUnsavedChangesGuard();
    });
} else {
    initSwitchControls();
    initLoginBackgroundPreview();
    initDesktopAutofocus();
    initAccessibleErrorAnnouncement();
    initUnsavedChangesGuard();
}

Alpine.start();
