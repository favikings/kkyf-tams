document.documentElement.dataset.ready = 'true';

window.addEventListener('DOMContentLoaded', () => {
    const appShell = document.querySelector('.app-shell');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarClose = document.querySelector('[data-sidebar-close]');

    const setSidebar = (open) => {
        if (!appShell || !sidebarToggle) {
            return;
        }

        appShell.classList.toggle('is-sidebar-open', open);
        sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    };

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setSidebar(!appShell?.classList.contains('is-sidebar-open'));
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', () => setSidebar(false));
    }

    document.querySelectorAll('.sidebar a').forEach((link) => {
        link.addEventListener('click', () => setSidebar(false));
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 880) {
            setSidebar(false);
        }
    });

    const openModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        modal.querySelector('input, select, textarea, button')?.focus();
    };

    const closeModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (!appShell?.classList.contains('is-sidebar-open')) {
            document.body.style.overflow = '';
        }
    };

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            openModal(document.querySelector(`[data-modal="${button.dataset.modalOpen}"]`));
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(button.closest('[data-modal]'));
        });
    });

    document.querySelectorAll('[data-modal]').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-modal].is-open').forEach(closeModal);
        }
    });

    const notice = document.querySelector('.notice');
    const alert = document.querySelector('.alert');

    if (window.Swal && notice) {
        window.Swal.fire({
            icon: 'success',
            title: 'Done',
            text: notice.textContent.trim(),
            confirmButtonColor: '#00a83b'
        });
    }

    if (window.Swal && alert) {
        window.Swal.fire({
            icon: 'error',
            title: 'Needs attention',
            text: alert.textContent.trim(),
            confirmButtonColor: '#c2410c'
        });
    }
});
