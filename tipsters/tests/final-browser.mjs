// Final UX checks in two isolated Chrome sessions.
// node final-browser.mjs fixtures.json base-url debug-port wp-load php screenshot-dir
import {readFileSync, writeFileSync, mkdirSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import {resolve, dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
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
const shots = resolve(process.argv[7]); mkdirSync(shots, {recursive:true});
async function screenshot(session, name) {
  const image = await cdp('Page.captureScreenshot', {format:'png', captureBeyondViewport:false}, session);
  writeFileSync(resolve(shots, name+'.png'), Buffer.from(image.data, 'base64'));
}
async function viewport(session, width) {
  await cdp('Emulation.setDeviceMetricsOverride', {width, height:900, deviceScaleFactor:1, mobile:width < 600}, session);
  await cdp('Emulation.setTouchEmulationEnabled', {enabled:width < 600, maxTouchPoints:1}, session);
  await sleep(250);
}
async function send(session, text) {
  await evaluate(session, `document.getElementById('message-body').value=${JSON.stringify(text)};document.getElementById('message-body').form.requestSubmit();true;`);
  await wait(session, `[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent===${JSON.stringify(text)})`);
}
async function enter(session, shift = false) {
  await cdp('Input.dispatchKeyEvent', {type:'keyDown', key:'Enter', code:'Enter', windowsVirtualKeyCode:13, nativeVirtualKeyCode:13, text:'\r', unmodifiedText:'\r', modifiers:shift ? 8 : 0}, session);
  await cdp('Input.dispatchKeyEvent', {type:'keyUp', key:'Enter', code:'Enter', windowsVirtualKeyCode:13}, session);
}
try {
  const owner = await page(), admin = await page();
  await viewport(owner, 390);
  await navigate(owner, '/tipsters/login/', '!!document.getElementById("tipster-username")');
  await wait(owner, '!document.querySelector("[data-password-toggle]").hidden');
  for (const width of [1280,390]) {
    await viewport(owner,width);
    check(await evaluate(owner, "(()=>{const r=document.querySelector('.cammino-tipsters__login').getBoundingClientRect();return Math.abs(r.left+r.width/2-document.documentElement.clientWidth/2)<2;})()"), `Login card is centered at ${width}px`);
    await screenshot(owner, `login-${width}`);
  }
  check(await evaluate(owner, "document.querySelector('.footer-tipster-login').pathname==='/tipsters/login/'"), 'Footer link targets tipster login');
  await evaluate(owner, `document.getElementById('tipster-username').value=${JSON.stringify(fixtures.users.one.username)};document.getElementById('tipster-password').value=${JSON.stringify(fixtures.password)};document.querySelector('[data-password-toggle]').click();true;`);
  check(await evaluate(owner, `document.getElementById('tipster-password').type==='text' && document.getElementById('tipster-password').value===${JSON.stringify(fixtures.password)} && document.querySelector('[data-password-toggle]').getAttribute('aria-pressed')==='true'`), 'Show password preserves entered value and exposes toggle state');
  await evaluate(owner, "document.querySelector('[data-password-toggle]').click();true;");
  check(await evaluate(owner, "document.getElementById('tipster-password').type==='password' && document.querySelector('[data-password-toggle]').getAttribute('aria-pressed')==='false'"), 'Hide password restores masking');
  await screenshot(owner, 'login-mobile');
  await evaluate(owner, "document.querySelector('.cammino-tipsters__login form').requestSubmit();true;");
  await wait(owner, '!!document.getElementById("tipster-tips-title")');
  await navigate(owner, '/tipsters/new/', '!!document.getElementById("tip-title")');
  await evaluate(owner, "document.getElementById('tip-title').value='Final browser tip';document.getElementById('tip-short_description').value='Short';document.getElementById('tip-long_description').value='Long';document.getElementById('tip-title').form.requestSubmit();true;");
  await wait(owner, '!!document.getElementById("tipster-tips-title")');
  check(await evaluate(owner, "location.pathname==='/tipsters/'"), 'Submission returns to the dashboard');
  await evaluate(owner, "document.querySelector('.cammino-tipsters__tip-actions .button').click();true;");
  await wait(owner, '!!document.querySelector("#conversation[data-cursor]")');
  const id = await evaluate(owner, "document.getElementById('conversation').dataset.tip");
  const ownerPath = '/tipsters/tip/'+id+'/', adminPath = '/wp-admin/admin.php?page=cammino-tips&tip_id='+id;
  await navigate(owner, '/tipsters/', '!!document.querySelector(".cammino-tipsters__list-item")');
  for (const width of [1280,390]) {
    await viewport(owner,width);
    check(await evaluate(owner, `(()=>{const b=document.querySelector('.cammino-tipsters__logout button');const r=b.getBoundingClientRect();const bar=document.querySelector('.cammino-tipsters__topbar').getBoundingClientRect();return Math.abs(r.right-bar.right)<2 && Math.abs(r.top-bar.top)<2 && getComputedStyle(b).backgroundColor!=='rgba(0, 0, 0, 0)' && document.documentElement.scrollWidth<=${width}+1;})()`), `Logout occupies top right with a solid contrasting background at ${width}px`);
    await screenshot(owner, `dashboard-${width}`);
  }
  check(await evaluate(owner, "!document.querySelector('.cammino-tipsters__list-item h3 a') && !!document.querySelector('.cammino-tipsters__tip-actions .button')"), 'Tip opens through a dedicated button rather than a title link');
  await evaluate(owner, "document.querySelector('.cammino-tipsters__tip-actions .button').click();true;");
  await wait(owner, '!!document.getElementById("conversation")');
  await navigate(admin, '/wp-login.php', '!!document.getElementById("user_login")');
  await evaluate(admin, `document.getElementById('user_login').value=${JSON.stringify(fixtures.users.admin.username)};document.getElementById('user_pass').value=${JSON.stringify(fixtures.password)};document.getElementById('loginform').requestSubmit();true;`);
  await wait(admin, '!!document.getElementById("toplevel_page_cammino-tips")');
  await navigate(admin, adminPath, '!!document.getElementById("conversation")');
  await evaluate(admin, "document.querySelector('input[name=status][value=discussion]').form.requestSubmit();true;");
  await wait(admin, '!!document.querySelector("input[name=status][value=rejected]")');
  const helper = resolve(dirname(fileURLToPath(import.meta.url)), 'http-fixtures.php');
  const seed = JSON.parse(execFileSync(process.argv[6], [helper, process.argv[5], 'chat-history', fixtures.prefix], {encoding:'utf8'}));
  check(String(seed.tip_id)===id && seed.messages===45, 'Conversation contains more than two pages of persisted history');
  await navigate(owner, ownerPath, '!!document.querySelector("input[name=operation][value=edit_tip]")');
  await wait(owner, "document.querySelector('[data-chat-scroll]').scrollTop > 0");
  check(await evaluate(owner, "[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent.startsWith('Historical message 45')) && ![...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent.startsWith('Historical message 1\\n'))"), 'Conversation initially shows latest history page');
  for (const [session, name] of [[owner,'tipster'],[admin,'admin']]) {
    if (session===admin) await navigate(admin, adminPath, '!!document.getElementById("conversation")');
    for (const width of [1280,390,360]) {
      await viewport(session,width);
      check(await evaluate(session, `(()=>{const s=document.querySelector('[data-chat-scroll]');const f=document.querySelector('[data-message-form]');return document.documentElement.scrollWidth<=${width}+1 && s.clientHeight>=250 && s.clientHeight<=480 && s.scrollHeight>s.clientHeight && f.getBoundingClientRect().top>=s.getBoundingClientRect().bottom;})()`), `${name} ${width}px: bounded scrolling, no horizontal overflow and composer outside message area`);
      check(await evaluate(session, "(()=>{const t=document.getElementById('message-body').getBoundingClientRect();const b=document.querySelector('[data-message-form] button[type=submit]').getBoundingClientRect();return b.left>=t.right && Math.abs(b.bottom-t.bottom)<2 && t.width>100 && document.getElementById('message-body').maxLength===600;})()"), `${name} ${width}px: Send beside textarea with 600-character limit`);
      await evaluate(session, "document.getElementById('conversation').scrollIntoView({block:'start',behavior:'instant'});true;");
      await sleep(250); await screenshot(session, `${name}-${width}`);
    }
  }
  for (const [session, name] of [[owner,'tipster'],[admin,'admin']]) {
    await viewport(session,1280);
    check(await evaluate(session, "window.matchMedia('(hover: hover) and (pointer: fine)').matches"), `${name}: desktop keyboard shortcut is enabled`);
    await evaluate(session, `window.keyboardSentinel=true;const t=document.getElementById('message-body');t.value=${JSON.stringify('Keyboard '+name+' first')};t.focus();t.setSelectionRange(t.value.length,t.value.length);true;`);
    await enter(session,true);
    await cdp('Input.insertText', {text:'Second line'}, session);
    const keyboardBody = await evaluate(session, "document.getElementById('message-body').value");
    check(keyboardBody===`Keyboard ${name} first\nSecond line`, `${name}: Shift+Enter inserts a line break without sending`);
    await enter(session);
    await wait(session, `[...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent===${JSON.stringify(keyboardBody)})`);
    check(await evaluate(session, "keyboardSentinel && document.getElementById('message-body').value===''"), `${name}: Enter sends the multiline message without navigation`);
  }
  await viewport(owner,390);
  check(await evaluate(owner, "!window.matchMedia('(hover: hover) and (pointer: fine)').matches && document.querySelector('[data-keyboard-help]').hidden"), 'Mobile touch mode keeps the desktop shortcut disabled');
  await evaluate(owner, "(()=>{const t=document.getElementById('message-body');t.value='Mobile line';t.focus();t.setSelectionRange(t.value.length,t.value.length);return true;})()");
  await enter(owner);
  check(await evaluate(owner, "document.getElementById('message-body').value==='Mobile line\\n' && ![...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent==='Mobile line')"), 'Mobile Enter inserts a newline without sending');
  await evaluate(owner, "window.finalSentinel='owner';document.getElementById('message-body').value='Preserved unsent draft';const s=document.querySelector('[data-chat-scroll]');s.scrollTop=0;s.dispatchEvent(new Event('scroll'));window.readingY=window.scrollY;window.composerY=document.querySelector('[data-message-form]').getBoundingClientRect().top;true;");
  await send(admin, 'Incoming while reading earlier messages');
  await wait(owner, "!document.querySelector('[data-new-messages]').hidden");
  check(await evaluate(owner, "document.querySelector('[data-chat-scroll]').scrollTop<5 && Math.abs(window.scrollY-window.readingY)<2 && document.getElementById('message-body').value==='Preserved unsent draft' && finalSentinel==='owner'"), 'Incoming messages preserve reading position, page position and draft');
  check(await evaluate(owner, "(()=>{const n=document.querySelector('[data-new-messages]').getBoundingClientRect();const s=document.querySelector('[data-chat-scroll]').getBoundingClientRect();return n.top>=s.top && n.bottom<=s.bottom && Math.abs(document.querySelector('[data-message-form]').getBoundingClientRect().top-composerY)<2;})()"), 'New-message notification appears inside the panel without moving the composer');
  await evaluate(owner, "document.querySelector('[data-new-messages]').click();true;");
  check(await evaluate(owner, "(()=>{const s=document.querySelector('[data-chat-scroll]');return s.scrollHeight-s.scrollTop-s.clientHeight<3 && document.querySelector('[data-new-messages]').hidden;})()"), 'New-message button jumps only the message panel to its latest message');
  await send(owner, 'Owner latest reply');
  check(await evaluate(owner, "(()=>{const s=document.querySelector('[data-chat-scroll]');return s.scrollHeight-s.scrollTop-s.clientHeight<3 && document.querySelector('[data-live-messages] li:last-child').classList.contains('is-own') && finalSentinel==='owner';})()"), 'Sending follows latest, styles own bubble and keeps page alive');
  await navigate(admin, adminPath, '!!document.querySelector("input[name=status][value=rejected]")');
  await evaluate(admin, "document.querySelector('input[name=status][value=rejected]').form.requestSubmit();true;");
  await wait(admin, '!!document.querySelector("input[name=confirm_reopen]")');
  await wait(owner, "document.querySelector('[data-message-form]').hidden && document.querySelector('[data-tip-status]').textContent==='Zamietnutý'");
  check(await evaluate(owner, "document.querySelector('[data-chat-state]').textContent==='Zamietnutý' && document.querySelector('[data-tip-notice]').textContent.includes('Tip je zamietnutý.') && document.getElementById('tip-title').disabled && finalSentinel==='owner' && document.getElementById('message-body').value===''"), 'Rejection closes chat and locks an already open edit form without reload');
  await navigate(owner, ownerPath+'?messages_page=1', '!!document.getElementById("conversation")');
  check(await evaluate(owner, "document.querySelector('[data-message-form]').hidden && !document.querySelector('input[name=operation][value=edit_tip]') && [...document.querySelectorAll('.cammino-conversation__text')].some(n=>n.textContent.startsWith('Historical message 1\\n'))"), 'Rejected tip remains read-only with accessible older history');
  await evaluate(admin, "const f=document.querySelector('input[name=action][value=cammino_admin_delete_tip]').form;f.closest('details').open=true;f.elements.confirm_title.value='Final browser tip';f.elements.confirm_delete.checked=true;f.requestSubmit();true;");
  await wait(admin, "!location.href.includes('tip_id=') && !!document.getElementById('toplevel_page_cammino-tips')");
  check(await evaluate(admin, "document.body.textContent.includes('Tip a všetky jeho záznamy boli odstránené.')"), 'Confirmed admin deletion succeeds through browser form');
  await wait(owner, "document.querySelector('[data-live-status]').textContent.includes('Tip už nie je dostupný.')");
  check(await evaluate(owner, "document.querySelectorAll('[data-message-id]').length===0 && document.querySelector('[data-message-form]').hidden"), 'An already open conversation clears private messages after purge');
  console.log(`Passed ${checks} final-stage browser UX checks. Screenshots: ${shots}`);
} finally { await cdp('Browser.close'); }
