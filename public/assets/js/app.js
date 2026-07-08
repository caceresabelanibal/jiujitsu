// Helpers generales
function copyLink(inputId, btn) {
  const el = document.getElementById(inputId);
  el.select();
  navigator.clipboard.writeText(el.value).then(() => {
    const old = btn.textContent;
    btn.textContent = btn.dataset.copied || 'OK';
    setTimeout(() => (btn.textContent = old), 1500);
  });
}

document.querySelectorAll('[data-confirm]').forEach((f) => {
  f.addEventListener('submit', (e) => {
    if (!confirm(f.dataset.confirm)) e.preventDefault();
  });
});
