import { postJson, requireLogin, siteUrl, toast } from './http';

export function registerListing(Alpine) {
    // List pages: the filter panel is a bottom sheet below 1100px.
    Alpine.data('listingPage', () => ({
        filtersOpen: false,

        openFilters() {
            this.filtersOpen = true;
            document.documentElement.style.overflow = 'hidden';
        },

        closeFilters() {
            this.filtersOpen = false;
            document.documentElement.style.overflow = '';
        },
    }));

    // Detail page gallery: thumbnails open a full-screen lightbox with swipe (scroll snap) and arrows.
    Alpine.data('gallery', (count) => ({
        open: false,
        index: 0,
        count,

        show(index) {
            this.index = index;
            this.open = true;
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => this.scrollTo(index, 'instant'));
        },

        close() {
            this.open = false;
            document.documentElement.style.overflow = '';
        },

        next() {
            this.scrollTo((this.index + 1) % this.count);
        },

        previous() {
            this.scrollTo((this.index - 1 + this.count) % this.count);
        },

        scrollTo(index, behavior = 'smooth') {
            const track = this.$refs.track;

            if (track) {
                track.scrollTo({ left: track.clientWidth * index, behavior });
            }

            this.index = index;
        },

        onScroll() {
            const track = this.$refs.track;

            if (track && track.clientWidth > 0) {
                this.index = Math.round(track.scrollLeft / track.clientWidth);
            }
        },
    }));

    // "Show phone": the number comes from the server (logged, rate-limited), never in the page source.
    Alpine.data('phoneReveal', (type, id) => ({
        phone: null,
        tel: null,
        whatsapp: null,
        viber: null,
        busy: false,

        async reveal() {
            if (this.busy || this.phone) {
                return;
            }

            this.busy = true;

            try {
                const { ok, status, data } = await postJson(`${siteUrl('phone')}/${type}/${id}`);

                if (status === 429) {
                    toast(data.message, 'error');

                    return;
                }

                if (!ok) {
                    throw new Error(String(status));
                }

                Object.assign(this, { phone: data.display, tel: data.tel, whatsapp: data.whatsapp, viber: data.viber });
            } catch {
                toast(document.documentElement.dataset.errorMessage, 'error');
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('shareButton', (title, copiedMessage) => ({
        async share() {
            const url = window.location.href.split('#')[0];

            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                } catch {
                    // Cancelled by the user.
                }

                return;
            }

            try {
                await navigator.clipboard.writeText(url);
                toast(copiedMessage, 'success');
            } catch {
                window.prompt('', url);
            }
        },
    }));

    // Buttons that need a signed-in user (message, report, apply): guests get the sign-in dialog.
    Alpine.data('authAction', (signedIn, dialog) => ({
        run() {
            if (!signedIn) {
                requireLogin();

                return;
            }

            window.dispatchEvent(new CustomEvent('open-dialog', { detail: dialog }));
        },
    }));

    // Header search: instant suggestions grouped by module.
    Alpine.data('siteSearch', () => ({
        query: '',
        groups: [],
        loading: false,
        active: -1,
        controller: null,
        timer: null,

        get flat() {
            return this.groups.flatMap((group) => group.items);
        },

        input() {
            clearTimeout(this.timer);
            this.active = -1;

            if (this.query.trim().length < 2) {
                this.groups = [];

                return;
            }

            this.timer = setTimeout(() => this.fetch(), 220);
        },

        async fetch() {
            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;

            try {
                const url = new URL(siteUrl('suggest'), window.location.origin);
                url.searchParams.set('q', this.query.trim());

                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: this.controller.signal });
                const data = await response.json();

                this.groups = data.groups ?? [];
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.groups = [];
                }
            } finally {
                this.loading = false;
            }
        },

        move(step) {
            const total = this.flat.length;

            if (total === 0) {
                return;
            }

            this.active = (this.active + step + total) % total;
        },

        submit(event) {
            const item = this.flat[this.active];

            if (item) {
                event.preventDefault();
                window.location.href = item.url;
            }
        },
    }));
}
