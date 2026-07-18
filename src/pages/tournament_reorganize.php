<?php
/**
 * Reorganización de llaves (dueño, staff o admin — disponible siempre, con o
 * sin inscripciones cerradas): mover competidores libremente entre divisiones
 * para que nadie quede sin luchar, o declarar ganador automático (walkover)
 * al que quedó solo. Las divisiones con luchas REALES ya jugadas quedan
 * bloqueadas — no se les puede sacar/meter gente para no romper resultados.
 * Al mover, las llaves de origen y destino sin resultados se descartan (hay
 * que volver a generarlas) y las divisiones que quedan vacías se eliminan.
 */
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';

    if ($do === 'move') {
        $reg = row('SELECT * FROM registrations WHERE id = ? AND tournament_id = ? AND verified = 1', [(int)$_POST['reg_id'], $tid]);
        $target = row('SELECT * FROM divisions WHERE id = ? AND tournament_id = ? AND kind = "standard"', [(int)$_POST['target_id'], $tid]);
        if (!$reg || !$target) {
            flash('error', t('forbidden'));
        } else {
            // División estándar actual del inscripto (origen)
            $src = null;
            foreach (find_registrant_divisions($reg, $t) as $dv) {
                if ($dv['kind'] === 'standard') { $src = $dv; break; }
            }
            if ($src && (int)$src['id'] === (int)$target['id']) {
                flash('success', t('settings_saved'));
            } elseif ($src && division_has_played_matches((int)$src['id'])) {
                // El competidor ya luchó en su llave actual: moverlo rompería
                // esos resultados — este sí sigue bloqueado.
                flash('error', t('reorg_move_locked'));
            } else {
                // Descartar las llaves SIN resultados de origen y destino (se
                // regeneran). Si el destino ya tiene luchas jugadas, su llave se
                // deja intacta: el competidor entra a la división igual y el
                // organizador decide si regenerarla (perdiendo esos resultados)
                // desde la página de la división.
                $targetPlayed = division_has_played_matches((int)$target['id']);
                foreach (array_filter([$src ? (int)$src['id'] : null, $targetPlayed ? null : (int)$target['id']]) as $did) {
                    if ((int)scalar('SELECT COUNT(*) FROM matches WHERE division_id=?', [$did]) > 0) {
                        q('DELETE FROM matches WHERE division_id=?', [$did]);
                        q('UPDATE divisions SET status="pending" WHERE id=?', [$did]);
                    }
                }
                // Nuevos campos del inscripto para que caiga en la división destino
                $beltId = (int)$reg['belt_id'];
                if ($target['tier'] !== null) {
                    // nogi por nivel: si su cinturón real no mapea al tier destino,
                    // se le asigna el primer cinturón que sí mapee
                    $map = nogi_tiers_for($t);
                    $curCode = scalar('SELECT code FROM belts WHERE id = ?', [$beltId]);
                    if (($map[$curCode] ?? null) !== $target['tier']) {
                        foreach ($map as $code => $tier) {
                            if ($tier === $target['tier']) {
                                $beltId = (int)scalar('SELECT id FROM belts WHERE code = ?', [$code]);
                                break;
                            }
                        }
                    }
                } elseif ($target['belt_id'] !== null) {
                    $beltId = (int)$target['belt_id'];
                } // belt y tier NULL (nogi infantil/juvenil): el cinturón real no cambia

                // Sin restricción de género: el organizador decide la llave (a veces
                // se arman llaves mixtas o "irrisorias" con tal de que todos luchen).
                // Si la división destino es del otro género, el inscripto pasa a ella.
                q('UPDATE registrations SET gender=?, belt_id=?, age_division_id=?, weight_class_id=? WHERE id=?',
                    [$target['gender'], $beltId, $target['age_division_id'], $target['weight_class_id'], (int)$reg['id']]);
                prune_empty_divisions($tid);
                flash($targetPlayed ? 'warning' : 'success', $targetPlayed ? t('reorg_moved_played') : t('reorg_moved'));
            }
        }
    } elseif ($do === 'declare') {
        if (declare_solo_winner((int)$_POST['division_id'])) {
            recompute_rankings();
            flash('success', t('reorg_declared'));
        } else {
            flash('error', t('reorg_move_locked'));
        }
    }
    redirect("/tournament/$tid/reorganize");
}

ensure_divisions($tid);

$divOrder = $t['discipline'] === 'nogi'
    ? nogi_division_order_case_sql(nogi_division_order_for($t))
    : division_order_case_sql(division_order_for($t));
$ageOrder = age_order_case_sql(age_order_for($t));
$weightOrder = weight_order_case_sql(weight_order_for($t));
$divs = rows("SELECT d.* FROM divisions d
              LEFT JOIN belts b ON b.id=d.belt_id LEFT JOIN age_divisions ad ON ad.id=d.age_division_id LEFT JOIN weight_classes wc ON wc.id=d.weight_class_id
              WHERE d.tournament_id=? ORDER BY $divOrder, d.gender, $ageOrder, b.sort, $weightOrder", [$tid]);

// Pre-calcular estado de cada división
$info = []; // did => [regs, played, hasMatches]
foreach ($divs as $d) {
    $did = (int)$d['id'];
    $info[$did] = [
        'regs' => division_registrations($did),
        'played' => division_has_played_matches($did),
        'matches' => (int)scalar('SELECT COUNT(*) FROM matches WHERE division_id=?', [$did]),
    ];
}
// Destinos posibles: TODAS las divisiones estándar, sin restricción de género
// ni de estado — la elección es libre, la decide el organizador. Las que ya
// tienen luchas jugadas se marcan en la etiqueta (su llave no se toca al mover).
$allTargets = [];
foreach ($divs as $d) {
    if ($d['kind'] === 'standard') $allTargets[] = $d;
}

view_header(t('reorganize_brackets'));
?>
<p><a href="<?= APP_URL ?>/tournament/<?= $tid ?>">← <?= e($t['name']) ?></a></p>
<div class="flex spread">
  <h1><?= icon('shuffle', 22) ?> <?= t('reorganize_brackets') ?></h1>
</div>
<div class="flash flash-warning"><?= icon('flag', 15) ?> <?= t('reorg_hint') ?></div>

<?php foreach ($divs as $d): $did = (int)$d['id']; $i = $info[$did];
      $solo = count($i['regs']) === 1 && $i['matches'] === 0; ?>
<div class="card" <?= $solo ? 'style="border-color:var(--accent2)"' : '' ?>>
  <div class="flex spread">
    <h3 style="margin:0"><?= division_label($d, true) ?>
      <span class="muted" style="font-weight:normal">· <?= count($i['regs']) ?> <?= t('participants') ?></span>
      <?php if ($solo): ?><span class="badge gold"><?= t('reorg_solo') ?></span><?php endif; ?>
      <?php if ($d['status'] === 'done'): ?><span class="badge green"><?= t('status_finished') ?></span>
      <?php elseif ($i['played']): ?><span class="badge grey"><?= t('reorg_locked') ?></span><?php endif; ?>
    </h3>
    <?php if ($solo): ?>
    <form method="post" data-confirm="<?= e(t('confirm_declare_winner')) ?>" style="margin:0">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="declare">
      <input type="hidden" name="division_id" value="<?= $did ?>">
      <button class="btn sm"><?= icon('trophy', 13) ?> <?= t('reorg_declare_btn') ?></button>
    </form>
    <?php endif; ?>
  </div>

  <?php if ($i['played']): ?>
    <p class="muted" style="margin:8px 0 0"><?= t('reorg_move_locked') ?></p>
  <?php elseif ($d['kind'] !== 'standard'): ?>
    <p class="muted" style="margin:8px 0 0"><?= $d['kind'] === 'special' ? t('reorg_special_hint') : t('reorg_absolute_hint') ?></p>
  <?php elseif ($i['regs']): ?>
    <details <?= $solo ? 'open' : '' ?> style="margin-top:8px">
      <summary class="muted" style="cursor:pointer"><?= t('reorg_show_competitors') ?></summary>
      <?php foreach ($i['regs'] as $r):
          $targets = array_filter($allTargets, fn($td) => (int)$td['id'] !== $did); ?>
      <div class="flex spread" style="padding:8px 0;border-bottom:1px solid var(--border);gap:10px;flex-wrap:wrap">
        <span><b><?= e($r['name']) ?></b> <span class="muted"><?= e($r['academy_name'] ?? '') ?></span></span>
        <form method="post" class="flex" style="margin:0;gap:8px" data-confirm="<?= e(t('confirm_move')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="move">
          <input type="hidden" name="reg_id" value="<?= (int)$r['id'] ?>">
          <?php if ($targets): ?>
          <select name="target_id" style="width:auto;max-width:320px">
            <?php foreach ($targets as $td): ?>
            <option value="<?= (int)$td['id'] ?>"><?= e(division_label($td)) ?> (<?= count($info[(int)$td['id']]['regs']) ?>)<?= $info[(int)$td['id']]['played'] ? ' — ' . e(t('reorg_locked')) : '' ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn sm secondary"><?= icon('arrow-right', 12) ?> <?= t('reorg_move_btn') ?></button>
          <?php else: ?>
          <span class="muted"><?= t('reorg_no_targets') ?></span>
          <?php endif; ?>
          <a class="btn sm secondary" href="<?= APP_URL ?>/registration/<?= (int)$r['id'] ?>/edit"><?= icon('settings', 12) ?></a>
        </form>
      </div>
      <?php endforeach; ?>
    </details>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php view_footer();
