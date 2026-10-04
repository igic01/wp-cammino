// Two isolated Chrome sessions: incoming updates, draft preservation, sending and reconnects.
// node conversation-live-browser.mjs fixtures.json [base-url] [debug-port]
import {readFileSync} from 'node:fs';
const fixtures = JSON.parse(readFileSync(process.argv[2], 'utf8').replace(/^\uFEFF/, ''));
const base = process.argv[3] || 'http://127.0.0.1:8765';
const port = process.argv[4] || '9225';
const version = await (await fetch(`http://127.0.0.1:${port}/json/version`)).json();
const ws = new WebSocket(version.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
let next = 0, checks = 0;
const pending = new Map(), polls = [];
ws.onmessage = event => {
  const message = JSON.parse(event.data);
  if (message.id) {
    const request = pending.get(message.id); pending.delete(message.id);
    message.error ? request.reject(message.error) : request.resolve(message.result);
  } else if (message.method === 'Network.requestWillBeSent' && message.params.request.postData?.includes('operation=poll')) {
    polls.push({session:message.sessionId, time:Date.now()});
  }
};
function cdp(method, params = {}, sessionId) {
  return new Promise((resolve, reject) => {
    const id = ++next; pending.set(id, {resolve, reject}); ws.send(JSON.stringify({id, method, params, sessionId}));
  });
}
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
async function evaluate(session, expression) {
  const result = await cdp('Runtime.evaluate', {expression, returnByValue:true, awaitPromise:true}, session);
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
  return result.result.value;
}
async function wait(session, expression, ms = 22000) {
  const end = Date.now() + ms;
  while (Date.now() < end) { if (await evaluate(session, expression)) return; await sleep(200); }
  throw new Error('Browser condition failed: ' + expression);
}
function check(ok, name) { ++checks; if (!ok) throw new Error(name); }
async function page() {
  const {browserContextId} = await cdp('Target.createBrowserContext');
  const {targetId} = await cdp('Target.createTarget', {url:'about:blank', browserContextId});
  const {sessionId} = await cdp('Target.attachToTarget', {targetId, flatten:true});
  await cdp('Page.enable', {}, sessionId); await cdp('Network.enable', {}, sessionId);
  return sessionId;
}
async function navigate(session, path, condition) {
  await cdp('Page.navigate', {url:base+path}, session);
  await wait(session, `document.readyState === 'complete' && (${condition})`);
}
try {
  const owner = await page(), admin = await page();
  await navigate(owner, '/tipsters/login/', '!!document.getElementById("tipster-username")');
  await evaluate(owner, `document.getElementById('tipster-username').value=${JSON.stringify(fixtures.users.one.username)};document.getElementById('tipster-password').value=${JSON.stringify(fixtures.password)};document.querySelector('.cammino-tipsters__login form').requestSubmit();true;`);
  await wait(owner, '!!document.getElementById("tipster-tips-title")');
  await navigate(owner, '/tipsters/new/', '!!document.getElementById("tip-title")');
  await evaluate(owner, "document.getElementById('tip-title').value='Live browser tip';document.getElementById('tip-short_description').value='Short';document.getElementById('tip-long_description').value='Long';document.getElementById('tip-title').form.requestSubmit();true;");
  await wait(owner, '!!document.querySelector("#conversation[data-cursor]")');
  const id = await evaluate(owner, "document.getElementById('conversation').dataset.tip");
  await evaluate(owner, "window.liveSentinel='owner';document.getElementById('message-body').value='Unsent draft';true;");
  await navigate(admin, '/wp-login.php', '!!document.getElementById("user_login")');
  await evaluate(admin, `document.getElementById('user_login').value=${JSON.stringify(fixtures.users.admin.username)};document.getElementById('user_pass').value=${JSON.stringify(fixtures.password)};document.getElementById('loginform').requestSubmit();true;`);
  await wait(admin, '!!document.getElementById("toplevel_page_cammino-tips")');
  await navigate(admin, '/wp-admin/admin.php?page=cammino-tips&tip_id='+id, '!!document.getElementById("conversation")');
  await evaluate(admin, "document.querySelector('input[name=status][value=discussion]').form.requestSubmit();true;");
  await wait(admin, '!!document.querySelector("input[name=status][value=approved]")');
  await wait(owner, '!document.querySelector("[data-message-form]").hidden');
  check(await evaluate(owner, "liveSentinel==='owner' && document.getElementById('message-body').value==='Unsent draft'"), 'Discussion opens without reloading or destroying the draft');
  await evaluate(admin, "window.liveSentinel='admin';document.getElementById('message-body').value='Automatic incoming reply';document.getElementById('message-body').form.requestSubmit();true;");
  await wait(owner, "[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent==='Automatic incoming reply')");
  check(await evaluate(owner, "liveSentinel==='owner' && document.getElementById('message-body').value==='Unsent draft'"), 'Incoming admin reply appears without reload and preserves composing text');
  check(await evaluate(admin, "liveSentinel==='admin' && document.getElementById('message-body').value===''"), 'AJAX send clears only the sent draft and preserves the admin page');
  await evaluate(owner, "document.getElementById('message-body').value='Owner automatic reply';document.getElementById('message-body').form.requestSubmit();true;");
  await wait(admin, "[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent==='Owner automatic reply')");
  check(await evaluate(admin, "liveSentinel==='admin'"), 'Owner reply automatically reaches the unchanged admin page');
  // Exercise the visibility event without switching contexts and losing the test connection.
  await evaluate(owner, "window.testHidden=true;Object.defineProperty(document,'hidden',{configurable:true,get:()=>window.testHidden});document.dispatchEvent(new Event('visibilitychange'));true;");
  await sleep(700); const pausedCount = polls.filter(p=>p.session===owner).length;
  await evaluate(admin, "document.getElementById('message-body').value='Reply while hidden';document.getElementById('message-body').form.requestSubmit();true;");
  await wait(admin, "[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent==='Reply while hidden')");
  await sleep(6500);
  check(polls.filter(p=>p.session===owner).length===pausedCount, 'Hidden tab sends no polls');
  await evaluate(owner, "window.testHidden=false;document.dispatchEvent(new Event('visibilitychange'));true;");
  await wait(owner, "[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent==='Reply while hidden')", 6000);
  check(await evaluate(owner, "document.querySelectorAll('[data-live-messages] li').length===3 && liveSentinel==='owner'"), 'Returning tab catches up immediately without duplicate messages');
  await cdp('Network.emulateNetworkConditions', {offline:true, latency:0, downloadThroughput:0, uploadThroughput:0}, owner);
  await evaluate(owner, "window.dispatchEvent(new Event('offline'));true;");
  await evaluate(admin, "document.getElementById('message-body').value='Reply while offline';document.getElementById('message-body').form.requestSubmit();true;");
  await wait(admin, "[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent==='Reply while offline')");
  await cdp('Network.emulateNetworkConditions', {offline:false, latency:0, downloadThroughput:-1, uploadThroughput:-1}, owner);
  await evaluate(owner, "window.dispatchEvent(new Event('online'));true;");
  await wait(owner, "[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent==='Reply while offline')", 6000);
  check(await evaluate(owner, "liveSentinel==='owner'"), 'Reconnection resumes automatic updates without reload');
  check(polls.filter(p=>p.session===owner).length < 15, 'Polling remains bounded across the whole browser workflow');
  console.log(`Passed ${checks} two-session live conversation browser checks.`);
} finally { await cdp('Browser.close'); }
