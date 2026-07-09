<?php
/**
 * Publicidad rotativa en llaves proyectadas y marcadores.
 * Se administra desde /admin/ads (admin general del sitio).
 * ads_mode del torneo: none | tournament (solo propias) | general (solo globales) | both.
 */
function ads_for_tournament(int $tournamentId): array {
    $t = row('SELECT ads_mode FROM tournaments WHERE id = ?', [$tournamentId]);
    $mode = $t['ads_mode'] ?? 'both';
    if ($mode === 'none') return [];

    $where = match ($mode) {
        'tournament' => '(scope = "tournament" AND tournament_id = ?)',
        'general'    => '(scope = "global")',
        default      => '(scope = "global" OR (scope = "tournament" AND tournament_id = ?))',
    };
    $args = $mode === 'general' ? [] : [$tournamentId];
    return rows("SELECT * FROM ads WHERE active = 1 AND $where ORDER BY sort, id", $args);
}

/** Barra fija inferior con la rotacion de publicidades (no imprime nada si no hay) */
function render_ads_bar(int $tournamentId): void {
    $ads = ads_for_tournament($tournamentId);
    if (!$ads) return;
    $payload = array_map(fn($a) => [
        'type' => $a['type'],
        'title' => $a['title'],
        'text' => $a['text_content'],
        'image' => $a['image'] ? APP_URL . '/' . $a['image'] : null,
        'duration' => max(3, (int)$a['duration_sec']),
        'animation' => $a['animation'],
    ], $ads);
    echo '<div class="adsbar" id="adsbar"></div>';
    echo '<script>window.ADS = ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>';
    echo '<script src="' . APP_URL . '/assets/js/ads.js"></script>';
}
