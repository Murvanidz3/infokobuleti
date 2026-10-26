/**
 * Installable app: registers the service worker (offline page, push) when the layout enables the
 * PWA, and offers "add to home screen" once on phones (Android prompt, iOS instructions).
 */
export function registerPwa(Alpine) {
    const enabled = document.querySelector('meta[name="pwa"]')?.getAttribute('content') === '1';

    if (enabled && 'serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
        });
    }

    Alpine.data('installPrompt', () => ({
        open: false,
        ios: false,
        deferred: null,

        init() {
            if (!enabled || window.matchMedia('(display-mode: standalone)').matches || navigator.standalone || this.dismissed()) {
                return;
            }

            const phone = window.matchMedia('(max-width: 720px)').matches;
            this.ios = /iphone|ipad|ipod/i.test(navigator.userAgent) && !/crios|fxios/i.test(navigator.userAgent);

            window.addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();
                this.deferred = event;
                this.show(phone);
            });

            if (this.ios && phone) {
                setTimeout(() => this.show(true), 4000);
            }
        },

        // Never on top of the cookie banner: wait until the visitor has made the cookie choice.
        show(open) {
            if (document.cookie.includes('ik_consent=')) {
                this.open = open;
            } else {
                window.addEventListener('cookie-chosen', () => (this.open = open), { once: true });
            }
        },

        async install() {
            if (this.deferred) {
                this.deferred.prompt();
                await this.deferred.userChoice.catch(() => null);
                this.deferred = null;
            }

            this.dismiss();
        },

        dismiss() {
            this.open = false;

            try {
                localStorage.setItem('install-prompt-dismissed', String(Date.now()));
            } catch {
                // Storage blocked: the prompt just shows again next time.
            }
        },

        dismissed() {
            try {
                const at = Number(localStorage.getItem('install-prompt-dismissed') || 0);

                return Date.now() - at < 30 * 24 * 3600 * 1000;
            } catch {
                return false;
            }
        },
    }));
}
