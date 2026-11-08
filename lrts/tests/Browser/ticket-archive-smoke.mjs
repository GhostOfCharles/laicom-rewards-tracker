import assert from 'node:assert/strict';
import {spawn, execFileSync} from 'node:child_process';
import {mkdtemp, mkdir, writeFile} from 'node:fs/promises';
import {randomBytes} from 'node:crypto';
import net from 'node:net';
import os from 'node:os';
import path from 'node:path';

// Self-contained local QA: fresh SQLite, uploads/storage and headless browser in OS temp.
const directory = await mkdtemp(path.join(os.tmpdir(), 'rts-archive-qa-'));
for (const name of ['storage/framework/views', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/logs', 'storage/app/private']) {
    await mkdir(path.join(directory, name), {recursive: true});
}
await writeFile(path.join(directory, 'audit.sqlite'), '');
async function port() {
    const server = net.createServer();
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const value = server.address().port;
    await new Promise(resolve => server.close(resolve));
    return value;
}
const httpPort = await port(), debugPort = await port();
const base = `http://127.0.0.1:${httpPort}`;
const env = {...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: base,
    APP_KEY: 'base64:' + randomBytes(32).toString('base64'), DB_CONNECTION: 'sqlite',
    DB_DATABASE: path.join(directory, 'audit.sqlite'), DB_URL: '', SESSION_DRIVER: 'database',
    SESSION_SECURE_COOKIE: 'false', CACHE_STORE: 'database', MAIL_MAILER: 'array',
    LARAVEL_STORAGE_PATH: path.join(directory, 'storage'), RTS_ARCHIVE_QA_DIR: directory};
const php = process.env.PHP_BINARY || 'php';
const config = JSON.parse(execFileSync(php, ['tests/Support/archive-browser-fixture.php'], {env, windowsHide: true, encoding: 'utf8'}));
const router = path.join(directory, 'router.php');
const publicPath = path.resolve('public').replaceAll('\\', '/');
await writeFile(router, `<?php $file = '${publicPath}/'.ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); if (is_file($file)) return false; require '${publicPath}/index.php';`);
const server = spawn(php, ['-S', `127.0.0.1:${httpPort}`, '-t', 'public', router], {env, windowsHide: true, stdio: 'ignore'});
const edge = spawn(process.env.RTS_BROWSER_EXECUTABLE || 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    ['--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${debugPort}`, `--user-data-dir=${path.join(directory, 'profile')}`, 'about:blank'],
    {windowsHide: true, stdio: 'ignore'});
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(action, label) {
    for (let i = 0; i < 150; i++) { try { const result = await action(); if (result) return result; } catch {} await pause(100); }
    throw Error('Timed out: ' + label);
}
let socket;
const errors = [], screenshots = [];
try {
    await until(async () => (await fetch(base + '/up')).ok, 'local test server');
    const target = await until(async () => (await fetch(`http://127.0.0.1:${debugPort}/json/new?about:blank`, {method: 'PUT'})).json(), 'headless browser');
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise(resolve => socket.addEventListener('open', resolve, {once: true}));
    let sequence = 0;
    const pending = new Map();
    socket.addEventListener('message', event => {
        const data = JSON.parse(event.data);
        if (data.id) {
            const handler = pending.get(data.id); pending.delete(data.id);
            data.error ? handler.reject(Error(data.error.message)) : handler.resolve(data.result);
        }
        if (data.method === 'Runtime.exceptionThrown') errors.push(data.params.exceptionDetails.exception?.description || data.params.exceptionDetails.text);
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence; pending.set(id, {resolve, reject}); socket.send(JSON.stringify({id, method, params}));
    });
    async function evaluate(expression) {
        const result = await send('Runtime.evaluate', {expression, returnByValue: true, awaitPromise: true});
        if (result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
        return result.result.value;
    }
    const wait = expression => until(() => evaluate(expression), expression);
    async function navigate(url) {
        await send('Page.navigate', {url: base + url});
        await wait(`location.pathname === ${JSON.stringify(new URL(base + url).pathname)} && document.readyState === 'complete'`);
    }
    async function login(role) {
        await navigate(role === 'admin' ? '/admin/login' : '/login');
        await evaluate(`document.querySelector('[name=email]').value=${JSON.stringify(config[role])}; document.querySelector('[name=password]').value=${JSON.stringify(config.password)}; document.querySelector('form').requestSubmit()`);
        await wait(`location.pathname === '${role === 'admin' ? '/admin/dashboard' : '/dashboard'}' && document.readyState === 'complete'`);
    }
    async function screenshot(name, width) {
        await send('Emulation.setDeviceMetricsOverride', {width, height: 900, deviceScaleFactor: 1, mobile: width < 600});
        await pause(100);
        assert.ok(await evaluate(`document.documentElement.scrollWidth <= innerWidth + 1`), name + ': horizontal overflow');
        const image = await send('Page.captureScreenshot', {format: 'png'});
        await writeFile(path.join(directory, name + '.png'), Buffer.from(image.data, 'base64'));
        screenshots.push(name);
    }
    await send('Page.enable'); await send('Runtime.enable');
    await login('admin');
    const ticketUrl = '/admin/tickets?ticket=' + config.ticket_id;
    await navigate(ticketUrl);
    assert.ok(await evaluate(`!document.querySelector('[data-admin-ticket-archive]').hidden`));
    await screenshot('closed-ticket-desktop', 1440);
    await screenshot('closed-ticket-mobile', 390);
    await screenshot('closed-ticket-small-mobile', 320);
    await evaluate(`document.querySelector('[data-admin-ticket-archive]').click()`);
    await wait(`!!document.querySelector('#archiveTicket${config.ticket_id}.show')`);
    await screenshot('archive-confirmation-mobile', 390);
    await evaluate(`document.querySelector('#archiveTicket${config.ticket_id} button[type=submit]').click()`);
    await wait(`location.search === '?status=closed' && document.readyState === 'complete'`);
    assert.equal(await evaluate(`document.querySelectorAll('.adm-ticket-row').length`), 0);
    await navigate('/admin/tickets?status=archived&ticket=' + config.ticket_id);
    assert.ok(await evaluate(`!document.querySelector('[data-admin-ticket-restore]').hidden`));
    await screenshot('archived-ticket-desktop', 1440);
    await evaluate(`document.querySelector('[data-admin-ticket-restore]').requestSubmit()`);
    await wait(`location.search.includes('status=closed') && document.readyState === 'complete' && !!document.querySelector('[data-admin-ticket-reopen]')`);
    await evaluate(`document.querySelector('[data-admin-ticket-reopen]').requestSubmit()`);
    await wait(`document.readyState === 'complete' && !document.querySelector('[data-admin-ticket-composer]')?.hidden`);

    // Feed status snapshots through the real poller; no navigation may discard the draft.
    await evaluate(`window.archivePollState = 'resolved'; window.archivePollArchived = false; window.archiveRealFetch = fetch; window.fetch = async (url, options) => String(url).includes('/replies?') ? new Response(JSON.stringify({status:window.archivePollState, archived:window.archivePollArchived, messages:[]}), {headers:{'Content-Type':'application/json'}}) : window.archiveRealFetch(url, options); document.querySelector('#adminReplyForm textarea').value='Preserve this administrator draft'; document.dispatchEvent(new Event('visibilitychange'));`);
    await wait(`document.querySelector('[data-admin-ticket-status]').textContent === 'RESOLVED'`);
    assert.ok(await evaluate(`Array.from(document.querySelectorAll('.adm-composer-actions form button[type=submit]')).every(button => !button.disabled)`));
    await evaluate(`window.archivePollState='closed'; window.archivePollArchived=true; document.dispatchEvent(new Event('visibilitychange'))`);
    await wait(`!document.querySelector('[data-admin-ticket-restore]').hidden`);
    assert.equal(await evaluate(`document.querySelector('#adminReplyForm textarea').value`), 'Preserve this administrator draft');
    await evaluate(`window.archivePollState='open'; window.archivePollArchived=false; document.dispatchEvent(new Event('visibilitychange'))`);
    await wait(`!document.querySelector('[data-admin-ticket-composer]').hidden`);
    assert.ok(await evaluate(`!document.querySelector('[data-admin-ticket-reply]').disabled`));
    assert.equal(await evaluate(`document.querySelector('#adminReplyForm textarea').value`), 'Preserve this administrator draft');
    await evaluate(`window.fetch=window.archiveRealFetch; document.querySelector('form[action$="/logout"]').requestSubmit()`);
    await wait(`location.pathname === '/' && document.readyState === 'complete'`);
    await login('customer');
    assert.ok(await evaluate(`document.body.textContent.includes('Your previous inquiry has been completed.')`));
    assert.equal(errors.length, 0, errors.join('\n'));
    console.log(JSON.stringify({passed: true, archiveRestoreReopen: true, adminPollingAndDraft: true, screenshots, artifacts: directory}));
} finally {
    socket?.close(); edge.kill(); server.kill();
}
