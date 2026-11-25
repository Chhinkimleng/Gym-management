import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Desktop sidebar collapse persistence
    const collapseBtn = document.getElementById('sidebar-collapse-btn');
    if (localStorage.getItem('gym_sidebar_collapsed') === '1') {
        document.body.classList.add('sidebar-collapsed');
    }
    if (collapseBtn) {
        collapseBtn.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('gym_sidebar_collapsed', isCollapsed ? '1' : '0');
        });
    }

    // 2. Mobile drawer navigation
    const mobileOpenBtn = document.getElementById('mobile-menu-open');
    const mobileCloseBtn = document.getElementById('mobile-menu-close');
    const sidebar = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('mobile-sidebar-backdrop');

    const openMobileSidebar = () => {
        if (!sidebar || !backdrop) return;
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
    };
    const closeMobileSidebar = () => {
        if (!sidebar || !backdrop) return;
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
    };

    mobileOpenBtn?.addEventListener('click', openMobileSidebar);
    mobileCloseBtn?.addEventListener('click', closeMobileSidebar);
    backdrop?.addEventListener('click', closeMobileSidebar);

    // 3. Dropdown menus (Notifications & Profile)
    document.querySelectorAll('[data-dropdown-toggle]').forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const targetId = trigger.getAttribute('data-dropdown-toggle');
            const menu = document.getElementById(targetId);
            document.querySelectorAll('[data-dropdown-menu]').forEach((m) => {
                if (m !== menu) m.classList.add('hidden');
            });
            menu?.classList.toggle('hidden');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown-menu]').forEach((m) => m.classList.add('hidden'));
    });

    // 4. Accessible confirmation modal for destructive/important actions
    const confirmModal = document.getElementById('global-confirm-modal');
    const confirmTitle = document.getElementById('confirm-modal-title');
    const confirmMessage = document.getElementById('confirm-modal-message');
    const confirmOkBtn = document.getElementById('confirm-modal-ok');
    const confirmCancelBtn = document.getElementById('confirm-modal-cancel');
    let pendingForm = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (form.dataset.confirmed === 'true') return;
            e.preventDefault();
            pendingForm = form;
            if (confirmTitle) confirmTitle.textContent = form.dataset.confirmTitle || 'Confirm Action';
            if (confirmMessage) confirmMessage.textContent = form.dataset.confirm || 'Are you sure you want to proceed?';
            confirmModal?.classList.remove('hidden');
        });
    });

    confirmCancelBtn?.addEventListener('click', () => {
        pendingForm = null;
        confirmModal?.classList.add('hidden');
    });

    confirmOkBtn?.addEventListener('click', () => {
        if (pendingForm) {
            pendingForm.dataset.confirmed = 'true';
            pendingForm.submit();
        }
        confirmModal?.classList.add('hidden');
    });

    // 5. Loading indicator on submit buttons to prevent accidental double-clicks
    document.querySelectorAll('form:not([data-no-loading])').forEach((form) => {
        form.addEventListener('submit', () => {
            if (form.dataset.confirm && form.dataset.confirmed !== 'true') return;
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.dataset.loadingApplied) {
                btn.dataset.loadingApplied = 'true';
                btn.classList.add('opacity-75', 'pointer-events-none');
                const label = btn.querySelector('.btn-label');
                if (label) {
                    label.dataset.original = label.textContent;
                    label.textContent = btn.dataset.loadingText || 'Processing...';
                }
            }
        });
    });

    // 6. Auto-dismiss flash alerts after 6 seconds
    document.querySelectorAll('[data-auto-dismiss]').forEach((el) => {
        setTimeout(() => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        }, 6000);
    });
});
