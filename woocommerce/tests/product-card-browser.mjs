/** Run with Node 24 and Chrome installed; PHP_BIN and BROWSER_BIN are optional. */
import { createServer } from 'node:http';
import { spawn, spawnSync } from 'node:child_process';
import { once } from 'node:events';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, realpathSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const php = process.env.PHP_BIN || 'C:/xampp/php/php.exe';
const chromePath = process.env.BROWSER_BIN || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const singleProduct = process.argv.includes('--single-product');
const fixture = singleProduct ? { status: 0, stdout: readFileSync(join(root, 'woocommerce/tests/single-product-preview.html'), 'utf8') } : spawnSync(php, [join(root, 'woocommerce/tests/product-card-workflow.php'), '--preview'], { encoding: 'utf8', windowsHide: true });
if (fixture.status !== 0) throw new Error(fixture.stderr);
const server = createServer((req, res) => {
    const pathname = new URL(req.url, 'http://localhost').pathname;
    if (pathname === '/') { res.setHeader('Content-Type', 'text/html; charset=utf-8'); res.end(fixture.stdout); return; }
    const target = resolve(root, '.' + pathname);
    if (!target.startsWith(root + '\\') && !target.startsWith(root + '/')) { res.writeHead(403); res.end(); return; }
    if (!existsSync(target)) { res.writeHead(404); res.end(); return; }
    res.setHeader('Content-Type', pathname.endsWith('.css') ? 'text/css' : pathname.endsWith('.webp') ? 'image/webp' : 'application/octet-stream');
    res.end(readFileSync(target));
});
server.listen(0, '127.0.0.1'); await once(server, 'listening');
const profile = mkdtempSync(join(tmpdir(), 'cammino-product-cards-'));
const chrome = spawn(chromePath, ['--headless=new', '--disable-gpu', '--no-first-run', '--remote-debugging-port=0', '--user-data-dir=' + profile, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const pause = ms => new Promise(resolvePause => setTimeout(resolvePause, ms));
let socket;
try {
    const portFile = join(profile, 'DevToolsActivePort');
    for (let n = 0; n < 100 && !existsSync(portFile); n++) await pause(100);
    const port = readFileSync(portFile, 'utf8').split('\n')[0];
    const targets = await (await fetch(`http://127.0.0.1:${port}/json`)).json();
    socket = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
    await once(socket, 'open');
    let id = 0;
    const pending = new Map();
    socket.addEventListener('message', event => {
        const result = JSON.parse(event.data), request = pending.get(result.id);
        if (request) { pending.delete(result.id); result.error ? request.reject(result.error) : request.resolve(result.result); }
    });
    const call = (method, params = {}) => new Promise((resolveCall, reject) => {
        const requestId = ++id;
        const timeout = setTimeout(() => { pending.delete(requestId); reject(new Error(method + ' timed out')); }, 10000);
        pending.set(requestId, { resolve: value => { clearTimeout(timeout); resolveCall(value); }, reject: error => { clearTimeout(timeout); reject(error); } });
        socket.send(JSON.stringify({ id: requestId, method, params }));
    });
    await call('Page.enable');
    let checks = 0;
    for (const width of [1440, 390]) {
        await call('Emulation.setDeviceMetricsOverride', { width, height: 1100, deviceScaleFactor: 1, mobile: false });
        await call('Page.navigate', { url: `http://127.0.0.1:${server.address().port}/` });
        let ready = false;
        for (let n = 0; n < 100 && !ready; n++) {
            const result = await call('Runtime.evaluate', { expression: 'document.readyState === "complete"', returnByValue: true });
            ready = result.result.value; if (!ready) await pause(100);
        }
        const expression = singleProduct ? `(async () => {
            await document.fonts.ready;
            const product = document.querySelector('.product');
            const gallery = product.querySelector('.woocommerce-product-gallery').getBoundingClientRect();
            const summary = product.querySelector('.summary').getBoundingClientRect();
            const tabs = product.querySelector('.woocommerce-tabs').getBoundingClientRect();
            return {
                noOverflow: document.documentElement.scrollWidth <= innerWidth,
                columns: getComputedStyle(product).gridTemplateColumns.split(' ').length,
                cardsValid: (innerWidth > 850 ? summary.left >= gallery.right : summary.top >= gallery.bottom)
                    && tabs.top >= Math.max(gallery.bottom, summary.bottom)
                    && getComputedStyle(document.querySelector('.single-product-category')).display === 'none'
                    && document.querySelector('.single_add_to_cart_button').getBoundingClientRect().height >= 48
                    && getComputedStyle(document.querySelector('.zoomImg')).width === '901px'
            };
        })()` : `(async () => {
            await document.fonts.ready;
            const cards = [...document.querySelectorAll('.cammino-product-card')];
            return {
                noOverflow: document.documentElement.scrollWidth <= innerWidth,
                columns: getComputedStyle(document.querySelector('.products')).gridTemplateColumns.split(' ').length,
                cardsValid: cards.length === 4 && cards.every(card => {
                    const image = card.querySelector('.cammino-product-card__image').getBoundingClientRect();
                    const title = card.querySelector('h2').getBoundingClientRect();
                    const action = card.querySelector('.button').getBoundingClientRect();
                    const bounds = card.getBoundingClientRect();
                    return title.top >= image.bottom && action.top >= title.bottom && action.right <= bounds.right
                        && card.querySelectorAll('.button').length === 1
                        && getComputedStyle(card.querySelector('img')).objectFit === 'contain';
                })
            };
        })()`;
        const result = await call('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        const layout = result.result.value;
        if (!layout.noOverflow || !layout.cardsValid || layout.columns !== (width > 850 ? 2 : 1)) throw new Error(`Layout failed at ${width}px: ${JSON.stringify(layout)}`);
        checks += 3;
        if (process.env.PREVIEW_DIR) {
            mkdirSync(process.env.PREVIEW_DIR, { recursive: true });
            const screenshot = await call('Page.captureScreenshot', { format: 'png' });
            writeFileSync(join(process.env.PREVIEW_DIR, `${singleProduct ? 'single-product' : 'products'}-${width}.png`), Buffer.from(screenshot.data, 'base64'));
        }
    }
    console.log(`Passed ${checks} desktop/mobile ${singleProduct ? 'product detail' : 'product card'} browser checks.`);
} finally {
    socket?.close(); chrome.kill();
    if (chrome.exitCode === null) await Promise.race([once(chrome, 'exit'), pause(5000)]);
    server.closeAllConnections(); server.close();
    const target = realpathSync(profile);
    if (dirname(target).toLowerCase() !== resolve(tmpdir()).toLowerCase() || !basename(target).startsWith('cammino-product-cards-')) throw new Error('Unexpected temporary profile path');
    rmSync(target, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
}
