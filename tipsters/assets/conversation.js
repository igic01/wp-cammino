/* Incremental private conversations. No framework, page reload or overlapping polls. */
(() => {
  'use strict';
  const root = document.querySelector('.cammino-conversation[data-endpoint]');
  if (!root || !window.fetch) return;
  const form = root.querySelector('[data-message-form]');
  const textarea = form.querySelector('textarea');
  const button = form.querySelector('button[type=submit]');
  const status = root.querySelector('[data-live-status]');
  const live = root.querySelector('[data-live-messages]');
  const list = live.querySelector('ol');
  const scroller = root.querySelector('[data-chat-scroll]');
  const unreadButton = root.querySelector('[data-new-messages]');
  let unread = 0, followLatest = scroller.dataset.latest === 'yes';
  function toLatest() {
    scroller.scrollTop = scroller.scrollHeight;
    followLatest = true; unread = 0; unreadButton.hidden = true;
  }
  unreadButton.addEventListener('click', toLatest);
  scroller.addEventListener('scroll', () => {
    followLatest = scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight < 70;
    if (followLatest) { unread = 0; unreadButton.hidden = true; }
  });
  if (followLatest) requestAnimationFrame(toLatest);
  const seen = new Set([...root.querySelectorAll('[data-message-id]')].map(node => Number(node.dataset.messageId)));
  let cursor = Number(root.dataset.cursor), timer, controller;
  let stopped = false, busy = false, sending = false, writable = !form.hidden, idle = 0, failures = 0;
  let pollFinished = Promise.resolve(), finishPoll, connectionError = false;
  let pendingSend = null;
  const trackPendingEdit = () => {
    if (pendingSend && (textarea.selectionStart < pendingSend.body.length || !textarea.value.startsWith(pendingSend.body))) pendingSend.edited = true;
  };
  textarea.addEventListener('beforeinput', trackPendingEdit);
  textarea.addEventListener('input', trackPendingEdit);
  let feedbackTimer;
  function showStatus(text, error = false) {
    clearTimeout(feedbackTimer);
    status.classList.toggle('is-error', error);
    status.textContent = text;
    if (!error) feedbackTimer = setTimeout(() => { status.textContent = ''; }, 3500);
  }
  // Keep mobile Enter available for line breaks; desktop keyboard users can send directly.
  const desktopKeyboard = window.matchMedia('(hover: hover) and (pointer: fine)');
  const keyboardHelp = root.querySelector('[data-keyboard-help]');
  const updateKeyboardHelp = () => { keyboardHelp.hidden = !desktopKeyboard.matches; };
  updateKeyboardHelp(); desktopKeyboard.addEventListener('change', updateKeyboardHelp);
  textarea.addEventListener('keydown', event => {
    if (!desktopKeyboard.matches || event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey || event.isComposing || event.keyCode === 229) return;
    event.preventDefault();
    if (!event.repeat && !sending && !stopped && writable) form.requestSubmit();
  });

  function availability(value) {
    writable = value;
    form.hidden = !value;
    form.elements.operation.value = value ? 'send_message' : '';
    root.querySelector('[data-closed]').hidden = value;
    button.disabled = !value || sending || stopped;
  }
  function append(data, sent = false) {
    availability(data.can_send);
    root.querySelector('[data-chat-state]').textContent = data.can_send ? 'Otvorená' : (data.status_label || 'Uzamknutá');
    document.querySelector('[data-tip-status]')?.replaceChildren(document.createTextNode(data.status_label || ''));
    document.querySelector('[data-tip-notice]')?.replaceChildren(document.createTextNode(data.status_notice || ''));
    // A status change locks any open content form immediately; version checks still protect its save.
    if (data.status && data.status !== 'discussion') {
      const edit = document.querySelector('input[name=operation][value=edit_tip]')?.form;
      edit?.querySelectorAll('input:not([type=hidden]),textarea,button[type=submit]').forEach(control => { control.disabled = true; });
    }
    const fragment = document.createDocumentFragment();
    let added = 0;
    for (const message of data.messages || []) {
      if (seen.has(message.id)) continue;
      seen.add(message.id); ++added;
      const item = document.createElement('li'); item.dataset.messageId = message.id;
      item.className = message.own ? 'is-own' : 'is-other';
      const sender = document.createElement('strong'); sender.textContent = message.sender;
      const time = document.createElement('time'); time.dateTime = message.datetime; time.textContent = message.time;
      const body = document.createElement('div'); body.className = 'cammino-conversation__text'; body.textContent = message.body;
      item.append(sender, time, body); fragment.append(item);
    }
    list.append(fragment);
    if (added) {
      live.hidden = false;
      root.querySelector('[data-empty]')?.remove();
      if (sent || followLatest) { toLatest(); }
      else {
        unread += added; unreadButton.hidden = false;
        unreadButton.textContent = `Nové správy: ${unread} · Prejsť nadol`;
      }
      if (unreadButton.hidden) showStatus('Prišli nové správy.');
    }
    // Bound live DOM and duplicate tracking; older messages remain in paginated history.
    while (list.children.length > 200) list.firstElementChild.remove();
    if (seen.size > 300) {
      seen.clear();
      root.querySelectorAll('[data-message-id]').forEach(node => seen.add(Number(node.dataset.messageId)));
    }
    cursor = Math.max(cursor, Number(data.cursor));
    return added;
  }
  function schedule(delay) {
    clearTimeout(timer);
    if (!stopped && !sending && !document.hidden && navigator.onLine) timer = setTimeout(poll, delay);
  }
  async function request(operation, extra = {}) {
    controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(root.dataset.endpoint, {
        method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
        body: new URLSearchParams({action: 'cammino_conversation', tip_id: root.dataset.tip,
          _wpnonce: root.dataset.nonce, operation, after: String(cursor), ...extra})
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        const error = new Error(result.data?.message || 'Správu sa nepodarilo načítať. Skúste to znova.');
        error.code = response.status; error.data = result.data; throw error;
      }
      failures = 0; return result.data;
    } finally { clearTimeout(timeout); controller = null; }
  }
  function errorState(error, send = false) {
    if (error.code === 403 || error.code === 404) {
      stopped = true; availability(false);
      // Remove private content after the current session loses access.
      root.querySelectorAll('.cammino-conversation__messages').forEach(node => node.replaceChildren());
      unread = 0; unreadButton.hidden = true;
    }
    if (typeof error.data?.can_send === 'boolean') availability(error.data.can_send);
    connectionError = !error.code || error.code >= 500;
    showStatus(error.message && error.code ? error.message :
      (send ? 'Odoslanie sa nepodarilo potvrdiť. Skúste znova; správa sa neodošle dvakrát.' : 'Spojenie bolo prerušené. Nové správy sa načítajú po obnovení spojenia.'), true);
  }
  async function poll() {
    if (busy || sending || stopped || document.hidden || !navigator.onLine) return;
    busy = true;
    pollFinished = new Promise(resolve => { finishPoll = resolve; });
    let more = false;
    try {
      const data = await request('poll');
      if (connectionError) { showStatus('Spojenie bolo obnovené.'); connectionError = false; }
      idle = append(data) ? 0 : idle + 1; more = data.more;
    } catch (error) {
      if (error.name !== 'AbortError' || (!document.hidden && !sending)) { ++failures; errorState(error); }
    } finally {
      busy = false;
      finishPoll();
      schedule(more ? 250 : failures ? Math.min(60000, 5000 * 2 ** Math.min(failures, 4)) :
        document.activeElement === textarea ? 5000 : Math.min(15000, 5000 + idle * 2500));
    }
  }
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (sending || stopped || !writable) return;
    // An uncertain response must retry the same payload/token, even if typing continued.
    const attempt = pendingSend || {body: textarea.value, token: form.elements.message_token.value, edited: false, uncertain: false};
    pendingSend = attempt;
    const {body, token} = attempt;
    sending = true; clearTimeout(timer); button.disabled = true;
    controller?.abort(); await pollFinished;
    if (stopped || !writable) { if (!attempt.uncertain) pendingSend = null; sending = false; button.disabled = true; return; }
    busy = true;
    showStatus('Odosielanie…');
    try {
      const data = await request('send_message', {message_body: body, message_token: token});
      append(data, true); form.elements.message_token.value = data.message_token;
      // Remove the confirmed message even when typing continued while it was in flight.
      // Keep appended text as the next draft; preserve a deliberately replaced/edited draft.
      if (!attempt.edited && textarea.value.startsWith(body)) {
        const start = Math.max(0, textarea.selectionStart - body.length), end = Math.max(0, textarea.selectionEnd - body.length);
        const direction = textarea.selectionDirection;
        textarea.value = textarea.value.slice(body.length);
        textarea.setSelectionRange(start, end, direction);
      }
      pendingSend = null;
      showStatus('Správa bola odoslaná.'); idle = 0;
    } catch (error) {
      if (!error.code || error.code >= 500) attempt.uncertain = true;
      if (!attempt.uncertain) pendingSend = null;
      errorState(error, true);
    }
    finally { busy = sending = false; button.disabled = !writable || stopped; schedule(250); }
  });
  document.addEventListener('visibilitychange', () => {
    clearTimeout(timer);
    if (document.hidden) { if (!sending) controller?.abort(); }
    else { idle = 0; schedule(0); }
  });
  window.addEventListener('offline', () => { clearTimeout(timer); if (!sending) controller?.abort(); });
  window.addEventListener('online', () => { idle = failures = 0; schedule(0); });
  window.addEventListener('pagehide', () => { clearTimeout(timer); clearTimeout(feedbackTimer); controller?.abort(); });
  schedule(0);
})();
