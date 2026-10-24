/**
 * Favorite hearts share one delegated listener instead of an Alpine component each:
 * a dozen cards per page would otherwise mean a dozen components to initialise at startup.
 */
export function initFavorites() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-favorite]');

        if (!button) {
            return;
        }

        event.preventDefault();
        button.setAttribute('aria-pressed', button.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
    });
}
