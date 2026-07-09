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

/**
 * Barra fija con la rotacion de publicidades (no imprime nada si no hay).
 * $double: cinta arriba Y abajo (para el marcador proyectado, por si el proyector recorta).
 */
function render_ads_bar(int $tournamentId, bool $double = false): void {
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
    if ($double) echo '<div class="adsbar top"></div>';
    echo '<div class="adsbar"></div>';
    echo '<script>window.ADS = ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>';
    echo '<script src="' . asset('/assets/js/ads.js') . '"></script>';
}
