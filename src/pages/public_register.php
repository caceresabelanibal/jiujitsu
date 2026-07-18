<?php
$t = row('SELECT * FROM tournaments WHERE slug = ?', [$params[0]]);
if (!$t) { http_response_code(404); require BASE_PATH . '/src/pages/_404.php'; exit; }
$tid = (int)$t['id'];

$academies = rows('SELECT * FROM tournament_academies WHERE tournament_id=? ORDER BY name', [$tid]);
$professors = rows('SELECT * FROM tournament_professors WHERE tournament_id=? ORDER BY name', [$tid]);
$belts = rows('SELECT * FROM belts ORDER BY sort');
$nogiTiersForm = $t['discipline'] === 'nogi' ? nogi_tiers_for($t) : [];
$count = (int)scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=?', [$tid]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_exceeded_limit()) {
    // La foto (o el total del form) superó post_max_size: PHP ya descartó todo.
    flash('error', t('upload_too_large'));
    redirect('/t/' . $t['slug']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $gender = ($_POST['gender'] ?? 'M') === 'F' ? 'F' : 'M';
    $birthdate = $_POST['birthdate'] ?? '';
    $weight = (float)($_POST['weight_kg'] ?? 0);
    $beltId = (int)($_POST['belt_id'] ?? 0);
    $inCategory = isset($_POST['compete_category']);
    $inAbsolute = isset($_POST['compete_absolute']);
    $competesIn = $inCategory && $inAbsolute ? 'both' : ($inAbsolute ? 'absolute' : 'category');

    if ($t['status'] !== 'open' || registrations_closed($t)) {
        flash('error', t('registration_closed'));
    } elseif (!captcha_check()) {
        flash('error', t('captcha_wrong'));
    } elseif ($count >= (int)$t['max_participants']) {
        flash('error', t('tournament_full'));
    } elseif (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$birthdate || $weight <= 0 || !$beltId) {
        flash('error', t('invalid_credentials'));
    } elseif (row('SELECT id FROM registrations WHERE tournament_id=? AND email=?', [$tid, $email])) {
        flash('error', t('already_registered'));
    } else {
        $age = competition_age($birthdate);
        $ageDiv = find_age_division_for($age, age_thresholds_for($t));
        $belt = row('SELECT * FROM belts WHERE id=?', [$beltId]);
        $wc = find_weight_class($gender, $weight, (bool)($ageDiv['is_kids'] ?? false));
        if (!$ageDiv || !$wc) {
            flash('error', 'Edad/peso fuera de rango');
        } elseif ($inAbsolute && !can_compete_absolute($beltId, $ageDiv['id'], $t)) {
            flash('error', t('absolute_not_eligible'));
        } else {
            // Alta de usuario si no existe (la verificacion de inscripcion tambien verifica la cuenta)
            $user = row('SELECT * FROM users WHERE email = ?', [$email]);
            if (!$user && strlen($_POST['password'] ?? '') >= 6) {
                q('INSERT INTO users (name, email, pass_hash, locale) VALUES (?,?,?,?)',
                    [$name, $email, password_hash($_POST['password'], PASSWORD_DEFAULT), lang()]);
                $user = row('SELECT * FROM users WHERE email = ?', [$email]);
            }
            $photo = upload_image('photo', 'competitor');
            $token = bin2hex(random_bytes(24));
            q('INSERT INTO registrations (tournament_id, user_id, name, email, gender, birthdate, weight_kg, photo, belt_id, age_division_id, weight_class_id, competes_in, academy_id, professor_id, verify_token)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$tid, $user['id'] ?? null, $name, $email, $gender, $birthdate, $weight, $photo, $beltId,
                 $ageDiv['id'], $wc['id'], $competesIn,
                 (int)($_POST['academy_id'] ?? 0) ?: null, (int)($_POST['professor_id'] ?? 0) ?: null, $token]);
            $link = APP_URL . '/reg-verify?token=' . $token;
            queue_mail($email, $name, t('mail_reg_subject') . ' - ' . $t['name'], mail_layout(t('mail_reg_subject'),
                mail_p(e($name) . ',') .
                mail_p(t('mail_reg_body') . ' <b>' . e($t['name']) . '</b>.') .
                mail_button($link, t('mail_reg_button')) .
                mail_link_fallback($link)));
            flash('success', t('reg_check_email'));
            redirect('/t/' . $t['slug']);
        }
    }
}

view_header(t('register_for') . ' ' . $t['name']);
?>
<div class="card" style="max-width:640px;margin:20px auto">
  <div class="center">
    <?php if ($t['logo']): ?><img class="tlogo" src="<?= APP_URL . '/' . e($t['logo']) ?>" alt=""><?php endif; ?>
    <h1><?= e($t['name']) ?></h1>
    <p class="muted"><?= t('register_for') ?> <b><?= e($t['name']) ?></b>
      <?= $t['event_date'] ? '· ' . date('d/m/Y', strtotime($t['event_date'])) : '' ?></p>
    <p><?= help_link('inscribirse') ?></p>
  </div>
  <?php if ($t['status'] !== 'open' || registrations_closed($t)): ?>
    <div class="flash flash-warning"><?= t('registration_closed') ?></div>
  <?php else: ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="grid cols2">
      <div>
        <label><?= t('name') ?></label>
        <input type="text" name="name" required>
        <label><?= t('email') ?></label>
        <input type="email" name="email" required>
        <label><?= t('gender') ?></label>
        <select name="gender"><option value="M"><?= t('male') ?></option><option value="F"><?= t('female') ?></option></select>
        <label><?= t('birthdate') ?></label>
        <input type="date" name="birthdate" required>
        <label><?= t('weight_kg') ?></label>
        <input type="number" name="weight_kg" step="0.1" min="10" max="250" required>
        <label><?= t('photo') ?></label>
        <input type="file" name="photo" accept="image/*" data-photo
               data-optimizing="<?= e(t('photo_optimizing')) ?>" data-toobig="<?= e(t('upload_too_large')) ?>">
        <small class="muted"><?= t('photo_reg_hint') ?></small>
      </div>
      <div>
        <label><?= t('belt') ?></label>
        <select name="belt_id" id="beltsel" required onchange="updateAbsEligibility()">
          <?php foreach ($belts as $b):
              $eligible = !$b['is_kids'] && ($t['discipline'] === 'nogi' ? ($nogiTiersForm[$b['code']] ?? 'amateur') !== 'amateur' : $b['code'] !== 'white'); ?>
          <option value="<?= $b['id'] ?>" data-abs="<?= $eligible ? '1' : '0' ?>"><?= e(loc_name($b)) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($academies): ?>
        <label><?= t('select_academy') ?></label>
        <select name="academy_id" id="acadsel" onchange="filterProfs()">
          <?php foreach ($academies as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if ($professors): ?>
        <label><?= t('select_professor') ?></label>
        <select name="professor_id" id="profsel">
          <option value=""><?= t('none') ?></option>
          <?php foreach ($professors as $p): ?>
          <option value="<?= $p['id'] ?>" data-academy="<?= $p['academy_id'] ?>"><?= e($p['name']) ?><?= $p['sede'] ? ' · ' . e($p['sede']) : '' ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <label><?= t('password') ?></label>
        <input type="password" name="password" minlength="6" placeholder="••••••">
        <small class="muted"><?= t('set_password_hint') ?></small>
      </div>
    </div>
    <label class="mt"><?= t('compete_in_label') ?></label>
    <div class="flex">
      <label class="flex" style="margin:0"><input type="checkbox" name="compete_category" value="1" checked style="width:auto"> <?= t('compete_category') ?></label>
      <label class="flex" style="margin:0"><input type="checkbox" name="compete_absolute" id="abschk" value="1" style="width:auto"> <?= t('compete_absolute') ?></label>
    </div>
    <small class="muted"><?= t('compete_in_hint') ?></small>
    <small class="muted" id="abshint" style="display:none;color:var(--red)"><?= t('absolute_not_eligible') ?></small>
    <p class="muted"><?= t('your_category_auto') ?></p>
    <?= captcha_field() ?>
    <button class="btn xl mt" style="width:100%"><?= t('submit_registration') ?></button>
  </form>
  <script>
  function filterProfs() {
    const aid = document.getElementById('acadsel')?.value;
    document.querySelectorAll('#profsel option[data-academy]').forEach(o => {
      o.hidden = aid && o.dataset.academy !== aid;
    });
    const sel = document.getElementById('profsel');
    if (sel && sel.selectedOptions[0]?.hidden) sel.value = '';
  }
  filterProfs();
  function updateAbsEligibility() {
    const sel = document.getElementById('beltsel');
    const eligible = sel?.selectedOptions[0]?.dataset.abs === '1';
    const chk = document.getElementById('abschk');
    const hint = document.getElementById('abshint');
    if (!chk) return;
    chk.disabled = !eligible;
    if (!eligible) chk.checked = false;
    hint.style.display = eligible ? 'none' : '';
  }
  updateAbsEligibility();
  </script>
  <script src="<?= asset('/assets/js/photo-upload.js') ?>"></script>
  <?php endif; ?>
</div>
<?php view_footer();
