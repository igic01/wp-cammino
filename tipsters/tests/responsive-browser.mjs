// Chrome CDP review. Run with: node responsive-browser.mjs <fixtures.json> <output-directory> [base-url] [debug-port]
// Generate credentials with the guarded http-fixtures.php setup helper; clean them afterwards.
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
const fixtures = JSON.parse(readFileSync(process.argv[2], 'utf8').replace(/^\uFEFF/, ''));
const output = process.argv[3];
const base = process.argv[4] || 'http://127.0.0.1:8765';
const port = process.argv[5] || '9225';
mkdirSync(output, { recursive: true });
const targets = await (await fetch(`http://127.0.0.1:${port}/json`)).json();
const page = targets.find(t => t.type === 'page');
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
let next = 0;
const pending = new Map();
ws.onmessage = event => {
  const message = JSON.parse(event.data);
  if (message.id && pending.has(message.id)) {
    const request = pending.get(message.id); pending.delete(message.id);
    message.error ? request.reject(message.error) : request.resolve(message.result);
  }
};
function cdp(method, params = {}) {
  return new Promise((resolve, reject) => { const id = ++next; pending.set(id, {resolve, reject}); ws.send(JSON.stringify({id, method, params})); });
}
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
async function evaluate(expression) {
  const result = await cdp('Runtime.evaluate', {expression, returnByValue: true, awaitPromise: true});
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
  return result.result.value;
}
async function waitFor(expression) {
  for (let i = 0; i < 60; i++) { if (await evaluate(expression)) return; await pause(200); }
  throw new Error(`Page did not become ready: ${expression}; ${JSON.stringify(await evaluate('({url:location.href,text:document.body.innerText.slice(0,800)})'))}`);
}
async function navigate(path, expression) {
  await cdp('Page.navigate', {url: base + path}); await waitFor(`location.href === ${JSON.stringify(base + path)} && document.readyState === 'complete' && (${expression})`); await pause(200);
}
let checks = 0;
async function screenshot(name, width, height) {
	await waitFor("document.readyState === 'complete'");
  await cdp('Emulation.setDeviceMetricsOverride', {width, height, deviceScaleFactor: 1, mobile: width < 600});
  await pause(250); await evaluate('document.fonts.ready.then(() => true)');
  await evaluate("scrollTo({top:0,left:0,behavior:'instant'}); true"); await pause(100);
  const bounds = await evaluate(`(() => {
    const card = document.querySelector('.cammino-tipsters__card').getBoundingClientRect();
    return {width:innerWidth, body:document.body.scrollWidth, cardRight:card.right,
      headingTop:document.querySelector('.cammino-tipsters__heading').getBoundingClientRect().top + scrollY,
      headerBottom:document.querySelector('.site-header').getBoundingClientRect().bottom,
      translation:!!document.querySelector('[data-language-switcher]'),
      unlabeled:[...document.querySelectorAll('input:not([type=hidden]),textarea')].filter(x=>!x.labels?.length).length};
  })()`);
  if (bounds.body > bounds.width || bounds.cardRight > bounds.width || bounds.headingTop < bounds.headerBottom || bounds.translation || bounds.unlabeled) throw new Error(JSON.stringify(bounds));
  ++checks;
  const shot = await cdp('Page.captureScreenshot', {format:'png', captureBeyondViewport: true});
  writeFileSync(join(output, name + '.png'), Buffer.from(shot.data, 'base64'));
}
try {
  await cdp('Page.enable');
  await navigate('/tipsters/login/', '!!document.getElementById("tipster-username")');
  await evaluate(`document.getElementById('tipster-username').value=${JSON.stringify(fixtures.users.one.username)};
    document.getElementById('tipster-password').value=${JSON.stringify(fixtures.password)};
    document.querySelector('.cammino-tipsters__login form').requestSubmit(); true;`);
  await waitFor('!!document.getElementById("tipster-tips-title")');
  await screenshot('dashboard-empty-mobile', 390, 844);
  await navigate('/tipsters/new/', '!!document.getElementById("tip-title")');
  await screenshot('new-tip-desktop', 1280, 1100);
  await screenshot('new-tip-mobile', 390, 844);
  await evaluate(`document.getElementById('tip-file-link').value='https://drive.google.com/drive/folders/example?usp=sharing'; true;`);
  await evaluate(`document.getElementById('tip-title').value='Tip z prehliadača';
    document.getElementById('tip-short_description').value=${JSON.stringify('Krátky popis\nDruhý riadok')};
    document.getElementById('tip-long_description').value='Podrobný popis odoslaný cez prehliadač.';
    document.getElementById('tip-title').form.requestSubmit(); true;`);
  await waitFor('!!document.getElementById("tipster-tips-title")');
  await evaluate("document.querySelector('.cammino-tipsters__tip-actions .button').click();true;");
  await waitFor('!!document.querySelector(".cammino-tipsters__detail")');
  const tipPath = await evaluate('location.pathname');
  await screenshot('tip-detail-mobile', 390, 844);
  await navigate('/tipsters/', '!!document.getElementById("tipster-tips-title")');
  await screenshot('dashboard-with-tip-desktop', 1280, 900);
  await screenshot('dashboard-with-tip-mobile', 390, 844);
  if (process.argv[6] === 'review') {
    await evaluate(`document.querySelector('input[name=operation][value=logout]').form.requestSubmit(); true;`);
    await waitFor('!!document.getElementById("tipster-username")');
    await navigate('/wp-login.php', '!!document.getElementById("user_login")');
    await evaluate(`document.getElementById('user_login').value=${JSON.stringify(fixtures.users.admin.username)};
      document.getElementById('user_pass').value=${JSON.stringify(fixtures.password)};document.getElementById('loginform').requestSubmit();true;`);
    await waitFor('!!document.getElementById("toplevel_page_cammino-tips")');
    await navigate('/wp-admin/admin.php?page=cammino-tips', '!!document.querySelector(".cammino-admin-tips")');
    async function adminScreenshot(name, width, height) {
      await waitFor("document.readyState === 'complete'");
      await cdp('Emulation.setDeviceMetricsOverride', {width,height,deviceScaleFactor:1,mobile:width<600});await pause(300);
      const bounds = await evaluate('({width:innerWidth, body:document.body.scrollWidth})');
      if (bounds.body>bounds.width) throw new Error(JSON.stringify(bounds));++checks;
      const shot=await cdp('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
      writeFileSync(join(output,name+'.png'),Buffer.from(shot.data,'base64'));
    }
    await adminScreenshot('admin-tips-desktop',1280,900);
    await adminScreenshot('admin-tips-mobile',390,844);
    const tipId=tipPath.match(/tip\/(\d+)/)[1];
    await navigate('/wp-admin/admin.php?page=cammino-tips&tip_id='+tipId,'!!document.querySelector(".cammino-admin-tips__status-form")');
    await adminScreenshot('admin-tip-detail-desktop',1280,1000);
    await evaluate(`document.querySelector('input[name=status][value=discussion]').form.requestSubmit(); true;`);
    await waitFor(`!document.querySelector('input[name=status][value=discussion]') && !!document.querySelector('input[name=status][value=approved]')`);
    await adminScreenshot('admin-tip-discussion-mobile',390,844);
    await evaluate(`document.getElementById('message-body').value='Admin browser reply';document.getElementById('message-body').form.requestSubmit();true;`);
    await waitFor(`document.querySelector('.cammino-conversation__text')?.textContent === 'Admin browser reply' && document.readyState === 'complete'`);
    await adminScreenshot('admin-conversation-desktop',1280,1000);
    await adminScreenshot('admin-conversation-mobile',390,844);
    await evaluate(`location.href=document.querySelector('#wp-admin-bar-logout a').href;true;`);
    await waitFor('!!document.getElementById("user_login")');
    await navigate('/tipsters/login/','!!document.getElementById("tipster-username")');
    await evaluate(`document.getElementById('tipster-username').value=${JSON.stringify(fixtures.users.one.username)};
      document.getElementById('tipster-password').value=${JSON.stringify(fixtures.password)};document.querySelector('.cammino-tipsters__login form').requestSubmit();true;`);
    await waitFor('!!document.getElementById("tipster-tips-title")');
    await navigate(tipPath,'!!document.getElementById("tip-title")');
    await screenshot('discussion-edit-mobile',390,844);
    await evaluate(`document.getElementById('tip-title').value='Upravený tip z prehliadača';document.getElementById('tip-title').form.requestSubmit();true;`);
    await waitFor(`!!document.querySelector('.cammino-tipsters__detail h2') && document.querySelector('.cammino-tipsters__detail h2').textContent==='Upravený tip z prehliadača'`);
    await screenshot('discussion-edit-desktop',1280,1000);
    await evaluate(`document.getElementById('message-body').value=${JSON.stringify('Owner browser reply\nSecond line')};document.getElementById('message-body').form.requestSubmit();true;`);
    await waitFor(`document.querySelectorAll('.cammino-conversation__text').length === 2 && document.readyState === 'complete'`);
    await screenshot('conversation-desktop',1280,1000);
    await screenshot('conversation-mobile',390,844);
  }
  console.log(`Passed ${checks} Chrome desktop/mobile layout checks and browser form submission.`);
} finally {
  await cdp('Browser.close');
}
