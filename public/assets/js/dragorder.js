/**
 * Lista drag-and-drop generica para ordenar categorias (cinturon/edad/peso).
 * Cada <li data-key="..."> trae un <input type="hidden"> con su posicion; al
 * soltar, se renumeran todos los inputs 1..N en el nuevo orden, listos para
 * enviarse tal cual con el form (mismo esquema que ya usaban los <input
 * type="number"> que reemplaza: name="prefijo_clave" value="posicion").
 * Uso: <ul class="dragorder">...</ul> — se auto-inicializa al cargar.
 */
(function () {
  function getAfterElement(list, y) {
    const els = [...list.querySelectorAll('li:not(.dragging)')];
    return els.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) return { offset, element: child };
      return closest;
    }, { offset: -Infinity }).element;
  }

  function renumber(list) {
    [...list.querySelectorAll('li')].forEach((li, i) => {
      const input = li.querySelector('input[type="hidden"]');
      if (input) input.value = i + 1;
      const badge = li.querySelector('.dragorder-pos');
      if (badge) badge.textContent = i + 1;
    });
  }

  function initDragOrder(list) {
    let dragEl = null;
    list.querySelectorAll('li').forEach((li) => {
      li.setAttribute('draggable', 'true');
      li.addEventListener('dragstart', () => {
        dragEl = li;
        requestAnimationFrame(() => li.classList.add('dragging'));
      });
      li.addEventListener('dragend', () => {
        li.classList.remove('dragging');
        renumber(list);
      });
    });
    list.addEventListener('dragover', (e) => {
      e.preventDefault();
      if (!dragEl) return;
      const after = getAfterElement(list, e.clientY);
      if (after == null) list.appendChild(dragEl);
      else list.insertBefore(dragEl, after);
    });
    renumber(list);
  }

  document.querySelectorAll('.dragorder').forEach(initDragOrder);
})();
