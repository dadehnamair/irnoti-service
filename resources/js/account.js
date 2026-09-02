/*
 * irnoti — customer auth + account behaviour (docs/starter.md §26/§27).
 * Loaded on /register, /login, /verify and /dashboard* only.
 *   - OTP resend countdown
 *   - auto-advance / numeric-only on the OTP input
 */

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

document.addEventListener('DOMContentLoaded', () => {
    otpResendCountdown();
    otpInput();
});
