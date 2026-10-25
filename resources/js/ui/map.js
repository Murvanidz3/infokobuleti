/**
 * Leaflet + OpenStreetMap, loaded only when a map is on the page (dynamic import keeps ~45 KB of
 * map code out of every other page). Markers are clustered; listings with a price show it on the pin.
 */
const KOBULETI = [41.8214, 41.7792];

let leaflet = null;

async function loadLeaflet() {
    leaflet ??= (async () => {
        const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css'), import('../../css/map.css')]);
        window.L = L;
        await Promise.all([import('leaflet.markercluster'), import('leaflet.markercluster/dist/MarkerCluster.css')]);

        return L;
    })();

    return leaflet;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';

    return div.innerHTML;
}

function pinIcon(L, marker) {
    if (marker.pin) {
        return L.divIcon({
            className: 'map-price-pin',
            html: `<span>${escapeHtml(marker.pin)}</span>`,
            iconSize: null,
            iconAnchor: [0, 0],
        });
    }

    return L.divIcon({ className: 'map-dot-pin', html: '<span></span>', iconSize: [22, 22], iconAnchor: [11, 11] });
}

function popupHtml(marker, openLabel) {
    const image = marker.image ? `<img src="${escapeHtml(marker.image)}" alt="" loading="lazy" class="map-popup__image">` : '';
    const meta = marker.meta ? `<span class="map-popup__meta">${escapeHtml(marker.meta)}</span>` : '';

    return `<a href="${escapeHtml(marker.url)}" class="map-popup">${image}<span class="map-popup__body"><span class="map-popup__title">${escapeHtml(marker.title)}</span>${meta}<span class="map-popup__link">${escapeHtml(openLabel)} →</span></span></a>`;
}

export function registerMap(Alpine) {
    Alpine.data('listingMap', (markers = [], options = {}) => ({
        loading: true,
        moved: false,
        map: null,
        layer: null,
        markers,
        fitting: false,

        async init() {
            const observer = new IntersectionObserver(async ([entry]) => {
                if (entry.isIntersecting) {
                    observer.disconnect();
                    await this.boot();
                }
            }, { rootMargin: '200px' });

            observer.observe(this.$el);
        },

        async boot() {
            const L = await loadLeaflet();

            this.map = L.map(this.$refs.map, { scrollWheelZoom: false, zoomControl: true }).setView(KOBULETI, 13);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(this.map);

            this.layer = L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 48 });
            this.map.addLayer(this.layer);
            this.map.on('click', () => this.map.scrollWheelZoom.enable());
            this.map.on('moveend', () => {
                if (!this.fitting && options.searchArea !== false) {
                    this.moved = true;
                }
            });

            this.loading = false;
            this.render(true);
        },

        update(markers) {
            this.markers = markers ?? [];
            this.moved = false;
            this.render(false);
        },

        render(fit) {
            if (!this.map) {
                return;
            }

            const L = window.L;
            this.layer.clearLayers();

            const points = this.markers.map((marker) => {
                const leafletMarker = L.marker([marker.lat, marker.lng], { icon: pinIcon(L, marker), title: marker.title, keyboard: true });
                leafletMarker.bindPopup(popupHtml(marker, options.labels?.open ?? ''), { maxWidth: 260, minWidth: 220 });

                return leafletMarker;
            });

            this.layer.addLayers(points);

            if (fit && points.length > 0) {
                this.fitting = true;
                this.map.fitBounds(this.layer.getBounds(), { padding: [40, 40], maxZoom: 16 });
                this.map.once('moveend', () => {
                    this.fitting = false;
                });
            }
        },

        searchHere() {
            const bounds = this.map.getBounds();
            this.moved = false;
            this.$wire.searchArea(bounds.getSouth(), bounds.getWest(), bounds.getNorth(), bounds.getEast());
        },
    }));

    // A single static location (detail pages): one pin plus optional nearby places.
    Alpine.data('placeMap', (center, nearby = [], openLabel = '') => ({
        loading: true,

        init() {
            const observer = new IntersectionObserver(async ([entry]) => {
                if (!entry.isIntersecting) {
                    return;
                }

                observer.disconnect();

                const L = await loadLeaflet();
                const map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView([center.lat, center.lng], 15);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                }).addTo(map);

                L.marker([center.lat, center.lng], {
                    icon: L.divIcon({ className: 'map-main-pin', html: '<span></span>', iconSize: [30, 30], iconAnchor: [15, 30] }),
                    title: center.title,
                }).addTo(map);

                nearby.forEach((place) => {
                    L.marker([place.lat, place.lng], { icon: pinIcon(L, { pin: null }), title: place.title })
                        .bindPopup(popupHtml(place, openLabel), { maxWidth: 260, minWidth: 200 })
                        .addTo(map);
                });

                map.on('click', () => map.scrollWheelZoom.enable());
                this.loading = false;
            }, { rootMargin: '200px' });

            observer.observe(this.$el);
        },
    }));
}
