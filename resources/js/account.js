/*
 * irnoti — customer auth + account behaviour (docs/starter.md §26/§27).
 * Loaded on /register, /login, /verify and /dashboard* only.
 *   - OTP resend countdown
 *   - auto-advance / numeric-only on the OTP input
 *   - flash toasts + confirm dialogs (via ./flash.js)
 */

import './flash.js';

function otpResendCountdown() {
    const btn = document.querySelector('[data-resend]');
    const label = document.querySelector('[data-resend-label]');
    if (!btn) return;

    let remaining = parseInt(btn.dataset.resend || '0', 10);

    const tick = () => {
        if (remaining <= 0) {
            btn.disabled = false;
            if (label) label.textContent = '';
            return;
        }
        btn.disabled = true;
        if (label) label.textContent = `(${remaining} ثانیه)`;
        remaining -= 1;
        window.setTimeout(tick, 1000);
    };

    tick();
}

function otpInput() {
    const input = document.querySelector('.otp-input');
    if (!input) return;

    input.addEventListener('input', () => {
        input.value = input.value.replace(/\D+/g, '').slice(0, 5);
        if (input.value.length === 5) {
            input.closest('form')?.requestSubmit();
        }
    });
}

/* Monthly / yearly switch on the in-panel plans page (docs/starter.md §8/§24). */
function planPeriodSwitch() {
    const sw = document.querySelector('[data-period-switch]');
    if (!sw) return;

    const apply = (period) => {
        sw.querySelectorAll('button').forEach((b) =>
            b.classList.toggle('is-active', b.dataset.period === period),
        );
        document.querySelectorAll('.plan-price').forEach((el) => {
            const val = el.dataset[period];
            if (val) el.textContent = val;
        });
        document.querySelectorAll('.plan-saving').forEach((el) => {
            el.style.display = period === 'yearly' ? '' : 'none';
        });
        document.querySelectorAll('.plan-cta').forEach((el) => {
            if (el.dataset.checkout) {
                el.href = `${el.dataset.checkout}?period=${period}`;
            }
        });
    };

    sw.querySelectorAll('button').forEach((b) => {
        b.addEventListener('click', () => apply(b.dataset.period));
    });
    apply('monthly');
}

/* Character / SMS-part counter on the single-send form (docs/starter.md §12). */
function smsCounter() {
    const body = document.querySelector('[data-sms-body]');
    const out = document.querySelector('[data-sms-counter]');
    if (!body || !out) return;

    const fa = (n) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
    const update = () => {
        const len = body.value.length;
        const parts = Math.max(1, Math.ceil(len / 70));
        out.textContent = `${fa(len)} کاراکتر — ${fa(parts)} پیامک`;
    };

    body.addEventListener('input', update);
    update();
}

/*
 * Thousands separators on money inputs (docs/starter.md §22). Any
 * <input data-money-input> shows 1,234,567 while typing; a sibling hidden input
 * named "<name>" carries the raw integer, and the visible field is renamed to
 * "<name>_display" so only digits reach the server.
 */
function moneyInputs() {
    const nf = new Intl.NumberFormat('en-US');
    const toDigits = (s) =>
        String(s)
            .replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
            .replace(/[^\d]/g, '');

    document.querySelectorAll('input[data-money-input]').forEach((input) => {
        const name = input.getAttribute('name');
        let hidden = null;

        if (name) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            input.setAttribute('name', `${name}_display`);
            input.after(hidden);
        }

        const sync = () => {
            const raw = toDigits(input.value);
            input.value = raw ? nf.format(Number(raw)) : '';
            if (hidden) hidden.value = raw;
        };

        input.setAttribute('inputmode', 'numeric');
        input.addEventListener('input', sync);
        sync();
    });
}

/*
 * Compact multi-select for phonebook groups (docs/starter.md §17). A select-style
 * <button> opens a searchable checklist; the button label reflects the selection.
 * Works without JS too (the checklist just renders open-ish inline is avoided by
 * the [hidden] panel — with JS off the toggle does nothing, so keep groups few
 * OR rely on this). Progressive: only enhances [data-group-picker].
 */
function groupPickers() {
    const toFa = (n) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);

    document.querySelectorAll('[data-group-picker]').forEach((root) => {
        const toggle = root.querySelector('[data-group-picker-toggle]');
        const panel = root.querySelector('[data-group-picker-panel]');
        const label = root.querySelector('[data-group-picker-label]');
        const search = root.querySelector('[data-group-picker-search]');
        if (!toggle || !panel || !label) return;

        const opts = () => Array.from(root.querySelectorAll('[data-group-picker-option]'));

        const syncLabel = () => {
            const chosen = opts().filter((o) => o.querySelector('input')?.checked);
            if (chosen.length === 0) label.textContent = 'انتخاب گروه‌ها';
            else if (chosen.length === 1)
                label.textContent = chosen[0]
                    .querySelector('.group-picker__name')
                    .textContent.trim();
            else label.textContent = `${toFa(chosen.length)} گروه انتخاب شد`;
        };

        const open = () => {
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            if (search) search.focus();
        };
        const close = () => {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => (panel.hidden ? open() : close()));
        panel.addEventListener('change', syncLabel);
        document.addEventListener('click', (e) => {
            if (!root.contains(e.target)) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });

        if (search) {
            search.addEventListener('input', () => {
                const q = search.value.trim().toLowerCase();
                opts().forEach((o) => {
                    o.hidden = !(o.dataset.name || '').toLowerCase().includes(q);
                });
            });
        }

        close();
        syncLabel();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    otpResendCountdown();
    otpInput();
    planPeriodSwitch();
    smsCounter();
    moneyInputs();
    groupPickers();
});
