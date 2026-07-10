<?php
$t = row('SELECT * FROM tournaments WHERE slug = ?', [$params[0]]);
if (!$t) { http_response_code(404); require BASE_PATH . '/src/pages/_404.php'; exit; }
$tid = (int)$t['id'];

$academies = rows('SELECT * FROM tournament_academies WHERE tournament_id=? ORDER BY name', [$tid]);
$professors = rows('SELECT * FROM tournament_professors WHERE tournament_id=? ORDER BY name', [$tid]);
$belts = rows('SELECT * FROM belts ORDER BY sort');
$count = (int)scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=?', [$tid]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $gender = ($_POST['gender'] ?? 'M') === 'F' ? 'F' : 'M';
    $birthdate = $_POST['birthdate'] ?? '';
    $weight = (float)($_POST['weight_kg'] ?? 0);
    $beltId = (int)($_POST['belt_id'] ?? 0);

    if ($t['status'] !== 'open') {
        flash('error', t('registration_closed'));
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
        } else {
            // Alta de usuario si no existe (la verificacion de inscripcion tambien verifica la cuenta)
            $user = row('SELECT * FROM users WHERE email = ?', [$email]);
            if (!$user && strlen($_POST['password'] ?? '') >= 6) {
                q('INSERT INTO users (name, email, pass_hash, locale) VALUES (?,?,?,?)',
                    [$name, $email, password_hash($_POST['password'], PASSWORD_DEFAULT), lang()]);
                $user = row('SELECT * FROM users WHERE email = ?', [$email]);
            }
            $token = bin2hex(random_bytes(24));
            q('INSERT INTO registrations (tournament_id, user_id, name, email, gender, birthdate, weight_kg, belt_id, age_division_id, weight_class_id, academy_id, professor_id, verify_token)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$tid, $user['id'] ?? null, $name, $email, $gender, $birthdate, $weight, $beltId,
                 $ageDiv['id'], $wc['id'],
                 (int)($_POST['academy_id'] ?? 0) ?: null, (int)($_POST['professor_id'] ?? 0) ?: null, $token]);
            $link = APP_URL . '/reg-verify?token=' . $token;
            queue_mail($email, $name, t('mail_reg_subject') . ' - ' . $t['name'], mail_layout(t('mail_reg_subject'),
                '<p>' . t('mail_reg_body') . ' <b>' . e($t['name']) . '</b>.</p>' .
                '<p style="text-align:center"><a href="' . $link . '" style="background:#30a46c;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">' . t('mail_reg_button') . '</a></p>' .
                '<p style="font-size:12px;color:#888">' . $link . '</p>'));
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
  </div>
  <?php if ($t['status'] !== 'open'): ?>
    <div class="flash flash-warning"><?= t('registration_closed') ?></div>
  <?php else: ?>
  <form method="post">
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
      </div>
      <div>
        <label><?= t('belt') ?></label>
        <select name="belt_id" required>
          <?php foreach ($belts as $b): ?>
          <option value="<?= $b['id'] ?>"><?= e(loc_name($b)) ?></option>
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
    <p class="muted"><?= t('your_category_auto') ?></p>
    <button class="btn xl" style="width:100%"><?= t('submit_registration') ?></button>
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
  </script>
  <?php endif; ?>
</div>
<?php view_footer();
