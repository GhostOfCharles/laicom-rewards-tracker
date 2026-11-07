function lrtsOpenDrawer() {
            document.getElementById('lrtsDrawer').classList.add('is-open');
            document.getElementById('lrtsDrawerBackdrop').classList.add('is-open');
            document.body.classList.add('lrts-drawer-open');
            document.getElementById('lrtsDrawer').setAttribute('aria-hidden', 'false');
        }

        function lrtsCloseDrawer() {
            document.getElementById('lrtsDrawer').classList.remove('is-open');
            document.getElementById('lrtsDrawerBackdrop').classList.remove('is-open');
            document.body.classList.remove('lrts-drawer-open');
            document.getElementById('lrtsDrawer').setAttribute('aria-hidden', 'true');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') lrtsCloseDrawer();
        });

        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);

            const requestedTab = params.get('tab');
            if (requestedTab === 'help') document.getElementById('help-tab')?.click();
            if (requestedTab === 'tracker') document.getElementById('tracker-tab')?.click();
            if (requestedTab === 'promos') document.getElementById('promos-tab')?.click();
            if (requestedTab === 'order') document.getElementById('order-tab')?.click();

            if (params.get('open_drawer') === '1') {
                lrtsOpenDrawer();
            }

            if (params.get('tab') === 'promos' && params.get('section') === 'history') document.querySelector('[data-promos-section="history"]')?.click();
            if (params.get('tab') === 'promos' && params.get('section') === 'claims') document.querySelector('[data-promos-section="claims"]')?.click();

            const ticketId = params.get('ticket');
            if (ticketId) {
                const modalEl = document.getElementById('viewTicketModal' + ticketId);
                if (modalEl) new bootstrap.Modal(modalEl).show();
            }
        });