// Two isolated Chrome sessions: incoming updates, draft preservation, sending and reconnects.
// node conversation-live-browser.mjs fixtures.json [base-url] [debug-port]
import {readFileSync, writeFileSync} from 'node:fs';
import {join} from 'node:path';
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
  await wait(owner, '!!document.getElementById("tipster-tips-title")');
  check(await evaluate(owner, "location.pathname==='/tipsters/'"), 'Submission returns to the dashboard');
  await evaluate(owner, "document.querySelector('.cammino-tipsters__tip-actions .button').click();true;");
  await wait(owner, '!!document.querySelector("#conversation[data-cursor]")');
  const id = await evaluate(owner, "document.getElementById('conversation').dataset.tip");
  await evaluate(owner, "window.liveSentinel='owner';document.getElementById('message-body').value='Unsent draft';true;");
  await navigate(admin, '/wp-login.php', '!!document.getElementById("user_login")');
  await evaluate(admin, `document.getElementById('user_login').value=${JSON.stringify(fixtures.users.admin.username)};document.getElementById('user_pass').value=${JSON.stringify(fixtures.password)};document.getElementById('loginform').requestSubmit();true;`);
  await wait(admin, '!!document.getElementById("toplevel_page_cammino-tips")');
  await navigate(admin, '/wp-admin/admin.php?page=cammino-tips&tip_id='+id, '!!document.getElementById("conversation")');
  await evaluate(admin, "window.changingStatus=true;document.querySelector('input[name=status][value=discussion]').form.requestSubmit();true;");
  await wait(admin, 'document.readyState === "complete" && !window.changingStatus && !!document.querySelector("input[name=status][value=approved]")');
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
  // Hold responses to prove the composer clears synchronously, before any await.
  for (const [session, name] of [[owner,'tipster'],[admin,'admin']]) {
    await wait(session, '!document.querySelector("[data-message-form] button[type=submit]").disabled');
    const sent = `Send race ${name}`, draft = `Hello ${name}`;
    check(await evaluate(session, `window.testOriginalFetch=window.fetch;window.sendHeld=false;
      window.fetch=async (...args)=>{const response=await window.testOriginalFetch(...args);if(args[1]?.body?.get('operation')==='send_message'){window.sendHeld=true;await new Promise(resolve=>{window.releaseSend=resolve;});}return response;};
      document.getElementById('message-body').value=${JSON.stringify(sent)};document.getElementById('message-body').form.requestSubmit();
      document.getElementById('message-body').value==='' && [...document.querySelectorAll('[data-local-message].is-pending .cammino-conversation__text')].some(n=>n.textContent===${JSON.stringify(sent)})`), `${name}: clears immediately and renders a pending bubble`);
    await wait(session, 'window.sendHeld');
    await evaluate(session, "document.getElementById('message-body').focus();true;");
    await cdp('Input.insertText', {text:draft}, session);
    check(await evaluate(session, `document.getElementById('message-body').value===${JSON.stringify(draft)}`), `${name}: new typing never contains the submitted text`);
    await evaluate(session, 'window.releaseSend();true;');
    await wait(session, '!document.querySelector("[data-message-form] button[type=submit]").disabled');
    check(await evaluate(session, `document.getElementById('message-body').value===${JSON.stringify(draft)} && [...document.querySelectorAll('[data-message-id] .cammino-conversation__text')].filter(n=>n.textContent===${JSON.stringify(sent)}).length===1 && !document.querySelector('[data-local-message]')`), `${name}: confirms the bubble once without touching the composer`);
    await evaluate(session, 'window.fetch=window.testOriginalFetch;true;');
    for (const persisted of [false,true]) {
      const failed = `Failed send ${name} ${persisted ? 'saved' : 'unsaved'}`, continuation = `Another message ${name} ${persisted}`;
      check(await evaluate(session, `window.sendHeld=false;
        window.fetch=async (...args)=>{if(args[1]?.body?.get('operation')==='send_message'){if(${persisted}) await window.testOriginalFetch(...args);window.sendHeld=true;await new Promise(resolve=>{window.releaseSend=resolve;});throw new TypeError('Simulated failed send');}return window.testOriginalFetch(...args);};
        document.getElementById('message-body').value=${JSON.stringify(failed)};document.getElementById('message-body').form.requestSubmit();document.getElementById('message-body').value===''`), `${name}: failed send also starts with an empty composer`);
      await wait(session, 'window.sendHeld');
      await evaluate(session, "document.getElementById('message-body').focus();true;");
      await cdp('Input.insertText', {text:continuation}, session);
      await evaluate(session, 'window.releaseSend();true;');
      await wait(session, '!document.querySelector("[data-message-form] button[type=submit]").disabled');
      check(await evaluate(session, `(()=>{const bubble=[...document.querySelectorAll('[data-local-message].is-failed')].find(n=>n.querySelector('.cammino-conversation__text').textContent===${JSON.stringify(failed)});return document.getElementById('message-body').value===${JSON.stringify(continuation)} && !!bubble && bubble.querySelector('[role=status]').textContent.startsWith('! ') && bubble.querySelector('[role=status]').textContent.length>20 && !bubble.querySelector('button').hidden;})()`), `${name}: failure stays in the scroll panel with !, a reason and retry`);
      await evaluate(session, 'window.fetch=window.testOriginalFetch;true;');
      if (persisted) {
        await wait(session, `![...document.querySelectorAll('[data-local-message] .cammino-conversation__text')].some(n=>n.textContent===${JSON.stringify(failed)})`, 6000);
        check(await evaluate(session, `[...document.querySelectorAll('[data-message-id] .cammino-conversation__text')].filter(n=>n.textContent===${JSON.stringify(failed)}).length===1 && document.getElementById('message-body').value===${JSON.stringify(continuation)}`), `${name}: polling reconciles a saved message after a lost response`);
      }
      // A new send gets a fresh token even while a different message has failed.
      await evaluate(session, 'document.getElementById("message-body").form.requestSubmit();true;');
      await wait(session, `!document.querySelector('[data-message-form] button[type=submit]').disabled && [...document.querySelectorAll('[data-message-id] .cammino-conversation__text')].some(n=>n.textContent===${JSON.stringify(continuation)})`);
      check(await evaluate(session, `document.getElementById('message-body').value===''`), `${name}: another message sends independently of the failed message`);
      if (!persisted) {
        const nextDraft = `Typing during retry ${name}`;
        await evaluate(session, `document.getElementById('message-body').value=${JSON.stringify(nextDraft)};document.querySelector('[data-local-message].is-failed button').click();true;`);
        await wait(session, `!document.querySelector('[data-message-form] button[type=submit]').disabled && !document.querySelector('[data-local-message]')`);
        check(await evaluate(session, `[...document.querySelectorAll('[data-message-id] .cammino-conversation__text')].filter(n=>n.textContent===${JSON.stringify(failed)}).length===1 && document.getElementById('message-body').value===${JSON.stringify(nextDraft)}`), `${name}: retry sends the original body once without changing the composer`);
      }
    }
    // Server errors explain the actual reason inline; retry never creates a second record.
    const invalid = `Rejected send ${name}`;
    await evaluate(session, `window.fetch=async (...args)=>args[1]?.body?.get('operation')==='send_message'?new Response(JSON.stringify({success:false,data:{message:'Server validation reason',can_send:true}}),{status:422,headers:{'Content-Type':'application/json'}}):window.testOriginalFetch(...args);
      document.getElementById('message-body').value=${JSON.stringify(invalid)};document.getElementById('message-body').form.requestSubmit();true;`);
    await wait(session, "!!document.querySelector('[data-local-message].is-failed')");
    check(await evaluate(session, "document.querySelector('[data-local-message].is-failed [role=status]').textContent.includes('Server validation reason') && document.getElementById('message-body').value===''") , `${name}: server failure reason appears on the failed bubble`);
    for (const width of [1280,390]) {
      await cdp('Emulation.setDeviceMetricsOverride', {width,height:900,deviceScaleFactor:1,mobile:width<600},session);
      await evaluate(session, "(()=>{document.getElementById('conversation').scrollIntoView({block:'start',behavior:'instant'});const s=document.querySelector('[data-chat-scroll]');s.scrollTop=s.scrollHeight;return true;})()");
      await sleep(200);
      check(await evaluate(session, `(()=>{const s=document.querySelector('[data-chat-scroll]').getBoundingClientRect(), b=document.querySelector('[data-local-message].is-failed button').getBoundingClientRect(),f=document.querySelector('[data-message-form]').getBoundingClientRect();return b.left>=s.left && b.right<=s.right && f.top>=s.bottom && document.documentElement.scrollWidth<=${width}+1;})()`), `${name}: failed bubble and retry fit inside the scroll panel at ${width}px`);
      const shot = await cdp('Page.captureScreenshot', {format:'png',captureBeyondViewport:false},session);
      writeFileSync(join(process.env.TEMP, `cammino-chat-failed-${name}-${width}.png`),Buffer.from(shot.data,'base64'));
    }
    await evaluate(session, "window.fetch=window.testOriginalFetch;document.querySelector('[data-local-message].is-failed button').click();true;");
    await wait(session, "!document.querySelector('[data-local-message]') && !document.querySelector('[data-message-form] button[type=submit]').disabled");
  }
  console.log(`Passed ${checks} two-session live conversation browser checks.`);
} finally { await cdp('Browser.close'); }
