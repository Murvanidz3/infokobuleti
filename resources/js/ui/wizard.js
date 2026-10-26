import { loadLeaflet } from './map';

const KOBULETI = [41.8214, 41.7792];
const MAX_EDGE = 2048;

/**
 * Downscales a photo in the browser before upload (faster on mobile data) and re-encodes it,
 * which also drops EXIF metadata such as the GPS position. Falls back to the original file when
 * the browser cannot decode it (the server sanitizes every upload again anyway).
 */
async function resize(file) {
    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));

        if (!blob) {
            return file;
        }

        return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
    } catch {
        return file;
    }
}

export function registerWizard(Alpine) {
    // Photo step: pick or drop files, resize them, upload one by one into the Livewire "upload" property.
    Alpine.data('photoUploader', (max = 20) => ({
        dragging: false,
        queue: [],
        busy: false,
        progress: 0,
        dragFrom: null,

        pick(event) {
            this.add(event.target.files);
            event.target.value = '';
        },

        drop(event) {
            this.dragging = false;
            this.add(event.dataTransfer.files);
        },

        add(fileList) {
            const free = max - this.$wire.form.photos.length - this.queue.length;
            const files = Array.from(fileList).filter((file) => file.type.startsWith('image/')).slice(0, Math.max(0, free));
            this.queue.push(...files);
            this.run();
        },

        async run() {
            if (this.busy) {
                return;
            }

            this.busy = true;

            while (this.queue.length) {
                const file = await resize(this.queue.shift());
                this.progress = 0;

                await new Promise((resolve) => {
                    this.$wire.upload('upload', file, resolve, resolve, (event) => {
                        this.progress = event.detail.progress;
                    });
                });
            }

            this.busy = false;
        },

        // Drag to reorder (desktop); the arrow buttons do the same on touch screens.
        dragStart(index) {
            this.dragFrom = index;
        },

        dropOn(index) {
            if (this.dragFrom !== null && this.dragFrom !== index) {
                this.$wire.movePhoto(this.dragFrom, index);
            }

            this.dragFrom = null;
        },
    }));

    // Location step: click the map or drag the pin; the server stores it and finds the area.
    Alpine.data('pinMap', (lat = null, lng = null) => ({
        loading: true,
        map: null,
        marker: null,

        async init() {
            const L = await loadLeaflet();
            const start = lat !== null && lng !== null ? [lat, lng] : KOBULETI;

            this.map = L.map(this.$refs.map, { scrollWheelZoom: false, tap: true }).setView(start, lat !== null ? 16 : 13);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(this.map);

            if (lat !== null && lng !== null) {
                this.place(L, lat, lng);
            }

            this.map.on('click', (event) => {
                this.map.scrollWheelZoom.enable();
                this.place(L, event.latlng.lat, event.latlng.lng);
                this.$wire.setPin(event.latlng.lat, event.latlng.lng);
            });

            this.loading = false;
        },

        place(L, pointLat, pointLng) {
            if (this.marker) {
                this.marker.setLatLng([pointLat, pointLng]);

                return;
            }

            this.marker = L.marker([pointLat, pointLng], {
                draggable: true,
                autoPan: true,
                icon: L.divIcon({ className: 'map-main-pin', html: '<span></span>', iconSize: [30, 30], iconAnchor: [15, 30] }),
            }).addTo(this.map);

            this.marker.on('dragend', () => {
                const position = this.marker.getLatLng();
                this.$wire.setPin(position.lat, position.lng);
            });
        },

        moveTo(detail) {
            if (!this.map) {
                return;
            }

            loadLeaflet().then((L) => {
                this.place(L, detail.lat, detail.lng);
                this.map.setView([detail.lat, detail.lng], 17);
            });
        },
    }));
}

/**
 * Owner availability calendar: press and drag across nights (or tap the first and the last
 * night on touch screens) to select a range, then close, open or price it.
 */
export function registerRangeCalendar(Alpine) {
    Alpine.data('rangeCalendar', () => ({
        start: null,
        end: null,
        dragging: false,

        get from() {
            return this.start && this.end ? (this.start < this.end ? this.start : this.end) : this.start;
        },

        get to() {
            return this.start && this.end ? (this.start < this.end ? this.end : this.start) : this.start;
        },

        selected(date) {
            return this.start !== null && date >= this.from && date <= this.to;
        },

        down(date) {
            // Second tap on touch screens completes the range.
            if (this.start !== null && this.end === null && !this.dragging) {
                this.end = date;

                return;
            }

            this.start = date;
            this.end = null;
            this.dragging = true;
        },

        enter(date) {
            if (this.dragging) {
                this.end = date;
            }
        },

        up(date) {
            if (!this.dragging) {
                return;
            }

            this.dragging = false;

            if (date !== this.start) {
                this.end = date;
            }
        },

        clear() {
            this.start = null;
            this.end = null;
            this.dragging = false;
        },

        apply(action) {
            if (this.start === null) {
                return;
            }

            this.$wire.applyRange(this.from, this.to ?? this.from, action).then(() => this.clear());
        },
    }));
}
