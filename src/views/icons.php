<?php
/**
 * Set de iconos SVG minimalistas (trazo 2px, heredan currentColor).
 * Uso: icon('trophy'), icon('award', 22, 'ic-gold')
 */
function icon(string $name, int $size = 16, string $class = ''): string {
    return '<svg class="ic ' . e($class) . '" width="' . $size . '" height="' . $size . '" aria-hidden="true"><use href="#i-' . e($name) . '"></use></svg>';
}

function icons_sprite(): void {
    ?><svg xmlns="http://www.w3.org/2000/svg" style="display:none">
<defs>
<symbol id="i-trophy" viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M6 3h12v5a6 6 0 0 1-12 0V3z"/><path d="M6 5H3v1a4 4 0 0 0 4 4M18 5h3v1a4 4 0 0 1-4 4"/></symbol>
<symbol id="i-timer" viewBox="0 0 24 24"><circle cx="12" cy="14" r="7.5"/><path d="M12 14v-4M9.5 2.5h5M18.5 7l1.5-1.5"/></symbol>
<symbol id="i-screen" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></symbol>
<symbol id="i-bracket" viewBox="0 0 24 24"><rect x="3" y="4" width="5" height="4"/><rect x="3" y="16" width="5" height="4"/><rect x="16" y="10" width="5" height="4"/><path d="M8 6h3v6h5M8 18h3v-6"/></symbol>
<symbol id="i-users" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
<symbol id="i-user" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></symbol>
<symbol id="i-user-check" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11l2 2 4-4"/></symbol>
<symbol id="i-swords" viewBox="0 0 24 24"><path d="M4.5 3.5L18 17M19.5 3.5L6 17"/><path d="M14.7 17.3L18 20.6M17.3 14.7L20.6 18M9.3 17.3L6 20.6M6.7 14.7L3.4 18"/></symbol>
<symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></symbol>
<symbol id="i-chart" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></symbol>
<symbol id="i-award" viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.5 12.8L17 21l-5-3-5 3 1.5-8.2"/></symbol>
<symbol id="i-star" viewBox="0 0 24 24"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.2L5.8 21 7 14.2 2 9.3l6.9-1z"/></symbol>
<symbol id="i-sliders" viewBox="0 0 24 24"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></symbol>
<symbol id="i-clipboard" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></symbol>
<symbol id="i-mail" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></symbol>
<symbol id="i-trash" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></symbol>
<symbol id="i-play" viewBox="0 0 24 24"><path d="M7 4l13 8-13 8z" fill="currentColor" stroke="none"/></symbol>
<symbol id="i-pause" viewBox="0 0 24 24"><path d="M7 4h4v16H7zM13 4h4v16h-4z" fill="currentColor" stroke="none"/></symbol>
<symbol id="i-reset" viewBox="0 0 24 24"><path d="M1 4v6h6"/><path d="M3.5 15a9 9 0 1 0 2-9.4L1 10"/></symbol>
<symbol id="i-undo" viewBox="0 0 24 24"><path d="M9 14L4 9l5-5"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></symbol>
<symbol id="i-check" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></symbol>
<symbol id="i-x" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></symbol>
<symbol id="i-crown" viewBox="0 0 24 24"><path d="M3 7l4.5 5L12 5l4.5 7L21 7v11H3z"/></symbol>
<symbol id="i-megaphone" viewBox="0 0 24 24"><path d="M3 10v4h3l6 5V5l-6 5H3z"/><path d="M16.5 8.5a5 5 0 0 1 0 7M19.5 6a9 9 0 0 1 0 12"/></symbol>
<symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></symbol>
<symbol id="i-link" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></symbol>
<symbol id="i-zap" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></symbol>
<symbol id="i-target" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></symbol>
<symbol id="i-down-trend" viewBox="0 0 24 24"><path d="M23 18l-9.5-9.5-5 5L1 6"/><path d="M17 18h6v-6"/></symbol>
<symbol id="i-plus-circle" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></symbol>
<symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></symbol>
<symbol id="i-message" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></symbol>
<symbol id="i-home" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></symbol>
<symbol id="i-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></symbol>
<symbol id="i-shuffle" viewBox="0 0 24 24"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></symbol>
<symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/></symbol>
<symbol id="i-flag" viewBox="0 0 24 24"><path d="M4 22V4a1 1 0 0 1 1-1c3 0 5 2 8 2s5-1.5 7-1.5V15c-2 0-4 1.5-7 1.5S8 14.5 5 14.5"/></symbol>
<symbol id="i-arrow-right" viewBox="0 0 24 24"><path d="M4 12h16M13 5l7 7-7 7"/></symbol>
<symbol id="i-edit" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></symbol>
</defs>
</svg>
<?php
}
