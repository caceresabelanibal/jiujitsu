/**
 * Rotador de publicidades (window.ADS).
 * Animaciones: slide (carrusel), fade (materializa), zoom, ticker (cinta continua).
 * Cada aviso define su duracion y su animacion.
 */
(function () {
  const ads = window.ADS || [];
  const bar = document.getElementById('adsbar');
  if (!ads.length || !bar) return;
  document.body.classList.add('has-ads');
  let i = -1;

  function build(ad) {
    const el = document.createElement('div');
    el.className = 'ad ad-' + ad.animation;
    if (ad.type === 'image' && ad.image) {
      const img = document.createElement('img');
      img.src = ad.image;
      img.alt = ad.title || '';
      el.appendChild(img);
      if (ad.title) {
        const cap = document.createElement('span');
        cap.className = 'ad-cap';
        cap.textContent = ad.title;
        el.appendChild(cap);
      }
    } else {
      const span = document.createElement('span');
      span.className = 'ad-text';
      span.textContent = (ad.title ? ad.title + ' — ' : '') + (ad.text || '');
      el.appendChild(span);
      if (ad.animation === 'ticker') {
        // la cinta recorre todo el ancho durante la duracion del aviso
        span.style.animationDuration = ad.duration + 's';
      }
    }
    return el;
  }

  function next() {
    i = (i + 1) % ads.length;
    const ad = ads[i];
    const el = build(ad);
    const old = bar.firstElementChild;
    if (old) {
      old.classList.add('ad-out');
      setTimeout(() => old.remove(), 450);
    }
    bar.appendChild(el);
    setTimeout(next, ad.duration * 1000);
  }

  next();
})();
