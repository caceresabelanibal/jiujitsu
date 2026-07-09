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

// Tema claro / oscuro
function toggleTheme() {
  const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
  document.documentElement.dataset.theme = next;
  localStorage.setItem('theme', next);
}

// Aparicion al hacer scroll (landing)
const revealEls = document.querySelectorAll('.reveal');
if (revealEls.length && 'IntersectionObserver' in window) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add('in');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  revealEls.forEach((el) => io.observe(el));
} else {
  revealEls.forEach((el) => el.classList.add('in'));
}

// Contador animado de estadisticas
document.querySelectorAll('[data-count]').forEach((el) => {
  const target = parseInt(el.dataset.count, 10) || 0;
  if (target < 5) return;
  const dur = 1200;
  const t0 = performance.now();
  el.textContent = '0';
  function tick(now) {
    const p = Math.min(1, (now - t0) / dur);
    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
});
