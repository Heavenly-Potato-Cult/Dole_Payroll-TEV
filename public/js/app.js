/**
 * DOLE RO9 Payroll System — Global JS
 * Plain ES modules — no framework needed
 */

// ── Auto-dismiss flash alerts ─────────────────────────────────
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// ── CSRF token helper for fetch() calls ───────────────────────
window.csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

// ── Confirm delete forms ──────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        const msg = el.getAttribute('data-confirm') || 'Are you sure?';
        if (!confirm(msg)) e.preventDefault();
    });
});

// ── Active nav highlight (fallback for Blade active class) ────
if (typeof currentPath === 'undefined') {
    window.currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
}
