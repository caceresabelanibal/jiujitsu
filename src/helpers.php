<?php
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * URL de un asset estatico (css/js) con cache-busting: le agrega
 * ?v=<fecha de modificacion> para que el navegador (sobre todo en celulares,
 * que cachean bastante mas agresivo) baje la version nueva apenas se
 * modifica el archivo, sin depender de que el usuario borre cache a mano.
 */
function asset(string $path): string {
    $file = BASE_PATH . '/public' . $path;
    $v = file_exists($file) ? filemtime($file) : time();
    return APP_URL . $path . '?v=' . $v;
}

function redirect(string $path): never {
    header('Location: ' . (str_starts_with($path, 'http') ? $path : APP_URL . $path));
    exit;
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf'] ?? '') . '">';
}

function csrf_check(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) {
        http_response_code(419);
        die('CSRF token mismatch');
    }
}

function json_out($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function slug_token(int $len = 10): string {
    return substr(str_replace(['+','/','='], '', base64_encode(random_bytes(16))), 0, $len);
}

/** Edad al 31/12 del año en curso (criterio IBJJF) */
function competition_age(string $birthdate): int {
    return (int)date('Y') - (int)substr($birthdate, 0, 4);
}

function find_age_division(int $age): ?array {
    return row('SELECT * FROM age_divisions WHERE min_age <= ? AND (max_age IS NULL OR max_age >= ?) ORDER BY sort LIMIT 1', [$age, $age]);
}

/**
 * A partir de que edad (al 31/12) se considera infantil/juvenil en cada torneo;
 * adulto arranca despues de `juvenile_max`. Configurable en /admin/settings
 * (general) y por torneo en /tournament/{id}/settings o al crearlo (sobreescribe
 * el general si se guarda) — solo afecta inscripciones nuevas, no reclasifica
 * las que ya estan cargadas.
 */
function age_threshold_defaults(): array {
    return ['kids_max' => 15, 'juvenile_max' => 17];
}

function age_threshold_sanitize($v): array {
    $d = age_threshold_defaults();
    $kidsMax = is_array($v) ? (int)($v['kids_max'] ?? 0) : 0;
    $juvMax = is_array($v) ? (int)($v['juvenile_max'] ?? 0) : 0;
    if ($kidsMax < 3 || $kidsMax > 17) $kidsMax = $d['kids_max'];
    if ($juvMax <= $kidsMax || $juvMax > 20) $juvMax = max($kidsMax + 1, $d['juvenile_max']);
    return ['kids_max' => $kidsMax, 'juvenile_max' => $juvMax];
}

function age_thresholds_global(): array {
    return age_threshold_sanitize(setting('age_thresholds', null));
}

/** Umbrales efectivos para un torneo: su propio override si guardo uno, si no el general */
function age_thresholds_for(?array $tournament): array {
    if ($tournament && !empty($tournament['age_thresholds'])) {
        $decoded = json_decode((string)$tournament['age_thresholds'], true);
        if (is_array($decoded)) return age_threshold_sanitize($decoded);
    }
    return age_thresholds_global();
}

/** Igual que find_age_division() pero respetando los umbrales infantil/juvenil/adulto del torneo */
function find_age_division_for(int $age, array $thresholds): ?array {
    if ($age <= $thresholds['kids_max']) {
        return row('SELECT * FROM age_divisions WHERE is_kids=1 AND min_age <= ? ORDER BY sort DESC LIMIT 1', [$age]);
    }
    if ($age <= $thresholds['juvenile_max']) {
        return row("SELECT * FROM age_divisions WHERE code = 'juvenil' LIMIT 1");
    }
    return find_age_division(max($age, 18));
}

function find_weight_class(string $gender, float $kg, bool $kids): ?array {
    $g = $kids ? 'A' : $gender;
    $wc = row('SELECT * FROM weight_classes WHERE gender = ? AND is_absolute = 0 AND max_kg IS NOT NULL AND max_kg >= ? ORDER BY max_kg LIMIT 1', [$g, $kg]);
    if (!$wc) {
        $wc = row('SELECT * FROM weight_classes WHERE gender = ? AND is_absolute = 0 AND max_kg IS NULL ORDER BY sort LIMIT 1', [$g]);
    }
    return $wc;
}

function belt_name(array $belt): string {
    return loc_name($belt);
}

/** Nombre localizado (name_es/name_en/name_pt) de una fila de referencia; cae a name_es si falta el idioma */
function loc_name(array $rowData): string {
    $v = $rowData['name_' . lang()] ?? '';
    return $v !== '' && $v !== null ? $v : $rowData['name_es'];
}

/** Enlace contextual al centro de ayuda (/help#ancla) — para pantallas donde el usuario puede perderse */
function help_link(string $anchor): string {
    return '<a class="helplink" href="' . APP_URL . '/help#' . e($anchor) . '" title="' . e(t('help')) . '">'
        . icon('help', 14) . ' ' . e(t('help_link_label')) . '</a>';
}

/**
 * Variante para columnas ya alias-adas en una query (b_es/b_en/b_pt, a_es...):
 * loc_col($row, 'b') elige $row['b_<lang>'] cayendo a $row['b_es'].
 */
function loc_col(array $rowData, string $prefix): string {
    $v = $rowData[$prefix . '_' . lang()] ?? '';
    return $v !== '' && $v !== null ? $v : ($rowData[$prefix . '_es'] ?? '');
}

/**
 * Lista drag-and-drop (ver public/assets/js/dragorder.js) para ordenar
 * categorias. $prefix + "_" + clave = nombre del input hidden que se manda
 * con el form (ej. "div_ord_black", "age_ord_adulto", "wt_ord_w70").
 */
function render_drag_order(string $prefix, array $labels, array $order): void {
    echo '<ul class="dragorder">';
    foreach ($order as $i => $key) {
        $label = $labels[$key] ?? $key;
        echo '<li data-key="' . e($key) . '">'
           . '<span class="dragorder-pos">' . ($i + 1) . '</span>'
           . icon('grip', 15, 'dragorder-handle')
           . '<span class="dragorder-label">' . e($label) . '</span>'
           . '<input type="hidden" name="' . e($prefix) . '_' . e($key) . '" value="' . ($i + 1) . '">'
           . '</li>';
    }
    echo '</ul>';
}

/**
 * Colores fijos de las 4 categorias NoGi (mismo criterio que el belt-chip de
 * gi: blanco/violeta/negro para amateur/semipro/pro, amarillo para infantil/juvenil).
 */
function nogi_category_colors(): array {
    return [
        'kids_juvenile' => ['bg' => '#f2c500', 'fg' => '#1a1a1a'],
        'amateur' => ['bg' => '#f5f5f5', 'fg' => '#1a1a1a'],
        'semipro' => ['bg' => '#7b2fbe', 'fg' => '#ffffff'],
        'pro' => ['bg' => '#1a1a1a', 'fg' => '#ffffff'],
    ];
}

/** Ovalo de color envolviendo una categoria NoGi (infantil/juvenil o nivel) */
function nogi_category_badge(string $key, string $label): string {
    $c = nogi_category_colors()[$key] ?? null;
    if (!$c) return e($label);
    return '<span class="badge" style="background:' . e($c['bg']) . ';color:' . e($c['fg']) . ';border:1px solid rgba(128,128,128,.4)">' . e($label) . '</span>';
}

/**
 * Cinturon (gi) o categoria (nogi: infantil/juvenil o nivel) de una division,
 * ya localizado. $html=true envuelve infantil/juvenil o el nivel en el ovalo
 * de color (nogi_category_badge()) para mostrar en pantalla; $html=false
 * (default) devuelve texto plano para contextos que no toleran HTML (ej.
 * <title>). En NoGi el cinturon NO es el parametro real — antes esta funcion
 * devolvia '' para infantil/juvenil NoGi (sin cinturon ni nivel), ahora
 * siempre devuelve algo que distingue la categoria.
 */
function division_belt_or_tier_label(array $d, bool $html = false): string {
    if (!empty($d['tier'])) {
        $label = nogi_tier_labels()[$d['tier']] ?? $d['tier'];
        return $html ? nogi_category_badge($d['tier'], $label) : $label;
    }
    if (!empty($d['belt_id'])) {
        $belt = row('SELECT * FROM belts WHERE id = ?', [$d['belt_id']]);
        return $belt ? loc_name($belt) : '';
    }
    // nogi infantil/juvenil: sin cinturon ni tier (los dos NULL)
    $label = t('div_order_kids_juvenile');
    return $html ? nogi_category_badge('kids_juvenile', $label) : $label;
}

/**
 * Categoria de una division sin el genero (edad · cinturon/nivel · peso,
 * absoluto o nombre especial). $html=true devuelve HTML ya escapado (con el
 * ovalo de color de nogi_category_badge() incluido) — el caller NO debe
 * volver a pasarlo por e(); $html=false (default) devuelve texto plano, como
 * siempre, y el caller sigue siendo responsable de escaparlo.
 */
function division_category_label(array $d, bool $html = false): string {
    if (($d['kind'] ?? 'standard') === 'special') {
        $name = $d['name'] ?: t('special_category');
        return $html ? e($name) : $name;
    }
    $beltOrTier = division_belt_or_tier_label($d, $html);
    if (($d['kind'] ?? 'standard') === 'absolute') {
        $absLabel = $html ? e(t('absolute_category')) : t('absolute_category');
        return $beltOrTier ? $absLabel . ' · ' . $beltOrTier : $absLabel;
    }
    $age = !empty($d['age_division_id']) ? row('SELECT * FROM age_divisions WHERE id = ?', [$d['age_division_id']]) : null;
    $wc  = !empty($d['weight_class_id']) ? row('SELECT * FROM weight_classes WHERE id = ?', [$d['weight_class_id']]) : null;
    $parts = [];
    if ($age) $parts[] = $html ? e(loc_name($age)) : loc_name($age);
    if ($beltOrTier) $parts[] = $beltOrTier;
    if ($wc) $parts[] = $html ? e(loc_name($wc)) : loc_name($wc);
    return implode(' · ', $parts);
}

function division_label(array $d, bool $html = false): string {
    $g = $d['gender'] === 'M' ? t('male') : t('female');
    return ($html ? e($g) : $g) . ' · ' . division_category_label($d, $html);
}

function fmt_time(int $sec): string {
    return sprintf('%d:%02d', intdiv($sec, 60), $sec % 60);
}

function upload_image(string $field, string $prefix): ?string {
    if (empty($_FILES[$field]['tmp_name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $info = @getimagesize($_FILES[$field]['tmp_name']);
    if ($info === false) return null;
    $ext = image_type_to_extension($info[2], false);
    if (!in_array($ext, ['png','jpeg','gif','webp'])) return null;
    $name = $prefix . '-' . slug_token(8) . '.' . $ext;
    $dir = BASE_PATH . '/public/uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    move_uploaded_file($_FILES[$field]['tmp_name'], "$dir/$name");
    return 'uploads/' . $name;
}
