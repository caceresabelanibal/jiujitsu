<?php
/** Paleta de acentos por ronda (se repite si hay mas rondas que colores) */
const BRACKET_PALETTE = ['#4f8cff', '#c9252c', '#ffb347', '#30a46c', '#a855f7', '#14b8a6'];

/**
 * Renderiza la llave de una division (usada en gestion y en vista proyector).
 * Emite data-id/data-next/data-bronze en cada partido para que bracket.js
 * dibuje las lineas de conexion con SVG (independiente del alto real de
 * cada tarjeta, que varia segun el contenido).
 */
function render_bracket(int $divisionId, bool $manage = false): void {
    $matches = rows('SELECT m.*, r1.name red_name, r2.name blue_name, a1.name red_academy, a2.name blue_academy
                     FROM matches m
                     LEFT JOIN registrations r1 ON r1.id = m.red_reg_id
                     LEFT JOIN registrations r2 ON r2.id = m.blue_reg_id
                     LEFT JOIN tournament_academies a1 ON a1.id = r1.academy_id
                     LEFT JOIN tournament_academies a2 ON a2.id = r2.academy_id
                     WHERE m.division_id = ? ORDER BY m.round, m.is_bronze, m.slot', [$divisionId]);
    if (!$matches) { echo '<p class="muted center">' . t('no_competitors') . '</p>'; return; }

    $rounds = [];
    $bronze = null;
    $maxRound = 0;
    foreach ($matches as $m) {
        if ($m['is_bronze']) { $bronze = $m; continue; }
        $rounds[(int)$m['round']][] = $m;
        $maxRound = max($maxRound, (int)$m['round']);
    }

    [$g, $s, $b] = division_podium($divisionId);

    echo '<div class="bracket-layout">';
    echo '<div class="bracket-scroll"><div class="bracket" id="bracket-svg-root">';
    echo '<svg class="bracket-lines" id="bracket-lines"></svg>';
    $ri = 0;
    foreach ($rounds as $rnum => $ms) {
        $label = $rnum === $maxRound ? t('final') : ($rnum === $maxRound - 1 ? t('semifinal') : t('round') . ' ' . $rnum);
        $accent = BRACKET_PALETTE[$ri % count(BRACKET_PALETTE)];
        echo '<div class="b-round" style="--accent:' . $accent . '"><h4>' . e($label) . '</h4>';
        foreach ($ms as $m) render_bracket_match($m, $manage);
        // Bronce junto a la final
        if ($rnum === $maxRound && $bronze) {
            echo '<div class="b-match b-bronze-label"><h5>' . icon('award', 13, 'ic-bronze') . ' ' . t('bronze_match') . '</h5></div>';
            render_bracket_match($bronze, $manage, true);
        }
        echo '</div>';
        $ri++;
    }
    echo '</div></div>';

    // Podio a la derecha (no debajo) para no sumar alto y forzar scroll
    // cuando la division termina.
    if ($g) {
        echo '<div class="podium-side">';
        foreach ([['g', 'ic-gold', t('champion'), $g], ['s', 'ic-silver', t('second_place'), $s], ['b', 'ic-bronze', t('third_place'), $b]] as [$cls, $medalCls, $label, $regId]) {
            if (!$regId) continue;
            $reg = row('SELECT r.name, a.name academy FROM registrations r LEFT JOIN tournament_academies a ON a.id=r.academy_id WHERE r.id=?', [$regId]);
            echo '<div class="p ' . $cls . '"><div class="medal">' . icon('award', 26, $medalCls) . '</div><b>' . e($reg['name']) . '</b><br><small class="muted">' . e($reg['academy'] ?? '') . '</small><br><small>' . e($label) . '</small></div>';
        }
        echo '</div>';
    }
    echo '</div>';
    echo '<script>if (window.fitBracket) fitBracket();</script>';
}

function render_bracket_match(array $m, bool $manage, bool $isBronze = false): void {
    $live = $m['status'] === 'live';
    $attrs = ' data-id="' . (int)$m['id'] . '"';
    if (!empty($m['next_match_id'])) $attrs .= ' data-next="' . (int)$m['next_match_id'] . '"';
    if (!empty($m['bronze_match_id'])) $attrs .= ' data-bronze="' . (int)$m['bronze_match_id'] . '"';
    echo '<div class="b-match' . ($live ? ' live' : '') . ($isBronze ? ' b-bronze' : '') . '"' . $attrs . '>';
    foreach ([['red', $m['red_reg_id'], $m['red_name'], $m['red_academy'], $m['red_points']],
              ['blue', $m['blue_reg_id'], $m['blue_name'], $m['blue_academy'], $m['blue_points']]] as [$side, $regId, $name, $academy, $pts]) {
        $isWinner = $m['winner_reg_id'] && $m['winner_reg_id'] == $regId;
        $cls = 'b-side' . ($isWinner ? ' winner' : '') . (!$regId ? ' empty' : '');
        echo '<div class="' . $cls . '"><span class="who">' . ($regId ? e($name) . ($academy ? '<small>' . e($academy) . '</small>' : '') : t('tbd')) . '</span>';
        if ($m['status'] === 'done' && $regId && $m['red_reg_id'] && $m['blue_reg_id']) {
            echo '<span class="pts">' . (int)$pts . '</span>';
        }
        if ($isWinner) echo ' ' . icon('trophy', 13, 'ic-gold');
        echo '</div>';
    }
    if ($manage && $m['red_reg_id'] && $m['blue_reg_id'] && $m['status'] !== 'done') {
        echo '<a class="mlink" href="' . APP_URL . '/match/' . $m['id'] . '/operator">' . icon('timer', 12) . ' ' . t('operator') . '</a>';
    }
    if ($m['status'] === 'done' && $m['method'] && $m['method'] !== 'wo') {
        echo '<a class="mlink muted">' . e(t($m['method'] === 'points' ? 'by_points' : $m['method'])) . '</a>';
    }
    echo '</div>';
}
