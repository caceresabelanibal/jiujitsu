<?php
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'create') {
        $scope = ($_POST['scope'] ?? 'global') === 'tournament' ? 'tournament' : 'global';
        $type = ($_POST['type'] ?? 'text') === 'image' ? 'image' : 'text';
        $image = $type === 'image' ? upload_image('image', 'ad') : null;
        if ($type === 'text' ? trim($_POST['text_content'] ?? '') !== '' : $image !== null) {
            q('INSERT INTO ads (scope, tournament_id, type, title, text_content, image, duration_sec, animation, sort)
               VALUES (?,?,?,?,?,?,?,?,?)',
                [$scope, $scope === 'tournament' ? (int)$_POST['tournament_id'] : null, $type,
                 trim($_POST['title'] ?? '') ?: null, trim($_POST['text_content'] ?? '') ?: null, $image,
                 max(3, (int)($_POST['duration_sec'] ?: 8)),
                 in_array($_POST['animation'] ?? '', ['slide','fade','zoom','ticker']) ? $_POST['animation'] : 'slide',
                 (int)($_POST['sort'] ?? 0)]);
            flash('success', t('settings_saved'));
        } else {
            flash('error', t('ad_content_required'));
        }
    } elseif ($do === 'toggle') {
        q('UPDATE ads SET active = 1 - active WHERE id = ?', [(int)$_POST['id']]);
    } elseif ($do === 'delete') {
        q('DELETE FROM ads WHERE id = ?', [(int)$_POST['id']]);
    } elseif ($do === 'modes') {
        foreach (($_POST['mode'] ?? []) as $tid => $mode) {
            if (in_array($mode, ['none','tournament','general','both'])) {
                q('UPDATE tournaments SET ads_mode = ? WHERE id = ?', [$mode, (int)$tid]);
            }
        }
        flash('success', t('settings_saved'));
    }
    redirect('/admin/ads');
}

$ads = rows('SELECT a.*, t.name t_name FROM ads a LEFT JOIN tournaments t ON t.id = a.tournament_id ORDER BY a.scope, a.sort, a.id');
$tournaments = rows('SELECT id, name, ads_mode FROM tournaments ORDER BY created_at DESC');
$anims = ['slide' => t('anim_slide'), 'fade' => t('anim_fade'), 'zoom' => t('anim_zoom'), 'ticker' => t('anim_ticker')];
view_header(t('ads'));
?>
<h1><?= icon('megaphone', 24) ?> <?= t('ads') ?></h1>
<p class="muted"><?= t('ads_hint') ?></p>

<div class="card">
  <h3>+ <?= t('new_ad') ?></h3>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="create">
    <div class="grid cols3">
      <div>
        <label><?= t('ad_scope') ?></label>
        <select name="scope" onchange="document.getElementById('adTournament').style.display = this.value === 'tournament' ? '' : 'none'">
          <option value="global"><?= t('ad_scope_global') ?></option>
          <option value="tournament"><?= t('ad_scope_tournament') ?></option>
        </select>
        <div id="adTournament" style="display:none">
          <label><?= t('tournament') ?></label>
          <select name="tournament_id">
            <?php foreach ($tournaments as $tt): ?><option value="<?= $tt['id'] ?>"><?= e($tt['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <label><?= t('ad_type') ?></label>
        <select name="type" onchange="document.getElementById('adImg').style.display = this.value === 'image' ? '' : 'none';
                                      document.getElementById('adTxt').style.display = this.value === 'image' ? 'none' : ''">
          <option value="text"><?= t('ad_type_text') ?></option>
          <option value="image"><?= t('ad_type_image') ?></option>
        </select>
      </div>
      <div>
        <label><?= t('ad_title') ?></label>
        <input type="text" name="title" placeholder="Sponsor / marca">
        <div id="adTxt">
          <label><?= t('ad_text') ?></label>
          <textarea name="text_content" rows="3" maxlength="500" placeholder="<?= t('ad_text_ph') ?>"></textarea>
        </div>
        <div id="adImg" style="display:none">
          <label><?= t('ad_image') ?></label>
          <input type="file" name="image" accept="image/*">
        </div>
      </div>
      <div>
        <label><?= t('ad_duration') ?></label>
        <input type="number" name="duration_sec" value="<?= (int)setting('ads_default_duration', 8) ?>" min="3" max="120">
        <label><?= t('ad_animation') ?></label>
        <select name="animation">
          <?php foreach ($anims as $k => $lbl): ?><option value="<?= $k ?>"><?= e($lbl) ?></option><?php endforeach; ?>
        </select>
        <label><?= t('ad_sort') ?></label>
        <input type="number" name="sort" value="0">
      </div>
    </div>
    <button class="btn mt"><?= t('create') ?></button>
  </form>
</div>

<?php if ($ads): ?>
<div class="card table-wrap">
  <h3><?= t('ads') ?> (<?= count($ads) ?>)</h3>
  <table>
    <tr><th></th><th><?= t('ad_scope') ?></th><th><?= t('ad_title') ?></th><th><?= t('ad_animation') ?></th><th><?= t('ad_duration') ?></th><th><?= t('status') ?></th><th><?= t('actions') ?></th></tr>
    <?php foreach ($ads as $a): ?>
    <tr>
      <td>
        <?php if ($a['type'] === 'image' && $a['image']): ?>
          <img src="<?= APP_URL . '/' . e($a['image']) ?>" alt="" style="height:36px;border-radius:4px">
        <?php else: ?>
          <span class="muted" style="font-size:.85rem"><?= icon('message', 13) ?> <?= e(mb_substr((string)$a['text_content'], 0, 40)) ?></span>
        <?php endif; ?>
      </td>
      <td><?= $a['scope'] === 'global' ? '<span class="badge blue">' . t('ad_scope_global') . '</span>' : '<span class="badge gold">' . e($a['t_name'] ?? '?') . '</span>' ?></td>
      <td><?= e($a['title'] ?? '—') ?></td>
      <td><?= e($anims[$a['animation']]) ?></td>
      <td><?= (int)$a['duration_sec'] ?>s</td>
      <td><span class="badge <?= $a['active'] ? 'green' : 'grey' ?>"><?= $a['active'] ? 'ON' : 'OFF' ?></span></td>
      <td style="white-space:nowrap">
        <form class="inline-form" method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn sm secondary"><?= icon($a['active'] ? 'pause' : 'play', 12) ?></button></form>
        <form class="inline-form" method="post" data-confirm="<?= t('confirm_delete') ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn sm danger"><?= icon('x', 13) ?></button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<div class="card">
  <h3><?= t('ads_mode_title') ?></h3>
  <p class="muted"><?= t('ads_mode_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="modes">
    <div class="table-wrap"><table>
      <tr><th><?= t('tournament') ?></th><th><?= t('ads_mode_title') ?></th></tr>
      <?php foreach ($tournaments as $tt): ?>
      <tr>
        <td><b><?= e($tt['name']) ?></b></td>
        <td>
          <select name="mode[<?= $tt['id'] ?>]" style="max-width:340px">
            <option value="both" <?= $tt['ads_mode'] === 'both' ? 'selected' : '' ?>><?= t('ads_mode_both') ?></option>
            <option value="tournament" <?= $tt['ads_mode'] === 'tournament' ? 'selected' : '' ?>><?= t('ads_mode_tournament') ?></option>
            <option value="general" <?= $tt['ads_mode'] === 'general' ? 'selected' : '' ?>><?= t('ads_mode_general') ?></option>
            <option value="none" <?= $tt['ads_mode'] === 'none' ? 'selected' : '' ?>><?= t('ads_mode_none') ?></option>
          </select>
        </td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <button class="btn mt"><?= t('save') ?></button>
  </form>
</div>
<?php view_footer();
