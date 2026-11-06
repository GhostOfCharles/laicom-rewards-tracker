const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {pathToFileURL} = require('node:url');

// A real browser with local, deterministic responses; no application database is used.
const browser = process.env.RTS_BROWSER_EXECUTABLE || 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'rts-customer-poll-'));
const source = fs.readFileSync('public/js/rts-poll.js', 'utf8');
const html = `<!doctype html><body data-pulse-url="/pulse" data-pulse-role="customer">
<div data-customer-count="unread_notifications"></div><div id="tracker">old</div>
<div data-promos-pane="promotions">old</div><div data-promos-pane="claims">old</div><div data-promos-pane="history">old</div>
<div class="lrts-drawer-body"><div class="lrts-ticket-list">old</div></div>
<div class="modal" id="viewTicketModal1"><div class="lrts-ticket-modal-meta-value"><span class="lrts-badge lrts-badge-open">OPEN</span></div><div data-poll-thread="/thread" data-read-url="/read" data-after="0"><div class="lrts-ticket-modal-empty">empty</div></div><div class="lrts-ticket-modal-composer"><form><textarea></textarea><button type="submit">Send</button></form></div></div>
<script type="application/json" id="rts-promotion-data">[]</script>
<script>
window.rtsCustomerConfig = {csrf:'token'}; window.bootstrap = {Modal:{getInstance(){return null}}};
let revision = 1;
window.fetch = async url => {
 if (String(url).startsWith('/pulse')) return new Response(JSON.stringify({version:String(revision), unread_notifications:revision}), {headers:{'Content-Type':'application/json'}});
 if (String(url).startsWith('/thread')) return new Response(JSON.stringify({status:'closed', messages:[{id:7,author:'Support',body:'New reply',created_at:'2026-01-01T00:00:00Z'}]}), {headers:{'Content-Type':'application/json'}});
 if (String(url).startsWith('/read')) return new Response('{}', {headers:{'Content-Type':'application/json'}});
 if (document.body.dataset.pulsePage === 'notifications') return new Response('<div data-customer-notifications>notification '+revision+'</div>', {headers:{'Content-Type':'text/html'}});
 return new Response('<div id="tracker">order '+revision+'</div><div data-promos-pane="promotions">promotions '+revision+'</div><div data-promos-pane="claims">claims '+revision+'</div><div data-promos-pane="history">history '+revision+'</div><div class="lrts-ticket-list">tickets '+revision+'</div><div class="modal" id="viewTicketModal1"><div class="lrts-ticket-modal-meta-value"><span class="lrts-badge lrts-badge-open">OPEN</span></div><div data-poll-thread="/thread" data-read-url="/read" data-after="0"></div><div class="lrts-ticket-modal-composer"><form><textarea></textarea><button type="submit">Send</button></form></div></div><script type="application/json" id="rts-promotion-data">[]<\\/script>', {headers:{'Content-Type':'text/html'}});
};
<\/script><script>${source}<\/script><script>
const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
(async () => {
 try {
  await wait(100);
  if (document.querySelector('#tracker').textContent !== 'order 1') throw Error('initial refresh failed');
  let modal = document.querySelector('#viewTicketModal1');
  modal.classList.add('show'); modal.querySelector('textarea').value = 'Keep this draft';
  modal.dispatchEvent(new Event('shown.bs.modal',{bubbles:true}));
  await wait(100);
  if (!modal.textContent.includes('New reply') || !modal.querySelector('button[type=submit]').disabled) throw Error('reply or closed status missing');
  revision = 2; document.dispatchEvent(new Event('visibilitychange')); await wait(100);
  if (document.querySelector('#tracker').textContent !== 'order 1' || modal.querySelector('textarea').value !== 'Keep this draft') throw Error('open modal was disturbed');
  if ((modal.textContent.match(/New reply/g) || []).length !== 1) throw Error('reply duplicated');
  modal.classList.remove('show'); modal.dispatchEvent(new Event('hidden.bs.modal',{bubbles:true})); await wait(100);
  if (document.querySelector('#tracker').textContent !== 'order 2' || document.querySelector('[data-promos-pane=claims]').textContent !== 'claims 2') throw Error('sections did not update');
  if (document.querySelector('#viewTicketModal1 textarea').value !== 'Keep this draft') throw Error('closed draft was lost');
  modal.classList.add('show'); modal.dispatchEvent(new Event('shown.bs.modal',{bubbles:true})); await wait(100);
  if ((modal.textContent.match(/New reply/g) || []).length !== 1) throw Error('reply duplicated on reopening');
  modal.classList.remove('show'); modal.dispatchEvent(new Event('hidden.bs.modal',{bubbles:true}));
  document.querySelector('#viewTicketModal1 textarea').value = '';
  document.querySelector('#viewTicketModal1 textarea').dispatchEvent(new Event('input',{bubbles:true})); await wait(100);
  if (document.querySelector('#viewTicketModal1').textContent.includes('New reply')) throw Error('deferred modal did not refresh');
  document.body.dataset.pulsePage = 'notifications';
  const notifications = document.createElement('div'); notifications.dataset.customerNotifications = ''; notifications.textContent = 'old'; document.body.append(notifications);
  revision = 3; document.dispatchEvent(new Event('visibilitychange')); await wait(100);
  if (notifications.textContent !== 'notification 3') throw Error('notification list did not update');
  document.body.dataset.testResult = 'PASS';
 } catch (error) { document.body.dataset.testResult = 'FAIL: ' + error.message; }
})();
<\/script></body>`;
const fixture = path.join(temp, 'fixture.html');
try {
    fs.writeFileSync(fixture, html);
    const output = execFileSync(browser, ['--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--user-data-dir='+path.join(temp, 'profile'), '--virtual-time-budget=3000', '--dump-dom', pathToFileURL(fixture).href], {encoding:'utf8', windowsHide:true, stdio:['ignore','pipe','ignore'], timeout:30000});
    assert.match(output, /data-test-result="PASS"/, output.match(/data-test-result="[^"]*"/)?.[0] || 'Customer polling browser check did not finish');
    console.log('Customer polling browser smoke passed: automatic sections and notifications, immediate reply, deduplication, closed status and draft preservation.');
} finally {
    if (path.dirname(temp) === os.tmpdir() && path.basename(temp).startsWith('rts-customer-poll-')) fs.rmSync(temp, {recursive:true, force:true, maxRetries:3, retryDelay:200});
}
