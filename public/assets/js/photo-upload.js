// Redimensiona/comprime la foto del competidor en el navegador ANTES de subirla.
// Un celular saca fotos de varios MB; sin esto, superan post_max_size de PHP y
// el POST se descarta entero (rompiendo el CSRF). Acá la achicamos a un lado
// máximo razonable y la re-encodamos como JPEG, así el upload queda en pocas
// decenas de KB y nunca "explota". Backstop server-side igual valida el tamaño.
(function () {
  var MAX_DIM = 1280;      // lado más largo, en px
  var QUALITY = 0.85;      // calidad JPEG
  var HARD_MAX = 12 * 1024 * 1024; // tope duro si no se pudo redimensionar

  document.querySelectorAll('input[type=file][data-photo]').forEach(function (input) {
    var form = input.form;
    var note = document.createElement('small');
    note.className = 'muted';
    note.style.display = 'block';
    input.insertAdjacentElement('afterend', note);
    var busy = false;

    function setNote(msg, isError) {
      note.textContent = msg || '';
      note.style.color = isError ? 'var(--red)' : '';
    }

    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      setNote('');
      if (!f || !/^image\//.test(f.type)) return;

      busy = true;
      setNote(input.dataset.optimizing || 'Optimizando foto…');
      var url = URL.createObjectURL(f);
      var img = new Image();

      img.onload = function () {
        URL.revokeObjectURL(url);
        var scale = Math.min(1, MAX_DIM / Math.max(img.width, img.height));
        var cw = Math.max(1, Math.round(img.width * scale));
        var ch = Math.max(1, Math.round(img.height * scale));
        var canvas = document.createElement('canvas');
        canvas.width = cw; canvas.height = ch;
        try {
          canvas.getContext('2d').drawImage(img, 0, 0, cw, ch);
        } catch (e) { busy = false; setNote(''); return; }

        canvas.toBlob(function (blob) {
          try {
            if (blob && blob.size < f.size) {
              var base = (f.name || 'foto').replace(/\.[^.]+$/, '') || 'foto';
              var nf = new File([blob], base + '.jpg', { type: 'image/jpeg' });
              var dt = new DataTransfer();
              dt.items.add(nf);
              input.files = dt.files;
            }
          } catch (e) { /* navegador viejo sin DataTransfer: se sube el original */ }
          busy = false;
          var finalSize = (input.files[0] || f).size;
          if (finalSize > HARD_MAX) {
            setNote(input.dataset.toobig || 'La imagen es demasiado pesada.', true);
          } else {
            setNote(input.dataset.ready || '');
          }
        }, 'image/jpeg', QUALITY);
      };
      img.onerror = function () { URL.revokeObjectURL(url); busy = false; setNote(''); };
      img.src = url;
    });

    // Si el usuario manda el form mientras todavía se está optimizando, esperamos.
    if (form) {
      form.addEventListener('submit', function (e) {
        var f = input.files && input.files[0];
        if (f && f.size > HARD_MAX) {
          e.preventDefault();
          setNote(input.dataset.toobig || 'La imagen es demasiado pesada.', true);
          return;
        }
        if (busy) {
          e.preventDefault();
          setNote(input.dataset.optimizing || 'Optimizando foto…');
          var timer = setInterval(function () {
            if (!busy) { clearInterval(timer); form.submit(); }
          }, 100);
        }
      });
    }
  });
})();
