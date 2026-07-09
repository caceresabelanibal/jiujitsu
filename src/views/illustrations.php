<?php
/**
 * Ilustraciones vectoriales estilizadas para la landing (trazo grueso, flat).
 * Colores via CSS: .fig-a (luchador 1), .fig-b (luchador 2), .belt-r (cinto rojo), .mat (tatami).
 */

/** Proyeccion de judo/BJJ con kimono (seoi nage) */
function svg_gi_throw(): string {
    return <<<SVG
<svg class="art" viewBox="0 0 340 280" fill="none" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="BJJ gi throw">
  <ellipse class="mat" cx="175" cy="264" rx="150" ry="10"/>
  <!-- uke (proyectado por el aire, kimono azul) -->
  <g class="fig-b">
    <circle cx="95" cy="95" r="13" fill="currentColor" stroke="none"/>
    <path d="M107 101 Q142 48 186 60" stroke="currentColor" stroke-width="24"/>
    <path d="M186 60 L222 42 L254 60" stroke="currentColor" stroke-width="14"/>
    <path d="M186 62 L214 86 L248 92" stroke="currentColor" stroke-width="14"/>
    <path d="M110 106 L122 146" stroke="currentColor" stroke-width="12"/>
    <path d="M109 97 L142 114" stroke="currentColor" stroke-width="12"/>
  </g>
  <path class="belt-d" d="M148 55 L166 61" stroke-width="8"/>
  <!-- tori (ejecuta la proyeccion, kimono claro + cinto rojo) -->
  <g class="fig-a">
    <circle cx="185" cy="118" r="15" fill="currentColor" stroke="none"/>
    <path d="M190 134 L172 192" stroke="currentColor" stroke-width="30"/>
    <path d="M172 192 L145 226 L150 262" stroke="currentColor" stroke-width="16"/>
    <path d="M176 192 L205 231 L198 264" stroke="currentColor" stroke-width="16"/>
    <path d="M188 138 L228 100" stroke="currentColor" stroke-width="14"/>
    <path d="M182 141 L145 118" stroke="currentColor" stroke-width="14"/>
  </g>
  <path class="belt-r" d="M162 172 L196 179" stroke-width="9"/>
</svg>
SVG;
}

/** Grappling sin kimono en el piso (pasaje de guardia) */
function svg_nogi_grapple(): string {
    return <<<SVG
<svg class="art" viewBox="0 0 340 220" fill="none" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="No-gi grappling">
  <ellipse class="mat" cx="170" cy="200" rx="155" ry="10"/>
  <!-- abajo: jugando guardia -->
  <g class="fig-b">
    <circle cx="58" cy="162" r="14" fill="currentColor" stroke="none"/>
    <path d="M74 165 L142 152" stroke="currentColor" stroke-width="26"/>
    <path d="M142 152 L182 96 L222 106" stroke="currentColor" stroke-width="15"/>
    <path d="M146 156 L192 168 L224 142" stroke="currentColor" stroke-width="15"/>
    <path d="M94 158 L130 122" stroke="currentColor" stroke-width="12"/>
  </g>
  <!-- arriba: pasando la guardia -->
  <g class="fig-a">
    <circle cx="228" cy="58" r="14" fill="currentColor" stroke="none"/>
    <path d="M222 74 L192 126" stroke="currentColor" stroke-width="26"/>
    <path d="M214 82 L162 118" stroke="currentColor" stroke-width="13"/>
    <path d="M227 80 L254 130" stroke="currentColor" stroke-width="13"/>
    <path d="M196 128 L246 150 L236 190" stroke="currentColor" stroke-width="15"/>
    <path d="M200 130 L262 116 L292 160" stroke="currentColor" stroke-width="15"/>
  </g>
</svg>
SVG;
}
