/**
 * Ajusta el tamaño de la llave para que entre sin scroll vertical
 * (variable CSS --bz en #bracket-region, usada por app.css via calc()) y
 * dibuja las lineas de conexion entre partidos con SVG, midiendo la
 * posicion real de cada tarjeta (no depende de alturas fijas, asi funciona
 * con cualquier cantidad de rondas/partidos y contenido variable).
 */
function fitBracket() {
  const region = document.getElementById('bracket-region');
  if (!region) { drawBracketLines(1); return; }

  region.style.setProperty('--bz', 1);
  const available = region.clientHeight;
  const natural = region.scrollHeight;
  let factor = available > 0 && natural > available ? available / natural : 1;
  factor = Math.max(0.55, Math.min(1, factor));
  region.style.setProperty('--bz', factor);
  drawBracketLines(factor);
}

function drawBracketLines(zoom) {
  zoom = zoom || 1;
  const root = document.getElementById('bracket-svg-root');
  const svg = document.getElementById('bracket-lines');
  if (!root || !svg) return;

  const w = root.scrollWidth;
  const h = root.scrollHeight;
  svg.setAttribute('width', w);
  svg.setAttribute('height', h);
  svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
  svg.innerHTML = '';

  const rootRect = root.getBoundingClientRect();
  const matches = root.querySelectorAll('.b-match[data-id]');
  const byId = {};
  matches.forEach((m) => { byId[m.dataset.id] = m; });

  function rectOf(el) {
    const r = el.getBoundingClientRect();
    return {
      right: r.right - rootRect.left,
      left: r.left - rootRect.left,
      midY: r.top - rootRect.top + r.height / 2,
    };
  }

  function connector(fromEl, toEl, color, bronze) {
    const a = rectOf(fromEl);
    const b = rectOf(toEl);
    const midX = a.right + (b.left - a.right) / 2;
    const maxRadius = Math.max(5, 14 * zoom);
    const radius = Math.min(maxRadius, Math.abs(b.midY - a.midY) / 2 || maxRadius);
    const dir = b.midY > a.midY ? 1 : b.midY < a.midY ? -1 : 0;
    let d;
    if (dir === 0) {
      d = `M ${a.right} ${a.midY} H ${b.left}`;
    } else {
      d = `M ${a.right} ${a.midY} H ${midX - radius} `
        + `Q ${midX} ${a.midY} ${midX} ${a.midY + radius * dir} `
        + `V ${b.midY - radius * dir} `
        + `Q ${midX} ${b.midY} ${midX + radius} ${b.midY} `
        + `H ${b.left}`;
    }
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', d);
    path.setAttribute('class', 'bracket-line' + (bronze ? ' bracket-line-bronze' : ''));
    path.setAttribute('stroke', color);
    path.setAttribute('stroke-width', Math.max(1.5, 2.5 * zoom));
    svg.appendChild(path);
  }

  matches.forEach((m) => {
    const round = m.closest('.b-round');
    const color = round ? getComputedStyle(round).getPropertyValue('--accent').trim() || '#4f8cff' : '#4f8cff';
    if (m.dataset.next && byId[m.dataset.next]) connector(m, byId[m.dataset.next], color, false);
    if (m.dataset.bronze && byId[m.dataset.bronze]) connector(m, byId[m.dataset.bronze], '#e0a659', true);
  });
}

window.drawBracketLines = drawBracketLines;
window.fitBracket = fitBracket;
window.addEventListener('resize', () => { clearTimeout(window._brT); window._brT = setTimeout(fitBracket, 150); });
window.addEventListener('load', fitBracket);
