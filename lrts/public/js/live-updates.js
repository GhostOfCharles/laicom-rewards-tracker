(() => {
    'use strict';

    function start() {
        const notice = document.querySelector('[data-live-dashboard]');
        if (!notice) return;
        const interval = 15000;
        const refreshButton = notice.querySelector('[data-live-refresh]');
        const status = notice.querySelector('[data-live-status]');
        const baselines = new WeakMap();
        let applied = notice.dataset.liveFingerprint;
        let etag = `"${applied}"`;
        let pending = false;
        let timer = null;
        let checking = false;
        let refreshing = false;
        let immediate = false;
        let stopped = false;
        let suspended = false;
        let failures = 0;

        function formValue(form) {
            return JSON.stringify(Array.from(form.elements)
                .filter(field => /^(INPUT|TEXTAREA|SELECT)$/.test(field.tagName) && !['submit', 'button', 'reset', 'file'].includes(field.type))
                .map(field => [field.name || field.id, field.type === 'checkbox' || field.type === 'radio' ? field.checked :
                    field.multiple ? Array.from(field.selectedOptions, option => option.value) : field.value]));
        }

        function rememberForms() {
            document.querySelectorAll('form').forEach(form => {
                if (!baselines.has(form)) baselines.set(form, formValue(form));
            });
        }

        function blocked() {
            if (document.hidden || suspended) return true;
            if (document.querySelector('.modal.show, .modal.showing, .modal-backdrop, .lrts-drawer.is-open, .lrts-lightbox.is-open, .dropdown-menu.show')) return true;
            if (document.activeElement?.matches('input, textarea, select, [contenteditable="true"]')) return true;
            if (Array.from(document.querySelectorAll('input[type="file"]')).some(input => input.files?.length)) return true;
            return Array.from(document.forms).some(form => baselines.has(form) && baselines.get(form) !== formValue(form));
        }

        function showPending() {
            notice.hidden = false;
            refreshButton.hidden = false;
        }

        function expireSession() {
            stopped = true;
            clearTimeout(timer);
            timer = null;
            notice.hidden = false;
            refreshButton.hidden = true;
            notice.querySelector('[data-live-expired]').hidden = false;
            status.textContent = 'Session expired, please log in again';
        }

        function validSession(response) {
            if (response.redirected || [401, 419, 403].includes(response.status)) {
                expireSession();
                return false;
            }
            return true;
        }

        function updateCounts(counts) {
            document.querySelectorAll('[data-admin-count], [data-customer-count]').forEach(badge => {
                const key = badge.dataset.adminCount || badge.dataset.customerCount;
                if (!(key in counts)) return;
                const count = Number(counts[key]) || 0;
                badge.textContent = key === 'premium-expiry' ? `${count} EXPIRY ALERTS` : String(count);
                badge.hidden = count < 1;
            });
        }

        function saveView() {
            const selector = '.admin-scroll-content, .lrts-content, .table-responsive, .lrts-drawer-body, .adm-ticket-list, .adm-ticket-thread, .modal-body, [data-live-region]';
            const scrolls = Array.from(document.querySelectorAll(selector)).map(element => {
                const region = element.closest('[data-live-region]');
                return { element, id: element.id, regionKey: region?.dataset.liveRegion,
                    index: region === element ? -1 : region ? Array.from(region.querySelectorAll(selector)).indexOf(element) : -1,
                    top: element.scrollTop, left: element.scrollLeft };
            });
            const tab = document.querySelector('[data-bs-toggle="pill"].active, [data-bs-toggle="tab"].active')?.id;
            const section = document.querySelector('[data-promos-section].is-active')?.dataset.promosSection;
            const x = window.scrollX;
            const y = window.scrollY;
            return () => {
                if (tab && !document.getElementById(tab)?.classList.contains('active')) bootstrap.Tab.getOrCreateInstance(document.getElementById(tab)).show();
                if (section) document.querySelector(`[data-promos-section="${section}"]`)?.click();
                scrolls.forEach(saved => {
                    const region = Array.from(document.querySelectorAll('[data-live-region]')).find(item => item.dataset.liveRegion === saved.regionKey);
                    const element = saved.element.isConnected ? saved.element : document.getElementById(saved.id) ||
                        (saved.index < 0 ? region : region?.querySelectorAll(selector)[saved.index]);
                    if (element) { element.scrollTop = saved.top; element.scrollLeft = saved.left; }
                });
                window.scrollTo(x, y);
            };
        }

        function protectedRegion(region) {
            if (region.matches('form, .modal') || region.closest('form') || region.querySelector('.modal, script')) return true;
            if (!region.hasAttribute('data-live-preserve-forms')) return !!region.querySelector('form, input, textarea, select');
            return Array.from(region.querySelectorAll('form')).some(form => !form.id) ||
                Array.from(region.querySelectorAll('input, textarea, select')).some(field => !field.closest('form'));
        }

        async function refreshRegions() {
            if (!pending || refreshing || stopped || blocked()) return;
            refreshing = true;
            try {
                const pageUrl = new URL(window.location.href);
                const selectedTicket = document.querySelector('[data-live-ticket]')?.dataset.liveTicket;
                if (selectedTicket) pageUrl.searchParams.set('ticket', selectedTicket);
                const response = await fetch(pageUrl.href, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin', cache: 'no-store', signal: AbortSignal.timeout(12000),
                });
                if (!validSession(response)) return;
                if (!response.ok) throw new Error('Page refresh failed');
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const source = page.querySelector('[data-live-dashboard]');
                if (!source || source.dataset.liveIdentity !== notice.dataset.liveIdentity) {
                    expireSession();
                    return;
                }
                if (stopped || blocked()) return;

                const incoming = new Map(Array.from(page.querySelectorAll('[data-live-region]'), region => [region.dataset.liveRegion, region]));
                const replacements = Array.from(document.querySelectorAll('[data-live-region]')).map(current => [current, incoming.get(current.dataset.liveRegion) || current.cloneNode(false)]);
                // Validate the whole patch before touching the current document.
                if (replacements.some(([current, next]) => !next || protectedRegion(current) || protectedRegion(next))) throw new Error('Unsafe live region');
                const restoreView = saveView();

                // Existing forms and modal nodes retain their values and event handlers.
                page.querySelectorAll('form[data-live-action]').forEach(form => {
                    if (!document.getElementById(form.id)) document.querySelector('[data-live-actions]').append(document.importNode(form, true));
                });
                page.querySelectorAll('.modal[id]').forEach(modal => {
                    if (!document.getElementById(modal.id)) document.body.append(document.importNode(modal, true));
                });
                replacements.forEach(([current, next]) => {
                    const fragment = document.createDocumentFragment();
                    fragment.append(...Array.from(next.childNodes, node => document.importNode(node, true)));
                    if (current.hasAttribute('data-live-preserve-forms')) {
                        fragment.querySelectorAll('form[id]').forEach(form => {
                            const existing = document.getElementById(form.id);
                            if (existing?.tagName === 'FORM') form.replaceWith(existing);
                        });
                        // Keep forms whose action is no longer displayed without destroying them.
                        current.querySelectorAll('form').forEach(form => document.querySelector('[data-live-actions]').append(form));
                    }
                    current.replaceChildren(fragment);
                    current.className = next.className;
                    current.hidden = next.hidden;
                });
                document.querySelectorAll('.laicom-admin-dashboard .modal').forEach(modal => document.body.append(modal));
                rememberForms();
                document.dispatchEvent(new CustomEvent('live:updated'));
                restoreView();
                applied = source.dataset.liveFingerprint;
                notice.dataset.liveFingerprint = applied;
                pending = etag !== `"${applied}"`;
                notice.hidden = !pending;
                status.textContent = 'Dashboard updated';
            } finally {
                refreshing = false;
            }
        }

        function schedule(delay = interval) {
            clearTimeout(timer);
            timer = null;
            if (!stopped && !document.hidden && !suspended) timer = window.setTimeout(check, delay);
        }

        async function check() {
            clearTimeout(timer);
            timer = null;
            if (stopped || document.hidden || suspended) return;
            if (checking) { immediate = true; return; }
            checking = true;
            try {
                const response = await fetch(notice.dataset.liveEndpoint, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'If-None-Match': etag },
                    credentials: 'same-origin', cache: 'no-cache', signal: AbortSignal.timeout(12000),
                });
                if (!validSession(response)) return;
                if (response.status !== 304) {
                    if (!response.ok) throw new Error('Heartbeat failed');
                    const snapshot = await response.json();
                    if (!snapshot.fingerprint || !snapshot.counts) throw new Error('Invalid heartbeat');
                    if (snapshot.scope !== notice.dataset.liveIdentity) { expireSession(); return; }
                    etag = response.headers.get('ETag') || `"${snapshot.fingerprint}"`;
                    updateCounts(snapshot.counts);
                    pending = snapshot.fingerprint !== applied;
                    if (pending) showPending();
                }
                await refreshRegions();
                failures = 0;
            } catch (_) {
                failures = Math.min(failures + 1, 3);
            } finally {
                checking = false;
                const delay = immediate ? 0 : failures ? interval * 2 ** (failures - 1) : interval;
                immediate = false;
                schedule(delay);
            }
        }

        function applyPending() {
            refreshRegions().catch(() => { if (!stopped) showPending(); });
        }

        rememberForms();
        refreshButton.addEventListener('click', () => {
            if (blocked()) status.textContent = 'Finish or reset your form, and close any open dialog to apply updates safely.';
            else applyPending();
        });
        document.addEventListener('hidden.bs.modal', applyPending);
        document.addEventListener('hidden.bs.dropdown', applyPending);
        document.addEventListener('focusout', () => requestAnimationFrame(applyPending));
        document.addEventListener('input', applyPending);
        document.addEventListener('change', applyPending);
        document.addEventListener('reset', () => queueMicrotask(applyPending));
        document.addEventListener('live:unblocked', applyPending);
        document.addEventListener('visibilitychange', () => {
            clearTimeout(timer);
            timer = null;
            if (!document.hidden) check();
        });
        window.addEventListener('pagehide', () => { suspended = true; clearTimeout(timer); timer = null; });
        window.addEventListener('pageshow', event => {
            suspended = false;
            if (event.persisted) check();
        });
        // The initial snapshot was rendered with the page; do not fetch it twice.
        schedule();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
