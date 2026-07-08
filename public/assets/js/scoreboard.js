/**
 * Scoreboard compartido por operador y display publico.
 * El servidor es la fuente de verdad; el cliente interpola el countdown.
 * window.SB = { matchId, apiUrl, isOperator, csrf }
 */
(function () {
  const S = window.SB;
  let state = null;
  let lastSync = 0;

  function fmt(sec) {
    sec = Math.max(0, Math.round(sec));
    return Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0');
  }

  function remaining() {
    if (!state) return 0;
    if (!state.timer_running) return state.timer_remaining;
    const elapsed = (Date.now() - lastSync) / 1000;
    return Math.max(0, state.timer_remaining - elapsed);
  }

  function render() {
    if (!state) return;
    const rem = remaining();
    document.querySelectorAll('[data-sb="timer"]').forEach((el) => {
      el.textContent = fmt(rem);
      el.classList.toggle('warning', rem <= 60 && rem > 10);
      el.classList.toggle('danger', rem <= 10);
    });
    const map = {
      red_points: state.red_points, blue_points: state.blue_points,
      red_adv: state.red_adv, blue_adv: state.blue_adv,
      red_pen: state.red_pen, blue_pen: state.blue_pen,
    };
    for (const k in map) {
      document.querySelectorAll('[data-sb="' + k + '"]').forEach((el) => (el.textContent = map[k]));
    }
    const wbar = document.querySelector('[data-sb="winnerbar"]');
    if (wbar) {
      if (state.status === 'done' && state.winner_name) {
        wbar.style.display = '';
        wbar.textContent = '🏆 ' + wbar.dataset.label + ': ' + state.winner_name + (state.method_label ? ' (' + state.method_label + ')' : '');
      } else {
        wbar.style.display = 'none';
      }
    }
    document.querySelectorAll('[data-sb="startbtn"]').forEach((el) => {
      el.textContent = state.timer_running ? el.dataset.pause : el.dataset.start;
      el.classList.toggle('warn', !!state.timer_running);
      el.classList.toggle('green', !state.timer_running);
    });
  }

  async function sync() {
    try {
      const r = await fetch(S.apiUrl);
      state = await r.json();
      lastSync = Date.now();
      render();
    } catch (e) { /* reintenta en el proximo tick */ }
  }

  window.sbAction = async function (action, side, type) {
    if (!S.isOperator) return;
    const body = new URLSearchParams({ action, csrf: S.csrf });
    if (side) body.set('side', side);
    if (type) body.set('type', type);
    const r = await fetch(S.apiUrl, { method: 'POST', body });
    state = await r.json();
    lastSync = Date.now();
    render();
    if (state.status === 'done' && action === 'end') location.reload();
  };

  window.sbToggleTimer = function () {
    sbAction(state && state.timer_running ? 'pause' : 'start');
  };

  sync();
  setInterval(sync, 2000);
  setInterval(render, 250);
})();
