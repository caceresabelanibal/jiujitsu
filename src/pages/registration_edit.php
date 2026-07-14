<?php
// Edicion libre de un inscripto: el organizador (o admin) puede corregir
// cualquier dato, incluida la categoria (cinturon/edad/peso) — es la forma de
// "unificar" a un competidor sin rival a otra categoria donde si compita.
$r = row('SELECT * FROM registrations WHERE id = ?', [(int)$params[0]]);
if (!$r) { http_response_code(404); require BASE_PATH . '/src/pages/_404.php'; exit; }
$t = require_tournament_owner((int)$r['tournament_id']);
$tid = (int)$t['id'];
$rid = (int)$r['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_exceeded_limit()) {
    flash('error', t('upload_too_large'));
    redirect("/registration/$rid/edit");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $photo = upload_image('photo', 'competitor') ?? $r['photo'];
    $beltId = (int)($_POST['belt_id'] ?: $r['belt_id']);
    $ageDivisionId = (int)($_POST['age_division_id'] ?: $r['age_division_id']);
    $competesIn = in_array($_POST['competes_in'] ?? 'category', ['absolute', 'both'], true) ? $_POST['competes_in'] : 'category';
    if ($competesIn !== 'category' && !can_compete_absolute($beltId, $ageDivisionId, $t)) {
        $competesIn = 'category';
        flash('warning', t('absolute_not_eligible_forced'));
    }
    q('UPDATE registrations SET name=?, gender=?, birthdate=?, weight_kg=?, photo=?, belt_id=?, age_division_id=?, weight_class_id=?, competes_in=?, academy_id=?, professor_id=?
       WHERE id = ?',
        [trim($_POST['name'] ?? $r['name']), ($_POST['gender'] ?? 'M') === 'F' ? 'F' : 'M',
         $_POST['birthdate'] ?: $r['birthdate'], (float)($_POST['weight_kg'] ?: $r['weight_kg']), $photo,
         $beltId, $ageDivisionId,
         (int)($_POST['weight_class_id'] ?: $r['weight_class_id']),
         $competesIn,
         (int)($_POST['academy_id'] ?? 0) ?: null, (int)($_POST['professor_id'] ?? 0) ?: null, $rid]);
    flash('success', t('registration_updated'));
    redirect("/tournament/$tid/registrations");
}

$belts = rows('SELECT * FROM belts ORDER BY sort');
$ages = rows('SELECT * FROM age_divisions ORDER BY sort');
$wcs = rows('SELECT * FROM weight_classes ORDER BY gender, sort');
$academies = rows('SELECT * FROM tournament_academies WHERE tournament_id = ? ORDER BY name', [$tid]);
$professors = rows('SELECT * FROM tournament_professors WHERE tournament_id = ? ORDER BY name', [$tid]);
view_header(t('edit_registration'));
?>
<p><a href="<?= APP_URL ?>/tournament/<?= $tid ?>/registrations">← <?= t('registrations') ?></a></p>
<div class="card" style="max-width:640px;margin:0 auto">
  <h2><?= t('edit_registration') ?>: <?= e($r['name']) ?></h2>
  <p class="muted"><?= t('edit_registration_hint') ?></p>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="grid cols2">
      <div>
        <label><?= t('name') ?></label>
        <input type="text" name="name" value="<?= e($r['name']) ?>" required>
        <label><?= t('email') ?></label>
        <input type="email" value="<?= e($r['email']) ?>" disabled>
        <label><?= t('gender') ?></label>
        <select name="gender">
          <option value="M" <?= $r['gender'] === 'M' ? 'selected' : '' ?>><?= t('male') ?></option>
          <option value="F" <?= $r['gender'] === 'F' ? 'selected' : '' ?>><?= t('female') ?></option>
        </select>
        <label><?= t('birthdate') ?></label>
        <input type="date" name="birthdate" value="<?= e($r['birthdate']) ?>">
        <label><?= t('weight_kg') ?></label>
        <input type="number" name="weight_kg" value="<?= e((string)$r['weight_kg']) ?>" step="0.1" min="10" max="250">
        <label><?= t('photo') ?></label>
        <?php if ($r['photo']): ?><div class="mb"><img src="<?= APP_URL . '/' . e($r['photo']) ?>" alt="" class="reg-photo-md"></div><?php endif; ?>
        <input type="file" name="photo" accept="image/*" data-photo
               data-optimizing="<?= e(t('photo_optimizing')) ?>" data-toobig="<?= e(t('upload_too_large')) ?>">
      </div>
      <div>
        <label><?= t('belt') ?></label>
        <select name="belt_id">
          <?php foreach ($belts as $b): ?>
          <option value="<?= $b['id'] ?>" <?= (int)$b['id'] === (int)$r['belt_id'] ? 'selected' : '' ?>><?= e(loc_name($b)) ?></option>
          <?php endforeach; ?>
        </select>
        <label><?= t('age_division') ?></label>
        <select name="age_division_id">
          <?php foreach ($ages as $a): ?>
          <option value="<?= $a['id'] ?>" <?= (int)$a['id'] === (int)$r['age_division_id'] ? 'selected' : '' ?>><?= e(loc_name($a)) ?></option>
          <?php endforeach; ?>
        </select>
        <label><?= t('weight_class') ?></label>
        <select name="weight_class_id">
          <?php foreach ($wcs as $w): ?>
          <option value="<?= $w['id'] ?>" <?= (int)$w['id'] === (int)$r['weight_class_id'] ? 'selected' : '' ?>>
            <?= ($w['gender'] === 'A' ? t('kids_gender_label') : ($w['gender'] === 'M' ? t('male') : t('female'))) ?> · <?= e(loc_name($w)) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php if ($academies): ?>
        <label><?= t('select_academy') ?></label>
        <select name="academy_id">
          <option value=""><?= t('none') ?></option>
          <?php foreach ($academies as $a): ?>
          <option value="<?= $a['id'] ?>" <?= (int)$a['id'] === (int)$r['academy_id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if ($professors): ?>
        <label><?= t('select_professor') ?></label>
        <select name="professor_id">
          <option value=""><?= t('none') ?></option>
          <?php foreach ($professors as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (int)$p['id'] === (int)$r['professor_id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <label><?= t('compete_in_label') ?></label>
        <select name="competes_in">
          <option value="category" <?= $r['competes_in'] === 'category' ? 'selected' : '' ?>><?= t('compete_category') ?></option>
          <option value="absolute" <?= $r['competes_in'] === 'absolute' ? 'selected' : '' ?>><?= t('compete_absolute') ?></option>
          <option value="both" <?= $r['competes_in'] === 'both' ? 'selected' : '' ?>><?= t('compete_both') ?></option>
        </select>
      </div>
    </div>
    <button class="btn mt" style="width:100%"><?= t('save') ?></button>
  </form>
</div>
<script src="<?= asset('/assets/js/photo-upload.js') ?>"></script>
<?php view_footer();
