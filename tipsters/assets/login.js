(() => {
  const input = document.getElementById('tipster-password');
  const toggle = document.querySelector('[data-password-toggle]');
  if (!input || !toggle) return;
  toggle.hidden = false;
  toggle.addEventListener('click', () => {
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(visible));
    toggle.textContent = visible ? 'Skryť heslo' : 'Zobraziť heslo';
  });
  input.form.addEventListener('submit', () => { input.type = 'password'; });
})();
