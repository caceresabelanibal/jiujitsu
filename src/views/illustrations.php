<?php
/**
 * Ilustracion vectorial de la landing: silueta de dos luchadores en proyeccion
 * (dominio publico, Openclipart). Se inlinea para poder pintarla con
 * currentColor (se adapta a tema claro/oscuro o a un tinte de marca) y
 * controlar su transparencia por CSS.
 */
function svg_fighters(string $extraClass = ''): string {
    static $raw = null;
    if ($raw === null) {
        $raw = file_get_contents(BASE_PATH . '/public/assets/img/fighters.svg');
    }
    $cls = trim('art-fighters ' . $extraClass);
    return preg_replace('/<svg /', '<svg class="' . e($cls) . '" role="img" aria-label="Jiu-Jitsu throw" ', $raw, 1);
}
