const desktop = () => window.matchMedia('(min-width: 45rem)').matches;

let turnstileScript = null;

function loadTurnstile() {
    turnstileScript ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.onload = () => resolve(window.turnstile);
        script.onerror = reject;
        document.head.appendChild(script);
    });

    return turnstileScript;
}

function formatNational(digits) {
    const parts = [digits.slice(0, 3), digits.slice(3, 5), digits.slice(5, 7), digits.slice(7, 9)];

    return parts.filter(Boolean).join(' ');
}

function nationalDigits(value) {
    let digits = value.replace(/\D/g, '');

    if (digits.startsWith('995') && digits.length > 9) {
        digits = digits.slice(3);
    }

    return digits.slice(0, 9);
}

function clock(seconds) {
    const minutes = Math.floor(seconds / 60);

    return `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
}

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify(body),
    });

    // An expired CSRF token or a session that ended elsewhere: start over with a fresh page.
    if (response.status === 419) {
        window.location.reload();

        return new Promise(() => {});
    }

    let data = {};

    try {
        data = await response.json();
    } catch {
        data = {};
    }

    return { ok: response.ok, status: response.status, data };
}

export function registerAuth(Alpine) {
    // Phone sign-in: number -> 6-digit SMS code -> signed in. Used in the header dialog and on /login.
    Alpine.data('phoneLogin', (config) => ({
        step: 'phone',
        phone: '',
        code: '',
        remember: true,
        busy: false,
        error: '',
        sentTo: '',
        devCode: '',
        resendIn: 0,
        captchaToken: '',
        captchaId: null,
        timer: null,
        otpAbort: null,

        init() {
            if (config.turnstile) {
                loadTurnstile().then((turnstile) => {
                    this.captchaId = turnstile.render(this.$refs.captcha, {
                        sitekey: config.turnstile,
                        language: config.locale,
                        callback: (token) => (this.captchaToken = token),
                        'expired-callback': () => (this.captchaToken = ''),
                    });
                });
            }

            this.$nextTick(() => this.$refs.phone?.focus({ preventScroll: true }));
        },

        destroy() {
            clearInterval(this.timer);
            this.otpAbort?.abort();
        },

        get googleHref() {
            return this.remember ? config.googleUrl : `${config.googleUrl}${config.googleUrl.includes('?') ? '&' : '?'}remember=0`;
        },

        get resendLabel() {
            return this.resendIn > 0 ? config.messages.resendIn.replace(':time', clock(this.resendIn)) : config.messages.resend;
        },

        onPhoneInput() {
            this.phone = formatNational(nationalDigits(this.phone));
            this.error = '';
        },

        onCodeInput() {
            this.code = this.code.replace(/\D/g, '').slice(0, config.codeLength);
            this.error = '';

            if (this.code.length === config.codeLength) {
                this.verify();
            }
        },

        async send() {
            if (this.busy) {
                return;
            }

            if (nationalDigits(this.phone).length !== 9) {
                this.error = config.messages.invalidPhone;
                this.$refs.phone?.focus();

                return;
            }

            this.busy = true;
            this.error = '';

            try {
                const { ok, data } = await postJson(config.sendUrl, { phone: this.phone, captcha_token: this.captchaToken });

                if (!ok) {
                    this.error = data.errors?.phone?.[0] ?? data.errors?.captcha_token?.[0] ?? data.message ?? config.messages.failed;
                    this.countdown(data.retry_after ?? 0);

                    return;
                }

                this.sentTo = data.phone;
                this.devCode = data.dev_code ?? '';
                this.step = 'code';
                this.code = '';
                this.countdown(data.resend_in ?? 60);
                this.listenForSms();
                this.$nextTick(() => this.$refs.code?.focus());
            } catch {
                this.error = config.messages.network;
            } finally {
                this.busy = false;
                this.resetCaptcha();
            }
        },

        async verify() {
            if (this.busy || this.code.length !== config.codeLength) {
                return;
            }

            this.busy = true;
            this.error = '';

            try {
                const { ok, data } = await postJson(config.verifyUrl, {
                    phone: this.phone,
                    code: this.code,
                    remember: this.remember,
                    return_to: config.returnTo,
                });

                if (ok && data.redirect) {
                    window.location.assign(data.redirect);

                    return new Promise(() => {});
                }

                this.error = data.errors?.code?.[0] ?? data.message ?? config.messages.failed;
                this.code = '';

                if (data.restart) {
                    this.resendIn = 0;
                }

                this.$nextTick(() => this.$refs.code?.focus());
            } catch {
                this.error = config.messages.network;
            } finally {
                this.busy = false;
            }
        },

        async resend() {
            if (this.resendIn > 0 || this.busy) {
                return;
            }

            await this.send();
        },

        back() {
            this.step = 'phone';
            this.code = '';
            this.error = '';
            this.devCode = '';
            this.otpAbort?.abort();
            this.$nextTick(() => this.$refs.phone?.focus());
        },

        countdown(seconds) {
            clearInterval(this.timer);
            this.resendIn = Math.max(0, Math.ceil(seconds));

            if (this.resendIn === 0) {
                return;
            }

            this.timer = setInterval(() => {
                this.resendIn = Math.max(0, this.resendIn - 1);

                if (this.resendIn === 0) {
                    clearInterval(this.timer);
                }
            }, 1000);
        },

        resetCaptcha() {
            if (this.captchaId !== null && window.turnstile) {
                window.turnstile.reset(this.captchaId);
                this.captchaToken = '';
            }
        },

        // Android Chrome can read the code from the SMS (WebOTP); the last SMS line names our domain.
        listenForSms() {
            if (!('OTPCredential' in window)) {
                return;
            }

            this.otpAbort?.abort();
            this.otpAbort = new AbortController();

            navigator.credentials
                .get({ otp: { transport: ['sms'] }, signal: this.otpAbort.signal })
                .then((credential) => {
                    if (credential?.code && this.step === 'code') {
                        this.code = credential.code;
                        this.onCodeInput();
                    }
                })
                .catch(() => {});
        },
    }));

    // Resend countdown inside Livewire forms (account phone change).
    Alpine.data('resendTimer', (seconds, template) => ({
        left: seconds,
        timer: null,

        init() {
            this.tick();
        },

        destroy() {
            clearInterval(this.timer);
        },

        tick() {
            clearInterval(this.timer);

            if (this.left <= 0) {
                return;
            }

            this.timer = setInterval(() => {
                this.left = Math.max(0, this.left - 1);

                if (this.left === 0) {
                    clearInterval(this.timer);
                }
            }, 1000);
        },

        get label() {
            return template.replace(':time', clock(this.left));
        },
    }));

    // Header "Sign in": a dialog from 720px up, the /login page on phones (and without JavaScript).
    Alpine.data('loginTrigger', () => ({
        trigger: {
            'x-on:click'(event) {
                const dialog = document.querySelector('[data-login-dialog]');

                if (!dialog || !desktop() || event.metaKey || event.ctrlKey || event.shiftKey) {
                    return;
                }

                event.preventDefault();

                const template = dialog.querySelector('template[data-login-panel]');

                if (template) {
                    template.replaceWith(template.content.cloneNode(true));
                }

                window.dispatchEvent(new CustomEvent('open-dialog', { detail: 'login' }));
            },
        },
    }));

    Alpine.data('userMenu', () => ({
        open: false,

        root: {
            'x-on:keydown.escape'() {
                this.open = false;
            },
            'x-on:click.outside'() {
                this.open = false;
            },
        },

        button: {
            'x-on:click'() {
                this.open = !this.open;
            },
            ':aria-expanded'() {
                return String(this.open);
            },
        },

        panel: {
            'x-show'() {
                return this.open;
            },
        },
    }));
}
