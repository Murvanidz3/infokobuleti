import { registerAuth } from './auth';
import { registerComponents } from './components';
import { initFavorites } from './favorites';
import { registerListing } from './listing';
import { registerMap } from './map';
import { initReveal } from './reveal';

// Registers the main font files in the Vite manifest so the layout can preload them.
import.meta.glob([
    '/node_modules/@fontsource-variable/noto-sans-georgian/files/noto-sans-georgian-georgian-wght-normal.woff2',
    '/node_modules/@fontsource-variable/noto-sans-georgian/files/noto-sans-georgian-latin-wght-normal.woff2',
    '/node_modules/@fontsource-variable/noto-sans/files/noto-sans-cyrillic-wght-normal.woff2',
    '/node_modules/@fontsource/noto-serif-georgian/files/noto-serif-georgian-georgian-600-normal.woff2',
    '/node_modules/@fontsource/noto-serif-georgian/files/noto-serif-georgian-latin-600-normal.woff2',
    '/node_modules/@fontsource/noto-serif/files/noto-serif-cyrillic-600-normal.woff2',
]);

/**
 * UI behaviour shared by both entry points (app.js and livewire.js).
 */
export function setupUi(Alpine) {
    registerComponents(Alpine);
    registerAuth(Alpine);
    registerListing(Alpine);
    registerMap(Alpine);
    initFavorites();
    initReveal();
}
