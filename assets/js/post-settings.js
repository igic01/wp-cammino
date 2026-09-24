document.addEventListener('DOMContentLoaded', () => {
  const select = document.getElementById('cammino-post-placement');
  if (!select) return;
  const update = () => document.querySelectorAll('[data-cammino-fields]').forEach((group) => {
    group.hidden = group.dataset.camminoFields !== select.value;
  });
  select.addEventListener('change', update);
  update();
});
