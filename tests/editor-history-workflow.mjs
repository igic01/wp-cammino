/** Run with `node tests/editor-history-workflow.mjs`. Requires Chrome or BROWSER_BIN. */
import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { existsSync, mkdtempSync, readFileSync, realpathSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = dirname(dirname(fileURLToPath(import.meta.url)));
const chromePath = process.env.BROWSER_BIN || [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    '/usr/bin/google-chrome', '/usr/bin/chromium'
].find(existsSync);
if (!chromePath) throw new Error('Set BROWSER_BIN to a Chrome/Chromium executable.');
const profile = mkdtempSync(join(tmpdir(), 'cammino-editor-history-'));
const state = {
    token: 'token-1',
    current: '<h1 id="title">Current client heading</h1><p id="removed">Recover this copy</p>',
    previous: '<h1 id="title">Older client heading</h1><script>top.previewScriptRan=true</script>',
    saves: [], restores: 0, social: 'facebook,instagram,copy', conflicts: ['p#removed: saved content could not be matched']
};
const previewMarkup = (html) => '<main id="main-content"><section id="hero"><h2 id="title" class="new-layout">'
    + (html.match(/<h[12][^>]*>(.*?)<\/h[12]>/s)?.[1] || 'Default heading') + '</h2></section></main>';
const fixture = `<!doctype html><html><head><link rel="stylesheet" href="/editor.css"></head><body class="nstarter-editor-shell">
<div data-nstarter-loading></div><iframe data-nstarter-frame src="/preview"></iframe>
<aside class="nstarter-editor-panel"><div data-nstarter-status><strong>Ready</strong></div><button data-nstarter-panel-toggle><span>−</span></button>
<select data-nstarter-mode><option value="text">Text</option><option value="media">Media</option></select>
<button data-nstarter-save>Save</button><a data-nstarter-view>View</a><button data-nstarter-history>History</button></aside>
<dialog data-nstarter-video-dialog><form data-nstarter-video-form></form><button data-nstarter-video-cancel></button></dialog>
<dialog data-nstarter-variable-dialog><form data-nstarter-variable-form><h2 data-nstarter-variable-title></h2><span data-nstarter-variable-label></span><input data-nstarter-variable-input><div data-nstarter-variable-project-picker hidden></div><p data-nstarter-variable-picker-hint hidden></p><button type="submit">Apply</button></form><button data-nstarter-variable-cancel></button></dialog>
<dialog data-nstarter-history-dialog class="nstarter-history-dialog"><select data-nstarter-history-version></select>
<p data-nstarter-history-status></p><button data-nstarter-regenerate>Reset to defaults</button><button data-nstarter-history-close>Close</button><button data-nstarter-history-restore disabled>Restore</button></dialog>
<script>window.nstarterEditor={ajaxUrl:'/ajax',nonce:'test',postId:1,source:'home',previewUrl:'/preview',isPost:false,strings:{
confirmSaveConflicts:'Review unmatched content. Save anyway?',mergeWarning:'Review saved content in History.',confirmRestore:'Restore content?',confirmRegenerate:'Reset content?',
noHistory:'No saved versions.',currentVersion:'Current save',loadingHistory:'Loading',saved:'Saved',unsaved:'Unsaved',error:'Failed',regenerated:'Reset',copyLink:'Copy link',hideSocialLinks:'Hide all',editSectionVariable:'Edit section variable'}};</script>
<script src="/editor.js"></script></body></html>`.replaceAll('<button ', '<button type="button" ');
const requests = [];
const personDialog = `<dialog class="nstarter-person-dialog" data-nstarter-person-dialog><form data-nstarter-person-form>
<h2>Edit team member</h2><input name="name" required><input name="role" required><input name="email" type="email" required>
<select name="media_type"><option value="icon">Icon</option><option value="image">Photo</option></select>
<label data-nstarter-person-icon-preset><select name="icon_preset"><option value="fa-solid fa-user">User</option><option value="custom">Custom</option></select></label>
<label data-nstarter-person-icon-field hidden><input name="icon"></label>
<div data-nstarter-person-image-field hidden><input name="image_url" type="url"><button type="button" data-nstarter-person-image-picker>Choose photo</button></div>
<button type="button" data-nstarter-person-cancel>Cancel</button><button type="submit">Apply</button></form></dialog>`;
// Model WordPress's ordinary DOM modal, including both close/select event orders.
const personMediaStub = `<script>window.wp={media(options){
    window.pickerOptions=options;
    const handlers={};const modal=document.createElement('div');
    modal.style.cssText='position:fixed;inset:0;z-index:160000;background:white;display:grid;place-content:center;gap:20px';
    modal.innerHTML='<button type="button" id="choose-photo">Select photo</button><button type="button" id="cancel-photo">Cancel</button>';
    const picker={on(name,handler){handlers[name]=handler;return this;},
        state(){return {get(){return {first(){return {toJSON(){return {url:'https://example.org/team-photo.jpg'};}};}};}};},
        open(){document.body.append(modal);modal.querySelector('button').focus();},
        close(){modal.remove();handlers.close?.();}};
    modal.querySelector('#choose-photo').onclick=()=>{if(window.selectBeforeClose){handlers.select?.();picker.close();}else{picker.close();handlers.select?.();}};
    modal.querySelector('#cancel-photo').onclick=()=>picker.close();
    modal.onkeydown=(event)=>{if(event.key==='Escape')picker.close();};
    return picker;
}};</script>`;

const server = createServer(async (req, res) => {
    const pathname = new URL(req.url, 'http://localhost').pathname;
    requests.push(req.method + ' ' + req.url);
    if (pathname === '/editor.js' || pathname === '/editor.css') {
        res.setHeader('Content-Type', pathname.endsWith('.js') ? 'text/javascript' : 'text/css');
        res.end(readFileSync(join(root, 'assets', pathname.endsWith('.js') ? 'js/editor.js' : 'css/editor.css')));
    } else if (pathname === '/font.woff2') {
        res.setHeader('Content-Type', 'font/woff2');
        res.end(readFileSync(join(root, 'assets/fonts/fredoka.woff2')));
    } else if (pathname === '/button-preview') {
        res.setHeader('Content-Type', 'text/html');
        const button = '<div class="article-button" data-nstarter-content-item data-nstarter-content-type="button"><a class="button button--coral" href="https://example.org/donate">Donate</a></div>';
        res.end(`<!doctype html><html><head><style>.button{display:inline-block;padding:16px;background:coral;min-width:40px;min-height:20px}</style></head><body class="nstarter-editor-preview"><div data-nstarter-snapshot-root data-nstarter-content-builder>${button}<template data-nstarter-content-template="button">${button}</template></div></body></html>`);
    } else if (pathname === '/social-preview') {
        res.setHeader('Content-Type', 'text/html');
        const selected = state.social.split(',');
        const items = ['facebook', 'instagram', 'copy'].map(item => `<button data-cammino-social="${item}" ${selected.includes(item) ? '' : 'hidden'}>${item}</button>`).join('');
        res.end(`<!doctype html><html><body class="nstarter-editor-preview"><aside data-cammino-post-social data-nstarter-variable-section="cammino_post_social" data-nstarter-variable-label="Social links" data-nstarter-variable-type="text" data-nstarter-variable-control="social-picker" data-nstarter-variable-value="${state.social}">${items}</aside><div data-nstarter-snapshot-root><p>Post content</p></div></body></html>`);
    } else if (pathname === '/person-preview') {
        res.setHeader('Content-Type', 'text/html');
        res.end(`<!doctype html><html><body><div data-nstarter-snapshot-root><section data-nstarter-variable-section="about_people_count" data-nstarter-variable-control="repeat" data-nstarter-variable-type="number" data-nstarter-variable-value="1"><div class="contact-people" data-nstarter-variable-items><article class="contact-person" data-nstarter-variable-item><div class="contact-person__icon"><i class="fa-solid fa-user"></i></div><div><span>Original role</span><h3>Original name</h3><a href="mailto:team@example.org">team@example.org</a></div></article></div></section></div></body></html>`);
    } else if (pathname === '/preview') {
        res.setHeader('Content-Type', 'text/html');
        const url = new URL(req.url, 'http://localhost');
        const restored = url.searchParams.get('nstarter_restore_version') === 'previous'
            && url.searchParams.get('nstarter_restore_token') === state.token
            && url.searchParams.get('nstarter_restore_source') === 'home';
        const conflicts = JSON.stringify(state.conflicts).replaceAll('"', '&quot;');
        res.end(`<!doctype html><html><head><style>@font-face{font-family:PreviewFont;src:url('/font.woff2')}.new-layout{color:blue;font-family:PreviewFont}</style></head><body><div data-nstarter-snapshot-root data-nstarter-snapshot-source="home" data-nstarter-snapshot-token="${state.token}" data-nstarter-restored-version="${restored ? 'previous' : ''}" data-nstarter-merge-conflicts="${conflicts}"><header class="site-header">Header</header>${previewMarkup(restored ? state.previous : state.current)}<footer class="site-footer">Footer</footer></div></body></html>`);
    } else if (pathname === '/ajax') {
        const chunks = [];
        for await (const chunk of req) chunks.push(chunk);
        const form = await new Request('http://localhost/ajax', { method: 'POST', headers: req.headers, body: Buffer.concat(chunks) }).formData();
        requests.push(String(form.get('action')));
        res.setHeader('Content-Type', 'application/json');
        if (form.has('source') && (form.get('source') !== 'home' || form.get('snapshot_token') !== state.token)) {
            res.statusCode = 409;
            res.end(JSON.stringify({ success: false, data: { message: 'Reload the stale editor.' } }));
            return;
        }
        let data;
        switch (form.get('action')) {
        case 'nstarter_snapshot_history':
            data = { versions: [{ id: 'current', saved_at: 'Today', author_name: 'Editor' }, { id: 'previous', saved_at: 'Yesterday', author_name: 'Editor' }] };
            break;
        case 'nstarter_restore_snapshot_version':
            state.restores++;
            data = { versionId: 'previous', snapshotToken: state.token };
            break;
        case 'nstarter_save_snapshot':
            if (form.has('social_links')) state.social = form.get('social_links');
            state.saves.push(form.get('html'));
            state.current = form.get('html');
            state.token += '-saved';
            state.conflicts = [];
            data = { message: 'Saved', snapshotToken: state.token };
            break;
        case 'nstarter_regenerate_snapshot':
            state.current = '<h1 id="title">Default heading</h1>';
            state.token += '-reset';
            state.conflicts = [];
            data = { message: 'Reset' };
            break;
        default: throw new Error('Unexpected action: ' + form.get('action'));
        }
        res.end(JSON.stringify({ success: true, data }));
    } else if (pathname === '/person-editor') {
        res.setHeader('Content-Type', 'text/html');
        // Start the preview after editor.js has installed its load listener.
        res.end(fixture.replaceAll('/preview', '/person-preview')
            .replace('<iframe data-nstarter-frame src="/person-preview">', '<iframe data-nstarter-frame>')
            .replace('<script src="/editor.js">', personDialog + personMediaStub + '<script src="/editor.js">')
            .replace('</body>', '<script>document.querySelector("[data-nstarter-frame]").src="/person-preview";</script></body>'));
    } else {
        res.setHeader('Content-Type', 'text/html');
        res.end(pathname === '/button-editor' ? fixture.replaceAll('/preview', '/button-preview').replace('isPost:false', 'isPost:true') : pathname === '/social-editor' ? fixture.replaceAll('/preview', '/social-preview').replace('isPost:false', 'isPost:true') : fixture);
    }
});
server.listen(0, '127.0.0.1');
await once(server, 'listening');
const address = `http://127.0.0.1:${server.address().port}`;
const chrome = spawn(chromePath, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=0', '--user-data-dir=' + profile, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
let socket;
let checks = 0;
const errors = [];
const pause = (ms) => new Promise((resolvePause) => setTimeout(resolvePause, ms));
try {
    const portFile = join(profile, 'DevToolsActivePort');
    for (let i = 0; i < 100 && !existsSync(portFile); i++) await pause(100);
    const port = readFileSync(portFile, 'utf8').split('\n')[0];
    const targets = await (await fetch(`http://127.0.0.1:${port}/json`)).json();
    socket = new WebSocket(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
    await new Promise((resolveOpen) => socket.addEventListener('open', resolveOpen, { once: true }));
    let id = 0;
    const pending = new Map();
    socket.addEventListener('message', (event) => {
        const message = JSON.parse(event.data);
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails);
        if (message.method === 'Network.requestWillBeSent' && message.params.type === 'Document') requests.push('Navigation ' + JSON.stringify(message.params.initiator));
        const request = pending.get(message.id);
        if (request) {
            pending.delete(message.id);
            message.error ? request.reject(message.error) : request.resolve(message.result);
        }
    });
    const call = (method, params = {}) => new Promise((resolveCall, reject) => {
        const requestId = ++id;
        const timeout = setTimeout(() => { pending.delete(requestId); reject(new Error('Browser command timed out: ' + method)); }, 10000);
        pending.set(requestId, { resolve: (value) => { clearTimeout(timeout); resolveCall(value); }, reject: (error) => { clearTimeout(timeout); reject(error); } });
        socket.send(JSON.stringify({ id: requestId, method, params }));
    });
    const evaluate = async (expression) => {
        const result = await call('Runtime.evaluate', { expression, returnByValue: true });
        if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
        return result.result.value;
    };
    const check = async (expression, label) => {
        let lastError;
        for (let i = 0; i < 60; i++) {
            try {
                if (await evaluate(expression)) { checks++; return; }
            } catch (error) { lastError = error; }
            await pause(100);
        }
        throw new Error(label + ' at ' + await evaluate('location.href') + '\nRequests: ' + requests.join(', ') + '\nBrowser errors: ' + JSON.stringify(errors) + '\nRestores: ' + state.restores + (lastError ? '\n' + lastError.message : ''));
    };
    await call('Runtime.enable');
    await call('Page.enable');
    await call('Network.enable');
    await call('Page.addScriptToEvaluateOnNewDocument', { source: 'window.confirmations=[];window.confirm=(message)=>{window.confirmations.push(message);return window.acceptConfirm!==false;};' });
    await call('Page.navigate', { url: address });
    await check(`document.querySelector('[data-nstarter-status]').classList.contains('is-error')`, 'Merge warning appears on load');
    await check(`window.confirm.toString().includes('confirmations')`, 'Confirmation stub is active');
    await evaluate(`document.querySelector('[data-nstarter-history]').click()`);
    await check(`document.querySelector('[data-nstarter-history-version]').options.length===2 && document.querySelector('[data-nstarter-history-dialog]').open`, 'History opens with version metadata');
    await check(`document.querySelector('[data-nstarter-history-restore]').disabled`, 'Current save cannot be restored over itself');
    await evaluate(`const versions=document.querySelector('[data-nstarter-history-version]');versions.selectedIndex=1;versions.dispatchEvent(new Event('change'))`);
    await check(`!document.querySelector('[data-nstarter-history-restore]').disabled`, 'Selecting a previous version enables restore');
    await evaluate(`document.querySelector('[data-nstarter-history-restore]').click()`);
    await check(`!document.querySelector('[data-nstarter-history-dialog]').open && document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('#title')?.textContent==='Older client heading'`, 'Restore reloads the latest layout with old content');
    if (state.restores !== 1) throw new Error('Restore endpoint was not called');
    if (!state.current.includes('Current client heading') || state.token !== 'token-1' || state.saves.length) throw new Error('Restore changed persisted content before Save');
    checks++;
    await check(`!document.querySelector('[data-nstarter-save]').disabled`, 'Restore finishes loading before Save is enabled');
    await evaluate(`window.acceptConfirm=false;document.querySelector('[data-nstarter-save]').click()`);
    await pause(150);
    if (state.saves.length !== 0) throw new Error('Cancelled conflict save wrote content');
    checks++;
    await evaluate(`window.acceptConfirm=true;const heading=document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('#title');heading.textContent='New client edit';heading.dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('[data-nstarter-save]').click()`);
    await check(`document.querySelector('[data-nstarter-status]').classList.contains('is-success')`, 'Save succeeds after review');
    if (!state.saves[0]?.includes('New client edit') || state.saves[0].includes('site-header') || state.saves[0].includes('site-footer')) throw new Error('Serialization did not isolate page content');
    checks++;
    await evaluate(`document.querySelector('[data-nstarter-save]').click()`);
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('[data-nstarter-snapshot-root]').dataset.nstarterSnapshotToken.endsWith('-saved-saved')`, 'Subsequent saves use the returned version token');
    state.token += '-external';
    await evaluate(`document.querySelector('[data-nstarter-save]').click()`);
    await check(`document.querySelector('[data-nstarter-status] strong').textContent==='Reload the stale editor.'`, 'Stale editor errors remain visible');
    if (state.saves.length !== 2) throw new Error('Stale save reached storage');
    await call('Page.navigate', { url: address });
    await check(`document.querySelector('[data-nstarter-frame]')?.contentDocument?.querySelector('#title')?.textContent==='New client edit'`, 'Reload retrieves current content');
    await evaluate(`document.querySelector('[data-nstarter-history]').click()`);
    await check(`document.querySelector('[data-nstarter-history-dialog]').open && !document.querySelector('[data-nstarter-regenerate]').disabled`, 'History offers Reset to defaults');
    await evaluate(`document.querySelector('[data-nstarter-regenerate]').click()`);
    await check(`!document.querySelector('[data-nstarter-history-dialog]').open && document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('#title')?.textContent==='Default heading'`, 'Reset from History closes the dialog and reloads defaults');
    await call('Page.navigate', { url: address + '/social-editor' });
    await check(`!!document.querySelector('[data-nstarter-frame]')?.contentDocument?.querySelector('[data-nstarter-variable-edit]')`, 'The post social section outside the saved body has a variable control');
    await evaluate(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('[data-nstarter-variable-edit]').click()`);
    await check(`document.querySelector('[data-nstarter-social-select]')?.options.length===8`, 'Social dropdown offers all combinations and Hide all');
    await evaluate(`document.querySelector('[data-nstarter-social-select]').value='instagram,copy';document.querySelector('[data-nstarter-variable-form]').requestSubmit()`);
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('[data-cammino-social="facebook"]').hidden && !document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('[data-cammino-social="instagram"]').hidden`, 'Applying a social choice immediately updates visibility');
    if (state.social !== 'facebook,instagram,copy') throw new Error('Social draft persisted before Save');
    await evaluate(`document.querySelector('[data-nstarter-save]').click()`);
    await check(`document.querySelector('[data-nstarter-status]').classList.contains('is-success')`, 'Saving a post persists social selection');
    if (state.social !== 'instagram,copy') throw new Error('Social selection was omitted from the save request');
    await call('Page.navigate', { url: address + '/social-editor' });
    await check(`document.querySelector('[data-nstarter-frame]')?.contentDocument?.querySelector('[data-cammino-social="facebook"]')?.hidden===true`, 'Saved visibility survives reloading');
    await evaluate(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('[data-nstarter-variable-edit]').click()`);
    await check(`document.querySelector('[data-nstarter-social-select]')?.value==='instagram,copy'`, 'The dropdown loads the saved selection');
    await evaluate(`document.querySelector('[data-nstarter-social-select]').value='';document.querySelector('[data-nstarter-variable-form]').requestSubmit();document.querySelector('[data-nstarter-save]').click()`);
    await check(`document.querySelector('[data-nstarter-status]').classList.contains('is-success')`, 'Hide all can be saved');
    if (state.social !== '') throw new Error('Hide all was not persisted');
    await call('Page.navigate', { url: address + '/button-editor' });
    await check(`document.querySelector('[data-nstarter-frame]')?.contentDocument?.querySelector('.article-button a')?.getAttribute('contenteditable')==='true'`, 'Button labels have their own editing host');
    const selectButton = async (position) => evaluate(`(() => {
        const doc=document.querySelector('[data-nstarter-frame]').contentDocument;
        const link=doc.querySelector('.article-button a');link.focus();
        const range=doc.createRange();range.selectNodeContents(link);
        ${position === 'all' ? '' : `range.collapse(${position === 'start'});`}
        doc.getSelection().removeAllRanges();doc.getSelection().addRange(range);
    })()`);
    const key = async (name) => {
        await call('Input.dispatchKeyEvent', { type: 'keyDown', key: name, code: name, windowsVirtualKeyCode: name === 'Backspace' ? 8 : 46 });
        await call('Input.dispatchKeyEvent', { type: 'keyUp', key: name, code: name, windowsVirtualKeyCode: name === 'Backspace' ? 8 : 46 });
    };
    await selectButton('end');
    await call('Input.insertText', { text: ' now' });
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.article-button').textContent==='Donate now' && document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.article-button a').textContent==='Donate now'`, 'Typing at the end stays inside the button');
    await selectButton('start');
    await call('Input.insertText', { text: 'Please ' });
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.article-button a').textContent==='Please Donate now'`, 'Typing at the start stays inside the button');
    await selectButton('all');
    await key('Backspace');
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.article-button a')?.textContent===''`, 'Deleting the whole label preserves the button');
    await key('Backspace');
    await key('Delete');
    await check(`!!document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.article-button a')`, 'Deleting again in an empty button preserves it');
    await evaluate(`document.querySelector('[data-nstarter-save]').click()`);
    await check(`document.querySelector('[data-nstarter-status]').classList.contains('is-success')`, 'An empty button can be saved');
    if (!state.saves.at(-1).includes('https://example.org/donate') || state.saves.at(-1).includes('contenteditable')) throw new Error('Saving an empty button lost its link or retained editor attributes');
    checks++;
    await selectButton('end');
    await call('Input.insertText', { text: 'X' });
    await key('Backspace');
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.article-button a')?.textContent===''`, 'Deleting the last character preserves the button');
    await call('Input.insertText', { text: 'New label' });
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.article-button a').textContent==='New label'`, 'An empty button accepts a new label');
    await evaluate(`document.querySelector('[data-nstarter-mode]').value='media';document.querySelector('[data-nstarter-mode]').dispatchEvent(new Event('change'))`);
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.article-button a').getAttribute('contenteditable')==='false'`, 'Button labels are locked outside text mode');
    await evaluate(`document.querySelector('[data-nstarter-mode]').value='text';document.querySelector('[data-nstarter-mode]').dispatchEvent(new Event('change'));document.querySelector('[data-nstarter-save]').click()`);
    await check(`document.querySelector('[data-nstarter-status]').classList.contains('is-success')`, 'Button changes save');
    const savedButton = state.saves.at(-1);
    if (!savedButton.includes('New label') || !savedButton.includes('https://example.org/donate') || savedButton.includes('contenteditable') || savedButton.includes('data-nstarter-editor-runtime')) throw new Error('Saved button lost content or retained editor attributes');
    checks++;
    await evaluate(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('[data-nstarter-inline-action="add-button"]').click()`);
    await check(`Array.from(document.querySelector('[data-nstarter-frame]').contentDocument.querySelectorAll('[data-nstarter-content-item] a')).length===2 && Array.from(document.querySelector('[data-nstarter-frame]').contentDocument.querySelectorAll('[data-nstarter-content-item] a')).every(link=>link.getAttribute('contenteditable')==='true')`, 'New buttons also have isolated editable labels');
    await call('Input.insertText', { text: ' today' });
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelectorAll('[data-nstarter-content-item] a')[1].textContent==='Donate today'`, 'New buttons focus their label and keep typing inside');
    await evaluate(`document.querySelector('[data-nstarter-save]').click()`);
    await check(`document.querySelector('[data-nstarter-status]').classList.contains('is-success')`, 'Save button draft before navigating to the team fixture');
    await call('Page.navigate', { url: address + '/person-editor' });
    await check(`!!document.querySelector('[data-nstarter-frame]')?.contentDocument?.querySelector('[data-nstarter-person-edit]')`, 'Team members have an edit control');
    await evaluate(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('[data-nstarter-person-edit]').click()`);
    await check(`document.querySelector('[data-nstarter-person-dialog]').matches(':modal')`, 'Team editor opens as a native modal');
    await evaluate(`const form=document.querySelector('[data-nstarter-person-form]');form.elements.name.value='Draft name';form.elements.role.value='Draft role';form.elements.media_type.value='image';form.elements.media_type.dispatchEvent(new Event('change'));form.elements.image_url.value='https://example.org/original.jpg'`);
    const clickMediaButton = async (selector) => {
        const reachable = await evaluate(`(() => {const button=document.querySelector('${selector}');const rect=button.getBoundingClientRect();return document.elementFromPoint(rect.x+rect.width/2,rect.y+rect.height/2)===button;})()`);
        if (!reachable) throw new Error('Media button is covered: ' + selector);
        checks++;
        await evaluate(`document.querySelector('${selector}').click()`);
    };
    for (const selectBeforeClose of [false, true]) {
        await evaluate(`window.selectBeforeClose=${selectBeforeClose};document.querySelector('[data-nstarter-person-image-picker]').click()`);
        await check(`!document.querySelector('[data-nstarter-person-dialog]').open && document.activeElement.id==='choose-photo'`, 'Library receives focus without a blocking native dialog');
        await check(`window.pickerOptions.library.type==='image' && window.pickerOptions.multiple===false`, 'Team photo picker accepts a single image');
        await clickMediaButton('#choose-photo');
        await check(`document.querySelector('[data-nstarter-person-dialog]').matches(':modal') && document.querySelector('[data-nstarter-person-form]').elements.image_url.value==='https://example.org/team-photo.jpg'`, 'Selecting a photo restores the form in either event order');
        await check(`document.querySelector('[data-nstarter-person-form]').elements.name.value==='Draft name' && document.querySelector('[data-nstarter-person-form]').elements.role.value==='Draft role'`, 'Photo selection preserves unsaved member fields');
    }
    for (const cancelWithEscape of [false, true]) {
        await evaluate(`document.querySelector('[data-nstarter-person-image-picker]').click()`);
        await check(`!document.querySelector('[data-nstarter-person-dialog]').open && !!document.querySelector('#cancel-photo')`, 'Library reopens for another choice');
        if (cancelWithEscape) {
            await call('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            await call('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
        } else {
            await clickMediaButton('#cancel-photo');
        }
        await check(`document.querySelector('[data-nstarter-person-dialog]').matches(':modal') && document.activeElement.name==='image_url' && document.querySelector('[data-nstarter-person-form]').elements.image_url.value==='https://example.org/team-photo.jpg'`, 'Cancelling the library restores focus and preserves the photo');
    }
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.contact-person h3').textContent==='Original name' && !document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.contact-person img')`, 'Library changes remain draft until Apply');
    await evaluate(`document.querySelector('[data-nstarter-person-form]').requestSubmit()`);
    await check(`!document.querySelector('[data-nstarter-person-dialog]').open && document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.contact-person h3').textContent==='Draft name' && document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('.contact-person img')?.src==='https://example.org/team-photo.jpg'`, 'Apply updates the member and selected photo after library use');
    if (errors.length) throw new Error(JSON.stringify(errors));
    console.log(`Passed ${checks} editor history browser checks.`);
} finally {
    socket?.close();
    chrome.kill();
    if (chrome.exitCode === null) await Promise.race([once(chrome, 'exit'), pause(5000)]);
    server.closeAllConnections();
    await Promise.race([new Promise((resolveClose) => server.close(resolveClose)), pause(1000)]);
    // Delete only the verified temporary profile created by this test.
    const target = realpathSync(profile);
    if (dirname(target).toLowerCase() !== resolve(tmpdir()).toLowerCase() || !basename(target).startsWith('cammino-editor-history-')) throw new Error('Unexpected browser profile path');
    rmSync(target, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
}
