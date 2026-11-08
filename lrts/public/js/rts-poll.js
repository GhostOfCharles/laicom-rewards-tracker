(() => {
    const root = document.body;
    if (!root.dataset.pulseUrl) return;
    let lastInput = Date.now();
    const pollers = [];
    let notice;
    function announce(text, reload = false) {
        if (!notice) {
            notice = document.createElement('div');
            notice.className = 'alert alert-info shadow-sm position-fixed bottom-0 start-50 translate-middle-x m-2';
            notice.style.zIndex = '1080';
            notice.setAttribute('role', 'status');
            notice.setAttribute('aria-live', 'polite');
            document.body.append(notice);
        }
        notice.replaceChildren(document.createTextNode(text + ' '));
        if (reload) {
            const button = document.createElement('button');
            button.className = 'btn btn-sm btn-outline-primary'; button.textContent = 'Load updates';
            button.addEventListener('click', refreshLists); notice.append(button);
        }
        const dismiss = document.createElement('button');
        dismiss.className = 'btn-close ms-2'; dismiss.setAttribute('aria-label', 'Dismiss update');
        dismiss.addEventListener('click', () => { notice.remove(); notice = null; }); notice.append(dismiss);
    }
    document.addEventListener('input', e => { if (e.target.form) e.target.form.dataset.unsaved = 'true'; }, true);
    async function refreshLists() {
        if (document.querySelector('.modal.show')) { announce('Close the open dialog before loading list updates.', true); return; }
        const regions = Array.from(document.querySelectorAll('[data-refresh-region]'));
        if (!regions.length) { announce('Updates are available. Reopen this list to view them.'); return; }
        if (regions.some(r => r.querySelector('form[data-unsaved]'))) { announce('Finish editing the filter before loading updates.', true); return; }
        const scroller = document.querySelector('.admin-scroll-content, .lrts-content');
        const scroll = scroller?.scrollTop;
        try {
            const response = await fetch(location.href, {headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest', 'X-RTS-Fragment': 'lists'}});
            if (!response.ok || response.redirected) throw new Error('Unable to load updates. Check your session and try again.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            regions.forEach(region => {
                const replacement = page.querySelector(`[data-refresh-region="${region.dataset.refreshRegion}"]`);
                if (replacement) region.replaceWith(replacement);
            });
            // Detail dialogs are read-only until an explicit action is selected; no open dialog is replaced.
            page.querySelectorAll('[id^="viewOrderModal"], [id^="viewRewardsModal"]').forEach(modal => {
                document.getElementById(modal.id)?.remove(); document.body.append(modal);
            });
            if (scroller) scroller.scrollTop = scroll;
            notice?.remove(); notice = null;
        } catch (error) { announce(error.message, true); }
    }
    let customerPending = false, customerRefreshing = false, deferredTicketDraft = false, customerController;
    let customerFailures = 0, customerRetryAt = 0, sessionExpired = false;
    function ticketHasDraft(modal) {
        return Array.from(modal.querySelectorAll('textarea, input[type="file"]')).some(input => input.type === 'file' ? input.files.length : input.value !== '');
    }
    function customerBusy(areas) {
        const active = document.activeElement;
        return document.querySelector('.modal.show, .modal.showing, .modal[aria-modal="true"], #lrtsImageLightbox.is-open') ||
            (active?.matches('input, textarea, select, [contenteditable="true"]') && areas.some(selector => active.closest(selector)));
    }
    async function refreshCustomer() {
        if (!customerPending || customerRefreshing || sessionExpired || document.hidden || Date.now() < customerRetryAt) return;
        const notificationsPage = root.dataset.pulsePage === 'notifications';
        const areas = notificationsPage ? ['[data-customer-notifications]'] : ['#tracker', '[data-promos-pane]', '.lrts-ticket-list'];
        if (customerBusy(areas)) {
            announce('New updates will appear when you finish here.');
            return;
        }
        customerRefreshing = true;
        customerPending = false;
        customerController = new AbortController();
        const timeout = setTimeout(() => customerController.abort(), 12000);
        try {
            const response = await fetch(location.href, {headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'}, cache: 'no-store', signal: customerController.signal});
            if (!response.ok || response.redirected) throw new Error('Unable to load updates.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (document.hidden || customerBusy(areas)) {
                customerPending = true;
                announce('New updates will appear when you finish here.');
                return;
            }
            const scrollSelectors = ['.lrts-content', '.lrts-drawer-body', '.lrts-ticket-list', '.lrts-receipt-tracker-scroll', '.lrts-claim-history-scroll'];
            const windowScroll = [window.scrollX, window.scrollY];
            const scrollPositions = scrollSelectors.map(selector => {
                const node = document.querySelector(selector);
                return node ? [node.scrollTop, node.scrollLeft] : null;
            });
            const selectors = notificationsPage ? ['[data-customer-notifications]'] : ['#tracker', '[data-promos-pane="promotions"]', '[data-promos-pane="claims"]', '[data-promos-pane="history"]', '.lrts-ticket-list'];
            if (selectors.some(selector => !document.querySelector(selector) || !page.querySelector(selector))) throw new Error('Unable to load updates.');
            selectors.forEach(selector => { document.querySelector(selector).innerHTML = page.querySelector(selector).innerHTML; });
            const modalSelectors = '[id^="viewOrderModal"], [id^="viewRewardsModal"], [id^="viewTicketModal"]';
            const incoming = new Map(Array.from(page.querySelectorAll(modalSelectors), modal => [modal.id, modal]));
            deferredTicketDraft = false;
            document.querySelectorAll(modalSelectors).forEach(modal => {
                if (modal.id.startsWith('viewTicketModal') && ticketHasDraft(modal)) { incoming.delete(modal.id); deferredTicketDraft = true; return; }
                window.bootstrap?.Modal.getInstance(modal)?.dispose();
                modal.remove();
            });
            incoming.forEach(modal => document.body.append(modal));
            const promotionData = page.getElementById('rts-promotion-data');
            if (promotionData) window.rtsPromotions = JSON.parse(promotionData.textContent);
            scrollSelectors.forEach((selector, index) => {
                const node = document.querySelector(selector), position = scrollPositions[index];
                if (node && position) { node.scrollTop = position[0]; node.scrollLeft = position[1]; }
            });
            if (window.scrollX !== windowScroll[0] || window.scrollY !== windowScroll[1]) window.scrollTo(...windowScroll);
            if (deferredTicketDraft) announce('Ticket details will update after your draft is cleared.');
            else { notice?.remove(); notice = null; }
            customerFailures = 0; customerRetryAt = 0;
        } catch (error) {
            customerPending = true;
            if (!document.hidden && !sessionExpired) {
                customerFailures++;
                customerRetryAt = Date.now() + Math.min(120000, 10000 * Math.pow(2, customerFailures - 1));
                announce('Unable to load updates. Retrying shortly.');
            }
        } finally {
            clearTimeout(timeout);
            customerController = null;
            customerRefreshing = false;
        }
    }
    class Poller {
        constructor(url, interval, consume, enabled = () => true) {
            Object.assign(this, {url, interval, consume, enabled, etag: null, failures: 0, stopped: false});
            pollers.push(this); this.schedule(0);
        }
        schedule(delay) {
            clearTimeout(this.timer);
            if (!this.stopped && !document.hidden) this.timer = setTimeout(() => this.run(), delay);
        }
        async run() {
            if (this.busy || this.stopped || document.hidden) return;
            if (Date.now() - lastInput > 900000 || !this.enabled()) { this.schedule(10000); return; }
            this.busy = true; this.controller = new AbortController();
            const timeout = setTimeout(() => this.controller.abort(), 12000);
            try {
                const url = typeof this.url === 'function' ? this.url() : this.url;
                if (this.previousUrl !== url) this.etag = null;
                this.previousUrl = url;
                const headers = {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'};
                if (this.etag) headers['If-None-Match'] = this.etag;
                const response = await fetch(url, {headers, signal: this.controller.signal, cache: 'no-store'});
                if ([401, 419].includes(response.status) || response.redirected) {
                    sessionExpired = true;
                    customerController?.abort();
                    pollers.forEach(p => { p.stopped = true; clearTimeout(p.timer); });
                    announce('Your session expired. Sign in again to receive updates.'); return;
                }
                if (!response.ok && response.status !== 304) throw new Error('Update request failed');
                if (response.status !== 304) {
                    this.etag = response.headers.get('ETag'); await this.consume(await response.json());
                }
                this.failures = 0;
            } catch (error) {
                this.etag = null;
                if (!document.hidden) this.failures++;
                // Keep existing data visible; reconnect using bounded backoff.
            } finally {
                clearTimeout(timeout); this.busy = false;
                if (!this.stopped && root.dataset.pulseRole === 'customer' && this.url === root.dataset.pulseUrl && customerPending) refreshCustomer();
                const base = typeof this.interval === 'function' ? this.interval() : this.interval;
                this.schedule(Math.min(120000, this.failures ? 20000 * Math.pow(2, this.failures - 1) : base) + (this.failures ? Math.random() * 1000 : 0));
            }
        }
    }
    ['pointerdown', 'pointermove', 'keydown', 'touchstart'].forEach(type => document.addEventListener(type, () => {
        const idle = Date.now() - lastInput > 900000; lastInput = Date.now();
        if (idle) pollers.forEach(p => p.schedule(0));
    }, {passive: true}));
    document.addEventListener('visibilitychange', () => pollers.forEach(p => {
        clearTimeout(p.timer);
        if (document.hidden) p.controller?.abort(); else p.schedule(0);
    }));
    document.addEventListener('visibilitychange', () => { if (document.hidden) customerController?.abort(); });
    window.addEventListener('pagehide', () => customerController?.abort());
    window.addEventListener('pagehide', () => pollers.forEach(p => {clearTimeout(p.timer); p.controller?.abort();}));
    window.addEventListener('pageshow', event => { if (event.persisted) pollers.forEach(p => p.schedule(0)); });
    const labels = {pending:'Processing', approved:'Approved', rejected:'Rejected', cancelled:'Cancelled', unclaimed:'Available', claim_requested:'Claim requested', released:'Awaiting confirmation', claimed:'Received', voided:'Voided', expired:'Expired'};
    function status(selector, status) {
        document.querySelectorAll(selector).forEach(node => {
            node.textContent = labels[status] || status;
            node.classList.remove('bg-success','bg-danger','bg-warning','bg-secondary','text-dark');
            node.classList.add(...(['approved','claimed'].includes(status) ? ['bg-success'] : ['rejected','expired','voided'].includes(status) ? ['bg-danger'] : ['bg-warning','text-dark']));
            if (node.dataset.receiptStatus && status !== 'pending') node.closest('tr')?.querySelectorAll('form[action$="/cancel"] button').forEach(button => {button.disabled=true; button.title='This receipt status changed. Load list updates.';});
        });
    }
    let version;
    new Poller(root.dataset.pulseUrl, () => root.dataset.pulseRole === 'admin' ? 20000 : 8000, data => {
        document.querySelectorAll('[data-admin-count]').forEach(node => {
            const count = Number(data[node.dataset.adminCount] || 0);
            node.textContent = node.dataset.adminCount === 'premium-expiry' ? `${count} expiry alerts` : count;
            node.hidden = count < 1;
        });
        document.querySelectorAll('[data-customer-count]').forEach(node => {
            const count = Number(data[node.dataset.customerCount] || 0); node.textContent = count; node.hidden = count < 1;
        });
        if (root.dataset.pulseRole === 'customer') {
            if (version !== data.version) { customerPending = true; refreshCustomer(); }
        } else {
            (Array.isArray(data.receipts) ? data.receipts : []).forEach(row => status(`[data-receipt-status="${row.id}"]`, row.status));
            (Array.isArray(data.rewards) ? data.rewards : []).forEach(row => status(`[data-claim-status="${row.id}"]`, row.claim_status));
            if (version && version !== data.version) announce('New receipt, claim or support updates are available.', true);
        }
        version = data.version;
    });
    const threadPollers = new WeakMap();
    function registerThread(thread) {
        if (threadPollers.has(thread)) return threadPollers.get(thread);
        let after = Number(thread.dataset.after || 0), readAfter = -1;
        const active = () => Boolean(thread.getClientRects().length) && (!thread.closest('.modal') || thread.closest('.modal').classList.contains('show'));
        const poller = new Poller(() => `${thread.dataset.pollThread}?after=${after}`, root.dataset.pulseRole === 'admin' ? 15000 : 5000, async data => {
            const atBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 60;
            data.messages.forEach(message => {
                if (message.id <= after) return;
                thread.querySelector('.lrts-ticket-modal-empty')?.remove();
                const article = document.createElement('div');
                article.className = root.dataset.pulseRole === 'admin' ? (message.is_internal_note ? 'adm-thread-note' : 'adm-thread-msg '+(message.author==='Support'?'is-admin':'is-customer')) : 'lrts-ticket-msg '+(message.author==='Support'?'is-support':'is-me');
                const meta = document.createElement('small'); meta.className = 'text-muted';
                meta.textContent = `${message.author}${message.is_internal_note ? ' · Internal note' : ''} · ${new Date(message.created_at).toLocaleString()}`;
                const body = document.createElement('p'); body.style.whiteSpace = 'pre-wrap'; body.textContent = message.body;
                article.append(meta, body);
                if (message.attachment_url) { const link = document.createElement('a'); link.href = message.attachment_url; link.textContent = 'View attachment'; link.target = '_blank'; link.rel = 'noopener'; article.append(link); }
                thread.append(article); after = Math.max(after, message.id); thread.dataset.after = String(after);
            });
            if (atBottom) thread.scrollTop = thread.scrollHeight;
            const modal = thread.closest('.modal');
            if (modal && root.dataset.pulseRole === 'customer') {
                const locked = modal.querySelector('.lrts-ticket-modal-locked');
                if (locked && !['resolved', 'closed'].includes(data.status)) {
                    customerPending = true;
                    announce('This ticket was reopened. Close the dialog to load its reply form.');
                }
                const badge = modal.querySelector('.lrts-ticket-modal-meta-value .lrts-badge');
                if (badge && !(locked && !['resolved', 'closed'].includes(data.status))) {
                    badge.textContent = data.status.replaceAll('_', ' ').toUpperCase();
                    badge.classList.remove('lrts-badge-open', 'lrts-badge-pending', 'lrts-badge-progress', 'lrts-badge-resolved', 'lrts-badge-closed');
                    badge.classList.add('lrts-badge-' + (data.status === 'in_progress' ? 'progress' : data.status));
                }
                modal.querySelectorAll('.lrts-ticket-modal-composer form [type="submit"]').forEach(button => {
                    button.disabled = ['resolved', 'closed'].includes(data.status);
                    button.title = button.disabled ? 'This ticket no longer accepts replies.' : '';
                });
                const composer = modal.querySelector('.lrts-ticket-modal-composer');
                let message = modal.querySelector('[data-ticket-closed-message]');
                if (composer && ['resolved', 'closed'].includes(data.status)) {
                    if (!message) { message = document.createElement('p'); message.dataset.ticketClosedMessage = ''; message.className = 'small text-danger'; message.setAttribute('role', 'status'); composer.prepend(message); }
                    message.textContent = 'This ticket no longer accepts replies. Your draft is preserved.';
                } else message?.remove();
            } else if (root.dataset.pulseRole === 'admin') {
                const detail = thread.parentElement;
                const archived = Boolean(data.archived);
                const closed = data.status === 'closed';
                detail.querySelectorAll('[data-admin-ticket-status]').forEach(badge => {
                    badge.textContent = data.status.replaceAll('_', ' ').toUpperCase();
                    badge.className = 'lrts-badge lrts-badge-' + (data.status === 'in_progress' ? 'progress' : data.status);
                });
                detail.querySelectorAll('[data-admin-ticket-reply]').forEach(button => { button.disabled = archived || closed; });
                detail.querySelectorAll('[data-admin-ticket-composer]').forEach(composer => {
                    composer.hidden = archived || closed;
                    composer.querySelectorAll('form [type="submit"]').forEach(button => { button.disabled = archived; });
                });
                detail.querySelectorAll('[data-admin-ticket-closed], [data-admin-ticket-reopen], [data-admin-ticket-archive]').forEach(node => { node.hidden = archived || !closed; });
                detail.querySelectorAll('[data-admin-ticket-archived], [data-admin-ticket-restore]').forEach(node => { node.hidden = !archived; });
            }
            if (thread.dataset.readUrl && after > readAfter) {
                const read = await fetch(thread.dataset.readUrl, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':window.rtsCustomerConfig.csrf},body:JSON.stringify({after})});
                if (!read.ok || read.redirected) throw new Error('Unable to mark replies read');
                readAfter = after;
            }
        }, active);
        threadPollers.set(thread, poller);
        return poller;
    }
    if (root.dataset.pulseRole === 'admin') document.querySelectorAll('[data-poll-thread]').forEach(registerThread);
    else {
        document.addEventListener('shown.bs.modal', event => {
            const thread = event.target.querySelector('[data-poll-thread]');
            if (thread) registerThread(thread).schedule(0);
        });
        document.addEventListener('hidden.bs.modal', event => {
            const thread = event.target.querySelector('[data-poll-thread]');
            const poller = thread && threadPollers.get(thread);
            if (poller) { poller.stopped = true; clearTimeout(poller.timer); poller.controller?.abort(); threadPollers.delete(thread); pollers.splice(pollers.indexOf(poller), 1); }
            if (customerPending) refreshCustomer();
        });
        document.addEventListener('focusout', () => { if (customerPending) setTimeout(refreshCustomer, 0); });
        const retryDraft = () => {
            if (deferredTicketDraft && !Array.from(document.querySelectorAll('[id^="viewTicketModal"]')).some(ticketHasDraft)) {
                deferredTicketDraft = false; customerPending = true; refreshCustomer();
            }
        };
        document.addEventListener('input', retryDraft);
        document.addEventListener('change', retryDraft);
    }
})();
