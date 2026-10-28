/**
 * Small JSON helpers for the site's own endpoints (CSRF token from the layout's meta tag).
 */
export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function siteUrl(name) {
    return document.querySelector(`meta[name="route-${name}"]`)?.getAttribute('content') ?? '';
}

export async function postJson(url, body = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    let data = {};

    try {
        data = await response.json();
    } catch {
        // Empty or non-JSON answer: handled by the status below.
    }

    return { ok: response.ok, status: response.status, data };
}

export function toast(message, type = 'info') {
    if (message) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
    }
}

/**
 * Guests: the sign-in dialog from 720px up, the /login page on phones.
 */
export function requireLogin() {
    const dialog = document.querySelector('[data-login-dialog]');

    if (dialog && window.matchMedia('(min-width: 45rem)').matches) {
        const template = dialog.querySelector('template[data-login-panel]');

        if (template) {
            template.replaceWith(template.content.cloneNode(true));
        }

        window.dispatchEvent(new CustomEvent('open-dialog', { detail: 'login' }));

        return;
    }

    const login = siteUrl('login');

    if (login) {
        window.location.href = login;
    }
}
