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
            notice.style.zIndex = '1040';
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
                    pollers.forEach(p => { p.stopped = true; clearTimeout(p.timer); });
                    announce('Your session expired. Sign in again to receive updates.'); return;
                }
                if (!response.ok && response.status !== 304) throw new Error('Update request failed');
                if (response.status !== 304) {
                    this.etag = response.headers.get('ETag'); await this.consume(await response.json());
                }
                this.failures = 0;
            } catch (error) {
                if (!document.hidden) this.failures++;
                // Keep existing data visible; reconnect using bounded backoff.
            } finally {
                clearTimeout(timeout); this.busy = false;
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
    window.addEventListener('pagehide', () => pollers.forEach(p => {clearTimeout(p.timer); p.controller?.abort();}));
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
    new Poller(root.dataset.pulseUrl, () => root.dataset.pulseRole === 'admin' ? 20000 : document.querySelector('#tracker.active, #promos.active') ? 30000 : 60000, data => {
        document.querySelectorAll('[data-admin-count]').forEach(node => {
            const count = Number(data[node.dataset.adminCount] || 0);
            node.textContent = node.dataset.adminCount === 'premium-expiry' ? `${count} expiry alerts` : count;
            node.hidden = count < 1;
        });
        document.querySelectorAll('[data-customer-count]').forEach(node => {
            const count = Number(data[node.dataset.customerCount] || 0); node.textContent = count; node.hidden = count < 1;
        });
        (data.receipts || []).forEach(row => status(`[data-receipt-status="${row.id}"]`, row.status));
        (data.rewards || []).forEach(row => status(`[data-claim-status="${row.id}"]`, row.claim_status));
        if (version && version !== data.version) announce('New receipt, claim or support updates are available.', true);
        version = data.version;
    });
    document.querySelectorAll('[data-poll-thread]').forEach(thread => {
        let after = Number(thread.dataset.after || 0), readAfter = -1;
        const active = () => Boolean(thread.getClientRects().length) && (!thread.closest('.modal') || thread.closest('.modal').classList.contains('show'));
        new Poller(() => `${thread.dataset.pollThread}?after=${after}`, root.dataset.pulseRole === 'admin' ? 15000 : 10000, async data => {
            const atBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 60;
            data.messages.forEach(message => {
                const article = document.createElement('div');
                article.className = root.dataset.pulseRole === 'admin' ? (message.is_internal_note ? 'adm-thread-note' : 'adm-thread-msg '+(message.author==='Support'?'is-admin':'is-customer')) : 'lrts-ticket-msg '+(message.author==='Support'?'is-support':'is-me');
                const meta = document.createElement('small'); meta.className = 'text-muted';
                meta.textContent = `${message.author}${message.is_internal_note ? ' · Internal note' : ''} · ${new Date(message.created_at).toLocaleString()}`;
                const body = document.createElement('p'); body.style.whiteSpace = 'pre-wrap'; body.textContent = message.body;
                article.append(meta, body);
                if (message.attachment_url) { const link = document.createElement('a'); link.href = message.attachment_url; link.textContent = 'View attachment'; link.target = '_blank'; link.rel = 'noopener'; article.append(link); }
                thread.append(article); after = Math.max(after, message.id);
            });
            if (atBottom) thread.scrollTop = thread.scrollHeight;
            const modal = thread.closest('.modal');
            if (['resolved','closed'].includes(data.status)) {
                (modal || thread.parentElement).querySelectorAll('form [type="submit"]').forEach(button => button.disabled = true);
            }
            if (thread.dataset.readUrl && after > readAfter) { readAfter=after; await fetch(thread.dataset.readUrl, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':window.rtsCustomerConfig.csrf},body:JSON.stringify({after})}).catch(() => { readAfter=-1; }); }
        }, active);
    });
})();
