const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');

// Deterministic browser API harness: no network, framework, or new dependency.
async function main() {
    let now = 0, id = 0, calls = 0, mode = 'ok', active = 0, maxActive = 0, release;
    const timers = new Map(), events = new Map();
    const element = () => ({style: {}, dataset: {}, append() {}, replaceChildren() {}, setAttribute() {}, addEventListener() {}, remove() {}});
    const document = {
        body: {...element(), dataset: {pulseUrl: '/admin/nav-counts', pulseRole: 'admin'}}, hidden: false,
        addEventListener(name, fn) { if (!events.has(name)) events.set(name, []); events.get(name).push(fn); },
        querySelectorAll() { return []; }, querySelector() { return null; },
        createElement: element, createTextNode: text => ({text}),
    };
    const context = vm.createContext({document, window: {addEventListener() {}},
        Date: class extends Date { static now() { return now; } }, Math: Object.assign(Object.create(Math), {random: () => 0}), AbortController,
        setTimeout(fn, delay) { const key = ++id; timers.set(key, {fn, delay}); return key; }, clearTimeout(key) { timers.delete(key); },
        async fetch() {
            calls++; active++; maxActive = Math.max(maxActive, active);
            try {
                if (mode === 'fail') throw Error('Offline');
                if (mode === 'pending') await new Promise(resolve => { release = resolve; });
                return {ok: mode !== 'expired', status: mode === 'expired' ? 401 : 200, redirected: false,
                    headers: {get() { return '"same"'; }}, async json() { return {version: 'same'}; }};
            } finally { active--; }
        },
    });
    vm.runInContext(fs.readFileSync('public/js/rts-poll.js', 'utf8'), context);
    const fire = delay => {
        const entry = [...timers].find(([, timer]) => timer.delay === delay);
        assert.ok(entry, `Expected timer ${delay}`); timers.delete(entry[0]); return entry[1].fn();
    };
    const emit = name => (events.get(name) || []).forEach(fn => fn({}));
    await fire(0); assert.equal(calls, 1);
    document.hidden = true; emit('visibilitychange'); assert.equal(timers.size, 0);
    document.hidden = false; emit('visibilitychange'); await fire(0); assert.equal(calls, 2);
    mode = 'fail'; await fire(20000); assert.ok([...timers.values()].some(t => t.delay === 20000));
    await fire(20000); assert.ok([...timers.values()].some(t => t.delay === 40000));
    await fire(40000); assert.ok([...timers.values()].some(t => t.delay === 80000));
    mode = 'ok'; await fire(80000); assert.ok([...timers.values()].some(t => t.delay === 20000));
    now += 16 * 60 * 1000; const beforeIdle = calls; await fire(20000); assert.equal(calls, beforeIdle);
    emit('pointerdown'); await fire(0); assert.equal(calls, beforeIdle + 1);
    mode = 'pending'; const pending = fire(20000);
    emit('visibilitychange'); await fire(0); assert.equal(maxActive, 1);
    release(); await pending;
    mode = 'expired'; await fire(20000); assert.equal(timers.size, 0);
    console.log('Polling smoke passed: hidden/idle pause, immediate resume, backoff/reset, no overlap, expired-session stop.');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
