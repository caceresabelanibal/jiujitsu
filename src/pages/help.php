<?php
// Centro de ayuda: contenido por pantalla en src/help/<idioma>.php (cae a es),
// con indice lateral y buscador client-side (filtra temas por texto).
$helpFile = BASE_PATH . '/src/help/' . lang() . '.php';
if (!file_exists($helpFile)) $helpFile = BASE_PATH . '/src/help/es.php';
$help = require $helpFile;

view_header(t('help'));
?>
<div class="help-head">
  <h1><?= icon('help', 26) ?> <?= e($help['title']) ?></h1>
  <p class="muted"><?= e($help['intro']) ?></p>
  <input type="search" id="helpsearch" class="help-search" placeholder="<?= e(t('help_search_ph')) ?>" autocomplete="off">
  <p class="muted" id="helpcount" style="display:none"></p>
</div>

<div class="help-layout">
  <nav class="help-index card" id="helpindex">
    <h3><?= t('help_index') ?></h3>
    <?php foreach ($help['sections'] as $s): ?>
    <div class="hi-sec" data-sec="<?= e($s['id']) ?>">
      <a class="hi-title" href="#<?= e($s['id']) ?>"><?= icon($s['icon'], 14) ?> <?= e($s['title']) ?></a>
      <div class="hi-topics">
        <?php foreach ($s['topics'] as $tp): ?>
        <a href="#<?= e($tp['id']) ?>" data-topic="<?= e($tp['id']) ?>"><?= e($tp['title']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </nav>

  <div class="help-content">
    <?php foreach ($help['sections'] as $s): ?>
    <section class="help-sec" id="<?= e($s['id']) ?>" data-sec="<?= e($s['id']) ?>">
      <h2><?= icon($s['icon'], 20) ?> <?= e($s['title']) ?></h2>
      <?php foreach ($s['topics'] as $tp): ?>
      <article class="card help-topic" id="<?= e($tp['id']) ?>">
        <h3><?= e($tp['title']) ?></h3>
        <?= $tp['body'] /* HTML de autoria propia (src/help/), no input de usuario */ ?>
      </article>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    <p class="muted center" id="helpempty" style="display:none"><?= t('help_no_results') ?></p>
  </div>
</div>

<script>
(function () {
  const input = document.getElementById('helpsearch');
  const topics = Array.from(document.querySelectorAll('.help-topic'));
  const secs = Array.from(document.querySelectorAll('.help-sec'));
  const idxTopics = Array.from(document.querySelectorAll('.hi-topics a'));
  const idxSecs = Array.from(document.querySelectorAll('.hi-sec'));
  const count = document.getElementById('helpcount');
  const empty = document.getElementById('helpempty');
  const norm = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  const cache = topics.map((el) => norm(el.textContent));

  input.addEventListener('input', () => {
    const q = norm(input.value.trim());
    let shown = 0;
    topics.forEach((el, i) => {
      const ok = !q || cache[i].includes(q);
      el.style.display = ok ? '' : 'none';
      if (ok) shown++;
      const link = idxTopics.find((a) => a.dataset.topic === el.id);
      if (link) link.style.display = ok ? '' : 'none';
    });
    secs.forEach((sec) => {
      const any = Array.from(sec.querySelectorAll('.help-topic')).some((t) => t.style.display !== 'none');
      sec.style.display = any ? '' : 'none';
      const idx = idxSecs.find((d) => d.dataset.sec === sec.dataset.sec);
      if (idx) idx.style.display = any ? '' : 'none';
    });
    count.style.display = q ? '' : 'none';
    count.textContent = q ? shown + ' ✓' : '';
    empty.style.display = q && shown === 0 ? '' : 'none';
  });

  // Si llegamos con #ancla, resaltar el tema un instante
  if (location.hash) {
    const el = document.getElementById(location.hash.slice(1));
    if (el) { el.classList.add('help-hl'); setTimeout(() => el.classList.remove('help-hl'), 2600); }
  }
})();
</script>
<?php view_footer();
