document.addEventListener('DOMContentLoaded', () => {
  // Classic editor: categories are a radio group and new categories have no parent.
  const categories = document.getElementById('categorydiv');
  const makeSingleChoice = () => document.querySelectorAll('#categorychecklist input[type="checkbox"], #categorychecklist-pop input[type="checkbox"]').forEach((input) => {
      input.type = 'radio';
    });
  makeSingleChoice();
  if (categories) new MutationObserver(makeSingleChoice).observe(categories, { childList: true, subtree: true });
  const parent = document.getElementById('newcategory_parent');
  if (parent) {
    parent.value = '0';
    parent.hidden = true;
  }
  const select = document.getElementById('cammino-post-placement');
  if (!select) return;
  const update = () => document.querySelectorAll('[data-cammino-fields]').forEach((group) => {
    group.hidden = group.dataset.camminoFields !== select.value;
  });
  select.addEventListener('change', update);
  update();
});
