import { postJson, requireLogin, siteUrl, toast } from './http';

/**
 * Favorite hearts share one delegated listener instead of an Alpine component each: a dozen cards per
 * page would otherwise mean a dozen components to initialise at startup. The heart flips at once and
 * flips back if the server says no; guests get the sign-in dialog.
 */
export function initFavorites() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-favorite]');

        if (!button) {
            return;
        }

        event.preventDefault();

        const item = button.dataset.favorite;
        const url = siteUrl('favorites');

        if (!item || !url || button.dataset.busy) {
            return;
        }

        const [type, id] = item.split(':');
        const was = button.getAttribute('aria-pressed') === 'true';

        button.dataset.busy = '1';
        button.setAttribute('aria-pressed', was ? 'false' : 'true');

        try {
            const { ok, status, data } = await postJson(`${url}/${type}/${id}`);

            if (status === 401) {
                button.setAttribute('aria-pressed', was ? 'true' : 'false');
                requireLogin();

                return;
            }

            if (!ok) {
                throw new Error(String(status));
            }

            document.querySelectorAll(`[data-favorite="${CSS.escape(item)}"]`).forEach((other) => {
                other.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
            });
            toast(data.message, 'success');
        } catch {
            button.setAttribute('aria-pressed', was ? 'true' : 'false');
            toast(document.documentElement.dataset.errorMessage, 'error');
        } finally {
            delete button.dataset.busy;
        }
    });

    window.addEventListener('require-login', requireLogin);
}
