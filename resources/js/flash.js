/*
 * Global flash-message + confirm dialogs (docs/starter.md §15). No third-party
 * UI library — everything here is hand-rolled to match the brand theme.
 *
 *  - Server-side flashes are handed to the page as `window.__flash` by
 *    resources/views/partials/flash.blade.php; each becomes a small frameless
 *    "toast" card that slides in from the top-right edge (styled in
 *    resources/css/irnoti.css → .toast-stack / .toast).
 *  - Any `[data-confirm]` link/button/form is upgraded into a glassy confirm
 *    dialog (.confirm-overlay / .confirm-card).
 */

/* ---------------------------------------------------------------- toasts --- */

const TOAST_ICONS = {
    success:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    error:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>',
    warning:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>',
    info:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16v-4m0-4h.01"/><circle cx="12" cy="12" r="10"/></svg>',
};

const TOAST_KINDS = ['success', 'error', 'warning', 'info'];
const TOAST_TTL = 5000;

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
}

function toastStack() {
    let stack = document.querySelector('.toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'toast-stack';
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
    }
    return stack;
}

/**
 * @param {string} type   one of TOAST_KINDS (anything else → info)
 * @param {string} text   body — "\n" renders as line breaks
 * @param {object} [opts] { title, sticky } — sticky toasts don't auto-dismiss
 */
function toast(type, text, opts = {}) {
    const kind = TOAST_KINDS.includes(type) ? type : 'info';
    const { title = '', sticky = false } = opts;
    const rich = sticky || /\n/.test(String(text));

    const node = document.createElement('div');
    node.className = `toast toast--${kind}${rich ? ' toast--rich' : ''}`;
    node.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    node.innerHTML = `
        <span class="toast__icon">${TOAST_ICONS[kind]}</span>
        <div class="toast__body">
            ${title ? `<p class="toast__title">${escapeHtml(title)}</p>` : ''}
            <p class="toast__text">${escapeHtml(text)}</p>
        </div>
        <button type="button" class="toast__close" aria-label="بستن">&times;</button>
    `;

    toastStack().appendChild(node);
    requestAnimationFrame(() => node.classList.add('is-in'));

    let timer;
    let closing = false;

    const dismiss = () => {
        if (closing) return;
        closing = true;
        window.clearTimeout(timer);
        node.classList.remove('is-in');
        node.classList.add('is-out');
        node.addEventListener('transitionend', () => node.remove(), { once: true });
        window.setTimeout(() => node.remove(), 600);
    };

    const arm = () => {
        if (sticky) return;
        timer = window.setTimeout(dismiss, TOAST_TTL);
    };

    arm();
    node.querySelector('.toast__close').addEventListener('click', dismiss);
    node.addEventListener('mouseenter', () => window.clearTimeout(timer));
    node.addEventListener('mouseleave', arm);
}

/* --------------------------------------------------------- confirm dialog -- */

function confirmDialog({ title, text = '', yes = 'بله', no = 'انصراف', danger = false }) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'confirm-overlay';
        overlay.innerHTML = `
            <div class="confirm-card" role="alertdialog" aria-modal="true">
                <p class="confirm-card__title">${escapeHtml(title || 'آیا مطمئن هستید؟')}</p>
                ${text ? `<p class="confirm-card__text">${escapeHtml(text)}</p>` : ''}
                <div class="confirm-card__actions">
                    <button type="button" class="confirm-card__btn confirm-card__btn--yes"${danger ? ' data-danger' : ''}>${escapeHtml(yes)}</button>
                    <button type="button" class="confirm-card__btn confirm-card__btn--no">${escapeHtml(no)}</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        requestAnimationFrame(() => overlay.classList.add('is-in'));

        const btnYes = overlay.querySelector('.confirm-card__btn--yes');
        const btnNo = overlay.querySelector('.confirm-card__btn--no');
        btnYes.focus();

        const close = (result) => {
            overlay.classList.remove('is-in');
            window.setTimeout(() => overlay.remove(), 250);
            document.removeEventListener('keydown', onKey);
            resolve(result);
        };
        const onKey = (e) => {
            if (e.key === 'Escape') close(false);
        };

        btnYes.addEventListener('click', () => close(true));
        btnNo.addEventListener('click', () => close(false));
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close(false);
        });
        document.addEventListener('keydown', onKey);
    });
}

/* ------------------------------------------------------- server flashes --- */

function showFlashes() {
    const items = Array.isArray(window.__flash) ? window.__flash : [];

    items.forEach((item) => {
        if (!item || !item.text) return;

        const type = TOAST_KINDS.includes(item.type) ? item.type : 'info';
        const multiline = item.modal || /\n/.test(item.text) || item.text.length > 160;

        toast(type, item.text, {
            title: item.title || (multiline && type === 'error' ? 'خطا' : ''),
            sticky: multiline,
        });
    });

    window.__flash = [];
}

/* --------------------------------------------------------- [data-confirm] -- */

function wireConfirms() {
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        if (el.dataset.confirmBound) return;
        el.dataset.confirmBound = '1';

        el.addEventListener('click', (e) => {
            e.preventDefault();

            const form = el.closest('form');
            const href = el.getAttribute('href');

            confirmDialog({
                title: el.dataset.confirm || 'آیا مطمئن هستید؟',
                text: el.dataset.confirmText || '',
                yes: el.dataset.confirmYes || 'بله',
                no: el.dataset.confirmNo || 'انصراف',
                danger: true,
            }).then((confirmed) => {
                if (!confirmed) return;
                if (form) form.submit();
                else if (href) window.location.href = href;
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    showFlashes();
    wireConfirms();
});

// Ad-hoc use from inline scripts if ever needed.
window.toast = toast;
window.confirmDialog = confirmDialog;
