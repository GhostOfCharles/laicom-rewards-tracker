const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawn} = require('node:child_process');
const {pathToFileURL} = require('node:url');
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'rts-submit-'));
const browser = process.env.RTS_BROWSER_EXECUTABLE || 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const scripts = ['form-feedback.js', 'receipt-panel.js'].map(file => `<script>${fs.readFileSync('public/js/'+file,'utf8')}</script>`).join('');
const html = `<!doctype html><body>
<form method="post" target="sink" action="about:blank" data-reward-approval>
<div data-allocation-summary>1 reward</div><input type="checkbox" name="available_stock"><button data-approve-rewards>Approve</button></form>
<iframe name="sink" hidden></iframe>
${scripts}
<script>
const form=document.querySelector('form'), button=form.querySelector('button');
window.confirm=()=>false;
let prevented;
document.addEventListener('submit',event=>{prevented=event.defaultPrevented;});
window.addEventListener('load',()=>{ button.dataset.ready='true'; });
window.checkSubmission=()=>{
 setTimeout(()=>{
  try {
   if(prevented) throw Error('Valid approval was cancelled by the disabled-button guard');
   if(!button.disabled || button.textContent!=='Submitting...') throw Error('Submission feedback missing');
   window.dispatchEvent(new Event('pageshow'));
   if(button.disabled || button.textContent!=='Approve') throw Error('Back navigation did not restore the button');
   form.elements.available_stock.checked=true;
   button.click();
   setTimeout(()=>{
    document.body.dataset.testResult=prevented && !button.disabled && button.textContent==='Approve' ? 'PASS' : 'FAIL: cancelled confirmation left a stuck button';
   },50);
  } catch(error) {document.body.dataset.testResult='FAIL: '+error.message;}
 },50);
};
</script></body>`;
async function main() {
 const fixture=path.join(temp,'fixture.html');fs.writeFileSync(fixture,html);
 const process=spawn(browser,['--headless','--disable-gpu','--no-first-run','--no-default-browser-check','--remote-debugging-port=0','--user-data-dir='+path.join(temp,'profile'),pathToFileURL(fixture).href],{windowsHide:true,stdio:'ignore'});
 const wait=ms=>new Promise(resolve=>setTimeout(resolve,ms));
 let socket;
 try {
  const portFile=path.join(temp,'profile','DevToolsActivePort');
  for(let i=0;i<100 && !fs.existsSync(portFile);i++) await wait(100);
  const port=fs.readFileSync(portFile,'utf8').split('\n')[0];
  const tabs=await (await fetch('http://127.0.0.1:'+port+'/json')).json();
  const tab=tabs.find(tab=>tab.url===pathToFileURL(fixture).href);
  socket=new WebSocket(tab.webSocketDebuggerUrl);
  await new Promise(resolve=>socket.addEventListener('open',resolve,{once:true}));
  let id=0;const pending=new Map();
  socket.addEventListener('message',event=>{const message=JSON.parse(event.data);if(message.id){const item=pending.get(message.id);pending.delete(message.id);message.error?item.reject(message.error):item.resolve(message.result);}});
  const send=(method,params={})=>new Promise((resolve,reject)=>{pending.set(++id,{resolve,reject});socket.send(JSON.stringify({id,method,params}));});
  const evaluate=expression=>send('Runtime.evaluate',{expression,returnByValue:true}).then(result=>result.result.value);
  for(let i=0;i<100 && !await evaluate('document.querySelector("button")?.dataset.ready');i++) await wait(50);
  const rect=await evaluate('(()=>{const r=document.querySelector("button").getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2};})()');
  await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...rect});
  await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...rect});
  await evaluate('window.checkSubmission()');
  let result;
  for(let i=0;i<100;i++){result=await evaluate('document.body.dataset.testResult');if(result)break;await wait(50);}
  assert.equal(result,'PASS');
  console.log('Submission browser smoke passed: native approval click, loading feedback, back navigation, cancelled confirmation.');
 } finally {
  socket?.close();process.kill();
  await new Promise(resolve=>process.exitCode!==null?resolve():process.once('exit',resolve));
  fs.rmSync(temp,{recursive:true,force:true,maxRetries:5,retryDelay:200});
 }
}
main().catch(error=>{console.error(error);process.exitCode=1;});
