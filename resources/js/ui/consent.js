/**
 * Cookie consent. The choice ("all" or "necessary") is kept for 180 days in the "ik_consent" cookie;
 * Google Analytics is loaded only after "all" (Consent Mode: ads storage always denied).
 */
const COOKIE = 'ik_consent';

function readChoice() {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

function writeChoice(value) {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${value}; Max-Age=${180 * 86400}; Path=/; SameSite=Lax${secure}`;
}

function clearAnalyticsCookies() {
    document.cookie.split('; ').forEach((pair) => {
        const name = pair.split('=')[0];

        if (name === '_ga' || name.startsWith('_ga_') || name === '_gid') {
            const domain = location.hostname.split('.').slice(-2).join('.');
            document.cookie = `${name}=; Max-Age=0; Path=/`;
            document.cookie = `${name}=; Max-Age=0; Path=/; Domain=.${domain}`;
        }
    });
}

let analyticsLoaded = false;

function loadAnalytics(id) {
    if (!id || analyticsLoaded) {
        return;
    }

    analyticsLoaded = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() {
        window.dataLayer.push(arguments);
    };
    window.gtag('consent', 'default', {
        analytics_storage: 'granted',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
    });
    window.gtag('js', new Date());
    window.gtag('config', id, { anonymize_ip: true });

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
    document.head.appendChild(script);
}

export function registerConsent(Alpine) {
    Alpine.data('cookieConsent', (analyticsId) => ({
        open: false,

        init() {
            const choice = readChoice();

            if (choice === 'all') {
                loadAnalytics(analyticsId);
            } else if (choice === null) {
                setTimeout(() => (this.open = true), 600);
            }

            window.addEventListener('cookie-settings', () => (this.open = true));
        },

        choose(value) {
            const before = readChoice();
            writeChoice(value);
            this.open = false;
            window.dispatchEvent(new CustomEvent('cookie-chosen'));

            if (value === 'all') {
                loadAnalytics(analyticsId);
            } else if (before === 'all') {
                clearAnalyticsCookies();
                location.reload();
            }
        },
    }));
}
