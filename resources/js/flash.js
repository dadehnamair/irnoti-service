/*
 * Global flash-message + confirm dialogs via SweetAlert2 (docs/starter.md §15).
 * Server-side flashes are handed to the page as `window.__flash` by
 * resources/views/partials/flash.blade.php; here we turn each into a toast.
 * Also upgrades any `[data-confirm]` link/button/form into a Swal confirm.
 */
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.css';

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4500,
    timerProgressBar: true,
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

const ICONS = ['success', 'error', 'warning', 'info', 'question'];

function showFlashes() {
    const items = Array.isArray(window.__flash) ? window.__flash : [];

    items.forEach((item) => {
        if (!item || !item.text) return;

        const icon = ICONS.includes(item.type) ? item.type : 'info';

        // Validation summaries / long text read better as a centered modal.
        if (item.modal || (item.text && item.text.length > 160) || /\n/.test(item.text || '')) {
            Swal.fire({
                icon,
                title: item.title || (icon === 'error' ? 'خطا' : ''),
                html: String(item.text).replace(/\n/g, '<br>'),
                confirmButtonText: 'باشه',
            });
        } else {
            Toast.fire({ icon, title: item.text });
        }
    });

    window.__flash = [];
}

function wireConfirms() {
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        if (el.dataset.confirmBound) return;
        el.dataset.confirmBound = '1';

        el.addEventListener('click', (e) => {
            e.preventDefault();

            const form = el.closest('form');
            const href = el.getAttribute('href');

            Swal.fire({
                icon: 'warning',
                title: el.dataset.confirm || 'آیا مطمئن هستید؟',
                text: el.dataset.confirmText || '',
                showCancelButton: true,
                confirmButtonText: el.dataset.confirmYes || 'بله',
                cancelButtonText: el.dataset.confirmNo || 'انصراف',
                confirmButtonColor: '#e11d48',
            }).then((result) => {
                if (!result.isConfirmed) return;
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

// Make it available for ad-hoc use from inline scripts if ever needed.
window.Swal = Swal;
