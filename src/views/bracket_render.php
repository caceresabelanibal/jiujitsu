<?php
/** Renderiza la llave de una division (usada en gestion y en vista proyector) */
function render_bracket(int $divisionId, bool $manage = false): void {
    $matches = rows('SELECT m.*, r1.name red_name, r2.name blue_name, a1.name red_academy, a2.name blue_academy
                     FROM matches m
                     LEFT JOIN registrations r1 ON r1.id = m.red_reg_id
                     LEFT JOIN registrations r2 ON r2.id = m.blue_reg_id
                     LEFT JOIN tournament_academies a1 ON a1.id = r1.academy_id
                     LEFT JOIN tournament_academies a2 ON a2.id = r2.academy_id
                     WHERE m.division_id = ? ORDER BY m.round, m.is_bronze, m.slot', [$divisionId]);
    if (!$matches) { echo '<p class="muted">' . t('no_competitors') . '</p>'; return; }

    $rounds = [];
    $bronze = null;
    $maxRound = 0;
    foreach ($matches as $m) {
        if ($m['is_bronze']) { $bronze = $m; continue; }
        $rounds[(int)$m['round']][] = $m;
        $maxRound = max($maxRound, (int)$m['round']);
    }

    echo '<div class="bracket-scroll"><div class="bracket">';
    foreach ($rounds as $rnum => $ms) {
        $label = $rnum === $maxRound ? t('final') : ($rnum === $maxRound - 1 ? t('semifinal') : t('round') . ' ' . $rnum);
        echo '<div class="b-round"><h4>' . e($label) . '</h4>';
        foreach ($ms as $m) render_bracket_match($m, $manage);
        // Bronce junto a la final
        if ($rnum === $maxRound && $bronze) {
            echo '<div class="b-match b-bronze"><h5>🥉 ' . t('bronze_match') . '</h5></div>';
            render_bracket_match($bronze, $manage, true);
        }
        echo '</div>';
    }
    echo '</div></div>';

    [$g, $s, $b] = division_podium($divisionId);
    if ($g) {
        echo '<div class="podium">';
        foreach ([['g', '🥇', t('champion'), $g], ['s', '🥈', t('second_place'), $s], ['b', '🥉', t('third_place'), $b]] as [$cls, $medal, $label, $regId]) {
            if (!$regId) continue;
            $reg = row('SELECT r.name, a.name academy FROM registrations r LEFT JOIN tournament_academies a ON a.id=r.academy_id WHERE r.id=?', [$regId]);
            echo '<div class="p ' . $cls . '"><div class="medal">' . $medal . '</div><b>' . e($reg['name']) . '</b><br><small class="muted">' . e($reg['academy'] ?? '') . '</small><br><small>' . e($label) . '</small></div>';
        }
        echo '</div>';
    }
}

function render_bracket_match(array $m, bool $manage, bool $isBronze = false): void {
    $live = $m['status'] === 'live';
    echo '<div class="b-match' . ($live ? ' live' : '') . ($isBronze ? ' b-bronze' : '') . '">';
    foreach ([['red', $m['red_reg_id'], $m['red_name'], $m['red_academy'], $m['red_points']],
              ['blue', $m['blue_reg_id'], $m['blue_name'], $m['blue_academy'], $m['blue_points']]] as [$side, $regId, $name, $academy, $pts]) {
        $isWinner = $m['winner_reg_id'] && $m['winner_reg_id'] == $regId;
        $cls = 'b-side' . ($isWinner ? ' winner' : '') . (!$regId ? ' empty' : '');
        echo '<div class="' . $cls . '"><span class="who">' . ($regId ? e($name) . ($academy ? '<small>' . e($academy) . '</small>' : '') : t('tbd')) . '</span>';
        if ($m['status'] === 'done' && $regId && $m['red_reg_id'] && $m['blue_reg_id']) {
            echo '<span class="pts">' . (int)$pts . '</span>';
        }
        if ($isWinner) echo ' 🏆';
        echo '</div>';
    }
    if ($manage && $m['red_reg_id'] && $m['blue_reg_id'] && $m['status'] !== 'done') {
        echo '<a class="mlink" href="' . APP_URL . '/match/' . $m['id'] . '/operator">⏱ ' . t('operator') . '</a>';
    }
    if ($m['status'] === 'done' && $m['method'] && $m['method'] !== 'wo') {
        echo '<a class="mlink muted">' . e(t($m['method'] === 'points' ? 'by_points' : $m['method'])) . '</a>';
    }
    echo '</div>';
}
