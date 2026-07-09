/**
 * Rotador de publicidades (window.ADS).
 * Animaciones: slide (carrusel), fade (materializa), zoom, ticker (cinta continua).
 * Cada aviso define su duracion y su animacion.
 */
(function () {
  const ads = window.ADS || [];
  const bars = document.querySelectorAll('.adsbar');
  if (!ads.length || !bars.length) return;
  document.body.classList.add('has-ads');
  if (bars.length > 1) document.body.classList.add('dual-ads');
  // Alto real ocupado por las cintas, para que el timer/marcador (que usa
  // vh) pueda restarlo con calc() en vez de desbordar la pantalla.
  const adsSpace = bars[0].getBoundingClientRect().height * bars.length;
  document.documentElement.style.setProperty('--ads-h', adsSpace + 'px');
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
    bars.forEach((bar) => {
      const el = build(ad);
      const old = bar.firstElementChild;
      if (old) {
        old.classList.add('ad-out');
        setTimeout(() => old.remove(), 450);
      }
      bar.appendChild(el);
    });
    setTimeout(next, ad.duration * 1000);
  }

  next();
})();
