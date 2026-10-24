const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function registerComponents(Alpine) {
    // The menu icons and the drawer follow aria-expanded / data-open in CSS, so the header needs only
    // two bind objects (see heroSearch for why inline expressions are avoided).
    Alpine.data('siteHeader', () => ({
        scrolled: false,
        open: false,

        root: {
            ':data-scrolled'() {
                return this.scrolled;
            },
            ':data-open'() {
                return this.open;
            },
            'x-on:keydown.escape.window'() {
                this.close();
            },
            'x-on:click'(event) {
                if (event.target.closest('[data-drawer] a')) {
                    this.close();
                }
            },
        },

        menuButton: {
            'x-on:click'() {
                this.toggle();
            },
            ':aria-expanded'() {
                return String(this.open);
            },
        },

        init() {
            // A 40px sentinel at the top of the document: once it leaves the viewport the header compacts.
            // Observing it avoids reading scrollY, which forces a full layout during startup.
            const sentinel = document.createElement('div');
            sentinel.setAttribute('aria-hidden', 'true');
            sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:40px;pointer-events:none;';
            document.body.prepend(sentinel);

            new IntersectionObserver(([entry]) => {
                this.scrolled = !entry.isIntersecting;
            }).observe(sentinel);

            window.matchMedia('(min-width: 77.5rem)').addEventListener('change', (event) => {
                if (event.matches) {
                    this.open = false;
                }
            });
        },

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        },
    }));

    Alpine.data('heroSearch', (initial) => ({
        tab: initial,
        shown: initial,
        fading: false,
        deal: 'buy',
        timer: null,

        // Bind objects instead of per-tab inline expressions: Alpine compiles every distinct inline
        // expression with new Function(), which is the costliest part of starting it on mobile.
        tabButton: {
            'x-on:click'() {
                this.select(this.$el.dataset.tab);
            },
            'x-on:keydown'(event) {
                this.step(event);
            },
            ':aria-selected'() {
                return String(this.tab === this.$el.dataset.tab);
            },
            ':tabindex'() {
                return this.tab === this.$el.dataset.tab ? 0 : -1;
            },
        },

        tabPanel: {
            'x-show'() {
                return this.shown === this.$el.dataset.tab;
            },
        },

        select(id) {
            if (id === this.tab) {
                return;
            }

            this.tab = id;
            clearTimeout(this.timer);

            if (prefersReducedMotion()) {
                this.shown = id;

                return;
            }

            this.fading = true;
            this.timer = setTimeout(() => {
                this.shown = id;
                this.fading = false;
            }, 170);
        },

        step(event) {
            const direction = { ArrowRight: 1, ArrowLeft: -1 }[event.key];

            if (!direction) {
                return;
            }

            event.preventDefault();
            const tabs = Array.from(this.$root.querySelectorAll('[role="tab"]'));
            const next = tabs[(tabs.indexOf(event.target) + direction + tabs.length) % tabs.length];

            next.focus();
            next.click();
        },
    }));

    Alpine.data('carousel', () => ({
        arrow: {
            'x-on:click'() {
                this.scroll(Number(this.$el.dataset.direction));
            },
        },

        scroll(direction) {
            const track = this.$root.querySelector('[data-carousel-track]');
            const card = track.firstElementChild;
            const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
            const step = card ? card.getBoundingClientRect().width + gap : track.clientWidth * 0.8;

            track.scrollBy({ left: direction * step, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
        },
    }));

    // Items inside the scope opt in with data-segment="<value>"; they are hidden here rather than with
    // x-show so that cards can stay free of Alpine directives (and be skipped with x-ignore).
    Alpine.data('segmented', (initial) => ({
        value: initial,

        option: {
            'x-on:click'() {
                this.value = this.$el.dataset.value;
                this.filter();
            },
            ':aria-pressed'() {
                return String(this.value === this.$el.dataset.value);
            },
        },

        filter() {
            this.$root.querySelectorAll('[data-segment]').forEach((item) => {
                item.hidden = this.value !== 'all' && item.dataset.segment !== this.value;
            });
        },
    }));

    Alpine.data('dialog', (name) => ({
        init() {
            window.addEventListener('open-dialog', (event) => {
                if (event.detail === name && !this.$el.open) {
                    this.$el.showModal();
                }
            });

            window.addEventListener('close-dialog', (event) => {
                if (!event.detail || event.detail === name) {
                    this.$el.close();
                }
            });
        },

        closeOnBackdrop(event) {
            if (event.target === this.$el) {
                this.$el.close();
            }
        },
    }));

    Alpine.data('dropzone', () => ({
        dragging: false,
        files: [],

        sync() {
            this.files = Array.from(this.$refs.input.files).map((file) => ({
                name: file.name,
                size: file.size < 1048576 ? `${Math.round(file.size / 1024)} KB` : `${(file.size / 1048576).toFixed(1)} MB`,
            }));
        },

        drop(event) {
            this.dragging = false;
            this.$refs.input.files = event.dataTransfer.files;
            this.sync();
        },
    }));

    Alpine.store('toasts', {
        items: [],
        nextId: 1,

        push({ message, type = 'info', timeout = 4500 }) {
            const id = this.nextId++;
            const mount = document.querySelector('template[data-toast-stack]');

            if (mount) {
                mount.replaceWith(mount.content.cloneNode(true));
            }

            this.items.push({ id, message, type });
            setTimeout(() => this.dismiss(id), timeout);
        },

        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    window.addEventListener('toast', (event) => Alpine.store('toasts').push(event.detail));
}
