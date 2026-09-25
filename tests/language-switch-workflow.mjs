import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../assets/js/cammino-shell.js', import.meta.url), 'utf8');
const header = readFileSync(new URL('../functions.php', import.meta.url), 'utf8');
assert.match(header, /data-language-switcher/);
assert.match(header, /translate\.google\.com\/translate/);

const siteUrl = 'https://ozcammino.sk/';
const originalUrl = 'https://ozcammino.sk/podujatia/?cammino_lang=sk';
function load(url, storage = new Map(), preview = false, pageUrl = 'https://ozcammino.sk/podujatia/') {
  const location = new URL(url);
  const actions = [];
  const links = Object.fromEntries(['sk', 'en'].map((code) => [code, {
    attributes: new Map(),
    listeners: new Map(),
    setAttribute(name, value) { this.attributes.set(name, value); },
    removeAttribute(name) { this.attributes.delete(name); },
    addEventListener(name, listener) { this.listeners.set(name, listener); },
    click() {
      const event = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
      this.listeners.get('click')?.(event);
      return event;
    },
  }]));
  const switcher = {
    dataset: {
      siteUrl,
      originalUrl: `${pageUrl}?cammino_lang=sk`,
      englishUrl: `https://translate.google.com/translate?sl=sk&tl=en&u=${encodeURIComponent(pageUrl)}`,
    },
    querySelector(selector) { return links[selector.match(/data-language="(sk|en)"/)[1]]; },
  };
  const document = {
    cookie: '',
    body: { classList: { contains(name) { return preview && name === 'nstarter-editor-preview'; } } },
    querySelector(selector) { return selector === '[data-language-switcher]' ? switcher : null; },
    addEventListener() {},
  };
  const window = {
    location: {
      origin: location.origin,
      search: location.search,
      href: location.href,
      replace(value) { actions.push(['replace', value]); },
      assign(value) { actions.push(['assign', value]); },
    },
    history: { state: null, replaceState(_state, _title, value) { actions.push(['clean', String(value)]); } },
    localStorage: {
      getItem(key) { return storage.get(key) ?? null; },
      setItem(key, value) { storage.set(key, value); },
    },
  };
  runInNewContext(source, { document, window, URL, URLSearchParams, HTMLImageElement: class {} });
  return { links, actions, storage };
}

const storage = new Map();
const first = load('https://ozcammino.sk/podujatia/', storage);
assert.equal(first.links.sk.attributes.get('aria-current'), 'true');
assert.deepEqual(first.actions, []);
assert.equal(first.links.en.click().defaultPrevented, false);
assert.equal(storage.get('cammino-language'), 'en');

const next = load('https://ozcammino.sk/kontakt/', storage, false, 'https://ozcammino.sk/kontakt/');
assert.deepEqual(next.actions, [['replace', 'https://translate.google.com/translate?sl=sk&tl=en&u=https%3A%2F%2Fozcammino.sk%2Fkontakt%2F']]);

const translated = load('https://ozcammino-sk.translate.goog/podujatia/?_x_tr_tl=en', storage);
assert.equal(translated.links.en.attributes.get('aria-current'), 'true');
assert.equal(translated.links.sk.click().defaultPrevented, true);
assert.deepEqual(translated.actions, [['assign', originalUrl]]);

const restored = load(originalUrl, storage);
assert.equal(storage.get('cammino-language'), 'sk');
assert.equal(restored.links.sk.attributes.get('aria-current'), 'true');
assert.equal(restored.actions[0][0], 'clean');
assert.equal(restored.actions.some(([action]) => action === 'replace'), false);

storage.set('cammino-language', 'en');
assert.deepEqual(load('https://ozcammino.sk/podujatia/?nstarter_preview=1', storage, true).actions, []);

console.log('Passed language switch workflow checks.');
