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
    saves: [], restores: 0, conflicts: ['p#removed: saved content could not be matched']
};
const previewMarkup = (html) => '<main id="main-content"><section id="hero"><h2 id="title" class="new-layout">'
    + (html.match(/<h[12][^>]*>(.*?)<\/h[12]>/s)?.[1] || 'Default heading') + '</h2></section></main>';
const fixture = `<!doctype html><html><head><link rel="stylesheet" href="/editor.css"></head><body class="nstarter-editor-shell">
<div data-nstarter-loading></div><iframe data-nstarter-frame src="/preview"></iframe>
<aside class="nstarter-editor-panel"><div data-nstarter-status><strong>Ready</strong></div><button data-nstarter-panel-toggle><span>−</span></button>
<select data-nstarter-mode><option value="text">Text</option><option value="media">Media</option></select>
<button data-nstarter-save>Save</button><a data-nstarter-view>View</a><button data-nstarter-regenerate>Reset</button><button data-nstarter-history>History</button></aside>
<dialog data-nstarter-video-dialog><form data-nstarter-video-form></form><button data-nstarter-video-cancel></button></dialog>
<dialog data-nstarter-variable-dialog><form data-nstarter-variable-form></form><button data-nstarter-variable-cancel></button></dialog>
<dialog data-nstarter-history-dialog class="nstarter-history-dialog"><select data-nstarter-history-version></select><input type="checkbox" data-nstarter-history-original>
<p data-nstarter-history-status></p><iframe data-nstarter-history-preview sandbox="allow-same-origin"></iframe><button data-nstarter-history-close>Close</button><button data-nstarter-history-restore disabled>Restore</button></dialog>
<script>window.nstarterEditor={ajaxUrl:'/ajax',nonce:'test',postId:1,source:'home',previewUrl:'/preview',isPost:false,strings:{
confirmSaveConflicts:'Review unmatched content. Save anyway?',mergeWarning:'Review saved content in History.',confirmRestore:'Restore content?',confirmRegenerate:'Reset content?',
noHistory:'No saved versions.',currentVersion:'Current save',loadingHistory:'Loading',saved:'Saved',unsaved:'Unsaved',error:'Failed',regenerated:'Reset'}};</script>
<script src="/editor.js"></script></body></html>`.replaceAll('<button ', '<button type="button" ');
const requests = [];

const server = createServer(async (req, res) => {
    const pathname = new URL(req.url, 'http://localhost').pathname;
    requests.push(req.method + ' ' + req.url);
    if (pathname === '/editor.js' || pathname === '/editor.css') {
        res.setHeader('Content-Type', pathname.endsWith('.js') ? 'text/javascript' : 'text/css');
        res.end(readFileSync(join(root, 'assets', pathname.endsWith('.js') ? 'js/editor.js' : 'css/editor.css')));
    } else if (pathname === '/font.woff2') {
        res.setHeader('Content-Type', 'font/woff2');
        res.end(readFileSync(join(root, 'assets/fonts/fredoka.woff2')));
    } else if (pathname === '/preview') {
        res.setHeader('Content-Type', 'text/html');
        const conflicts = JSON.stringify(state.conflicts).replaceAll('"', '&quot;');
        res.end(`<!doctype html><html><head><style>@font-face{font-family:PreviewFont;src:url('/font.woff2')}.new-layout{color:blue;font-family:PreviewFont}</style></head><body><div data-nstarter-snapshot-root data-nstarter-snapshot-source="home" data-nstarter-snapshot-token="${state.token}" data-nstarter-merge-conflicts="${conflicts}"><header class="site-header">Header</header>${previewMarkup(state.current)}<footer class="site-footer">Footer</footer></div></body></html>`);
    } else if (pathname === '/ajax') {
        const chunks = [];
        for await (const chunk of req) chunks.push(chunk);
        const form = await new Request('http://localhost/ajax', { method: 'POST', headers: req.headers, body: Buffer.concat(chunks) }).formData();
        requests.push(String(form.get('action')));
        res.setHeader('Content-Type', 'application/json');
        if (form.get('source') !== 'home' || form.get('snapshot_token') !== state.token) {
            res.statusCode = 409;
            res.end(JSON.stringify({ success: false, data: { message: 'Reload the stale editor.' } }));
            return;
        }
        let data;
        switch (form.get('action')) {
        case 'nstarter_snapshot_history':
            data = { versions: [{ id: 'current', saved_at: 'Today', author_name: 'Editor' }, { id: 'previous', saved_at: 'Yesterday', author_name: 'Editor' }] };
            break;
        case 'nstarter_preview_snapshot_version': {
            const html = form.get('version_id') === 'current' ? state.current : state.previous;
            data = { html: previewMarkup(html), savedHtml: html, conflicts: state.conflicts };
            break;
        }
        case 'nstarter_restore_snapshot_version':
            state.current = state.previous;
            state.restores++;
            state.token += '-restored';
            data = { message: 'Content restored', snapshotToken: state.token };
            break;
        case 'nstarter_save_snapshot':
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
    } else {
        res.setHeader('Content-Type', 'text/html');
        res.end(fixture);
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
    await check(`document.querySelector('[data-nstarter-history-version]').options.length===2 && !!document.querySelector('[data-nstarter-history-preview]').srcdoc`, 'History opens and previews current content');
    await check(`document.querySelector('[data-nstarter-history-restore]').disabled`, 'Current save cannot be restored over itself');
    await check(`Array.from(document.querySelector('[data-nstarter-history-preview]').contentDocument.fonts).some(font=>font.status==='loaded')`, 'History previews can load the theme’s fonts');
    await evaluate(`document.querySelector('[data-nstarter-history-original]').click()`);
    await check(`document.querySelector('[data-nstarter-history-preview]').srcdoc.includes('Recover this copy')`, 'Original HTML exposes unmatched content');
    await evaluate(`const versions=document.querySelector('[data-nstarter-history-version]');versions.selectedIndex=1;versions.dispatchEvent(new Event('change'))`);
    await check(`!document.querySelector('[data-nstarter-history-restore]').disabled && document.querySelector('[data-nstarter-history-preview]').srcdoc.includes('Older client heading')`, 'Previous version preview enables restore');
    await check(`!document.querySelector('[data-nstarter-history-preview]').sandbox.contains('allow-scripts') && !window.previewScriptRan`, 'History previews cannot execute saved scripts');
    await evaluate(`document.querySelector('[data-nstarter-history-restore]').click()`);
    await check(`!document.querySelector('[data-nstarter-history-dialog]').open && document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('#title')?.textContent==='Older client heading'`, 'Restore reloads the latest layout with old content');
    if (state.restores !== 1) throw new Error('Restore endpoint was not called');
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
    await evaluate(`document.querySelector('[data-nstarter-regenerate]').click()`);
    await check(`document.querySelector('[data-nstarter-frame]').contentDocument.querySelector('#title')?.textContent==='Default heading'`, 'Reset reloads defaults');
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
