import assert from 'node:assert/strict';
import {readFile, writeFile, mkdir} from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import {execFileSync} from 'node:child_process';

// Run only against an isolated local QA server and dedicated headless browser.
const base = process.env.RTS_BROWSER_URL || 'http://127.0.0.1:8900';
assert.ok(['127.0.0.1', 'localhost'].includes(new URL(base).hostname));
const config = JSON.parse(await readFile(process.env.RTS_BROWSER_CONFIG || path.join(os.tmpdir(), 'rts-browser-config.json'), 'utf8'));
const target = await (await fetch('http://127.0.0.1:9331/json/new?about:blank', {method:'PUT'})).json();
const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(resolve => socket.addEventListener('open', resolve, {once:true}));
let sequence = 0;
const pending = new Map(), errors = [];
socket.addEventListener('message', event => {
    const data = JSON.parse(event.data);
    if (data.id) { const handler=pending.get(data.id); pending.delete(data.id); data.error ? handler.reject(Error(data.error.message)) : handler.resolve(data.result); }
    if (data.method === 'Runtime.exceptionThrown') errors.push(data.params.exceptionDetails.exception?.description || data.params.exceptionDetails.text);
});
function send(method, params={}) { const id=++sequence; return new Promise((resolve,reject)=>{pending.set(id,{resolve,reject}); socket.send(JSON.stringify({id,method,params}));}); }
async function evaluate(expression) {
    const result=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});
    if(result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || 'Browser evaluation failed');
    return result.result.value;
}
const sleep=ms=>new Promise(resolve=>setTimeout(resolve,ms));
async function waitFor(expression) { for(let i=0;i<200;i++){if(await evaluate(expression)) return; await sleep(100);} throw Error('Browser condition timed out'); }
async function navigate(url) { await send('Page.navigate',{url:base+url}); await waitFor("document.readyState==='complete'"); }
async function login(role) {
    await navigate(role==='admin'?'/admin/login':'/login');
    await evaluate(`document.querySelector('[name=email]').value=${JSON.stringify(config[role])};document.querySelector('[name=password]').value=${JSON.stringify(config.password)};document.querySelector('form').requestSubmit();`);
    await waitFor(`location.pathname===${JSON.stringify(role==='admin'?'/admin/dashboard':'/dashboard')} && document.readyState==='complete'`);
}
await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
const output=path.join(os.tmpdir(),'rts-production-browser'); await mkdir(output,{recursive:true});
const results=[];
async function inspect(name,width,height) {
    await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false}); await sleep(250);
    const data=await evaluate(`({width:innerWidth,scrollWidth:document.documentElement.scrollWidth,logoLoaded:!!document.querySelector('img[src*="rts-logo"]')?.naturalWidth,navHeight:document.querySelector('nav.navbar')?.getBoundingClientRect().height})`);
    assert.ok(data.scrollWidth<=width+1,`${name}: horizontal overflow ${data.scrollWidth}/${width}`);
    const shot=await send('Page.captureScreenshot',{format:'png'}); await writeFile(path.join(output,name+'.png'),Buffer.from(shot.data,'base64'));
    results.push({name,...data});
}
try {
    await login('customer');
    await inspect('customer-desktop',1440,900);
    await evaluate("document.querySelector('.lrts-product-combobox-toggle').click()");
    const dropdown=await evaluate("({height:document.querySelector('#product_options').getBoundingClientRect().height, overflow:getComputedStyle(document.querySelector('#product_options')).overflowY,cardOverflow:getComputedStyle(document.querySelector('#order .card')).overflow})");
    assert.ok(dropdown.height<=272); assert.equal(dropdown.overflow,'auto'); assert.equal(dropdown.cardOverflow,'visible');
    await evaluate("document.querySelector('.lrts-product-combobox-option').click();document.getElementById('add_item_btn').click()");
    assert.equal(await evaluate("document.querySelectorAll('#item_listbox .lrts-order-item').length"),1);
    assert.equal(await evaluate("!!document.getElementById('empty_msg')"),false);
    await evaluate("document.getElementById('process_receipt_btn').click()");
    await waitFor("!!document.querySelector('#processReceiptModal.show')");
    const order='QA-BROWSER-'+Date.now();
    await evaluate(`(async()=>{document.querySelector('[name=salesman_order_number]').value=${JSON.stringify(order)};const canvas=document.createElement('canvas');canvas.width=100;canvas.height=100;canvas.getContext('2d').fillRect(0,0,100,100);const blob=await new Promise(r=>canvas.toBlob(r,'image/jpeg'));const transfer=new DataTransfer();transfer.items.add(new File([blob],'qa-receipt.jpg',{type:'image/jpeg'}));document.getElementById('receiptSlipInput').files=transfer.files;document.getElementById('submitOrderButton').click();})()`);
    await waitFor("document.readyState==='complete' && !document.querySelector('#processReceiptModal.show') && document.body.innerText.includes('Order submitted successfully')");
    await evaluate(`bootstrap.Tab.getOrCreateInstance(document.getElementById('help-tab')).show();bootstrap.Modal.getOrCreateInstance(document.getElementById('viewTicketModal${config.ticket_id}')).show()`);
    await evaluate(`const draft=document.querySelector('#viewTicketModal${config.ticket_id} textarea[name=body]');draft.value='Draft reply must stay here.';draft.dispatchEvent(new Event('input',{bubbles:true}));`);
    const liveBody=execFileSync(process.env.PHP_BINARY || 'php',['tests/Support/browser-pulse-worker.php'],{encoding:'utf8'}).trim();
    await waitFor(`document.querySelector('[data-poll-thread]').innerText.includes(${JSON.stringify(liveBody)})`);
    assert.equal(await evaluate("document.querySelector('[data-poll-thread]').innerText.includes('Never expose this live internal note')"),false);
    assert.equal(await evaluate(`document.querySelector('#viewTicketModal${config.ticket_id} textarea[name=body]').value`),'Draft reply must stay here.');
    await evaluate("document.dispatchEvent(new Event('visibilitychange'))");
    await waitFor("!document.querySelector('[data-customer-count=unread_notifications]').hidden");
    await evaluate(`bootstrap.Modal.getInstance(document.getElementById('viewTicketModal${config.ticket_id}')).hide();bootstrap.Tab.getOrCreateInstance(document.getElementById('order-tab')).show()`);
    await waitFor("!document.querySelector('.modal.show')");
    await evaluate("document.querySelector('#customerNotificationsDropdown button').click()");
    await waitFor(`document.querySelector('.lrts-notification-list').innerText.includes(${JSON.stringify(liveBody)})`);
    await evaluate("document.querySelector('#customerNotificationsDropdown button').click()");
    await inspect('customer-mobile',390,844);
    await inspect('customer-small-mobile',320,720);
    await evaluate("document.querySelector('form[action$=\"/logout\"]').requestSubmit()");
    await waitFor("location.pathname==='/' && document.readyState==='complete'");
    await send('Emulation.setDeviceMetricsOverride',{width:1440,height:900,deviceScaleFactor:1,mobile:false});
    await login('admin');
    await navigate('/admin/receipts');
    await evaluate(`document.querySelector('[data-receipt-panel="${base}/admin/receipts/${config.receipt_id}/panel"]').click()`);
    await waitFor("document.getElementById('receiptPanelContent').innerText.includes('PURCHASED PRODUCTS')");
    await inspect('admin-receipt-panel',1440,900);
    assert.equal(await evaluate("document.getElementById('receiptPanelContent').innerText.includes('QA Store')"),true);
    await navigate('/admin/tickets?ticket='+config.ticket_id);
    assert.equal(await evaluate("!!document.querySelector('[data-poll-thread]')"),true);
    await inspect('admin-mobile',390,844);
    await evaluate("document.getElementById('adminMenuToggle').click()");
    assert.equal(await evaluate("document.getElementById('adminSidebar').classList.contains('is-open')"),true);
    await evaluate("document.getElementById('adminSidebarBackdrop').click()");
    assert.equal(await evaluate("document.getElementById('adminSidebar').classList.contains('is-open')"),false);
    assert.equal(errors.length,0,'Browser JavaScript errors: '+errors.join('\n'));
    await writeFile(path.join(output,'results.json'),JSON.stringify({results,dropdown,errors},null,2));
    console.log(JSON.stringify({passed:true,liveSupportAndNotifications:true,checks:results,dropdown,errors:errors.length,artifacts:output}));
} finally {
    await send('Page.close').catch(()=>{}); socket.close();
}
