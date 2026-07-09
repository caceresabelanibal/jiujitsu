# Taninzu (taninzu.com)

Plataforma de torneos de Jiu-Jitsu: PHP 8.3 puro (sin framework) + MySQL 8 + Docker. UI bilingüe es/en. Logo: cinturón rojo (`public/assets/img/logo.svg`; versión PNG para PDFs se genera con GD en `taninzu_logo_png()`).

## Arquitectura

- `public/index.php` — router por regex; cada ruta mapea a `src/pages/<nombre>.php` (recibe `$params` con las capturas).
- `src/bootstrap.php` — carga todo; en páginas ya está disponible: `db()/q()/row()/rows()/scalar()`, `t()` (i18n), `e()`, `current_user()`, `require_login()/require_admin()/require_tournament_owner()`, `csrf_field()/csrf_check()`, `view_header()/view_footer()`.
- `src/bracket.php` — generación de llaves (eliminación simple + byes + bronce), `advance_winner()`, `propagate_byes()`, `division_podium()`.
- `src/certificates.php` — PDFs con dompdf en `storage/certificates/`.
- `src/ranking.php` — `recompute_rankings()` (lo corre el cron).
- `public/cron.php?task=emails|rankings|cleanup&key=CRON_KEY` — tareas programadas; se listan en `/admin/scheduler`.
- Settings clave-valor JSON en tabla `settings` (`setting()`/`set_setting()`): `scoring`, `ranking`, `tournament_weekly_limit`.
- Timer del marcador vive en el servidor (`matches.timer_remaining` + `timer_started_at`); el JS solo interpola. API en `/api/match/{id}`.
- `/tournament/{id}` es el **centro de operación** ("Ir al torneo": luchas en vivo, próximas, divisiones); la edición vive en `/tournament/{id}/settings`. `require_tournament_owner()` acepta dueño, admin **y personal del torneo** (`tournament_staff`, se agrega por email en Configuración).
- Tema claro/oscuro por `localStorage` + `data-theme` en `<html>` (script inline en `view_header` evita flash); el scoreboard `.sb` queda siempre oscuro (proyector).
- **APP_URL se autodetecta por request** (`resolve_app_url()` en bootstrap.php): usa `getenv('APP_URL')` solo si está explícitamente seteada (producción, ej. `https://taninzu.com`); si no, toma `$_SERVER['HTTP_HOST']` de cada request. Así funciona igual por `localhost`, una IP de LAN o el dominio real, sin que assets/redirects queden pegados a un host fijo. En CLI (scripts/cron sin request) cae a `http://localhost:8080`. **No** volver a hardcodear `APP_URL` en `docker-compose.yml` salvo que sea para forzar un dominio de producción.
- **Responsive**: `.table-wrap table` se apila como tarjetas (`display:block` por fila, header oculto vía `:has(th)`) por debajo de 680px — ver reglas en `app.css` cerca de `.table-wrap`. `.grid.cols2/3/4` fuerza una columna por debajo de 480px (el `auto-fit` de CSS Grid puede crear columnas más chicas que su propio `minmax` mínimo en viewports muy angostos, desbordando el contenido). Al probar mobile con headless Chrome/Edge en Windows, `--window-size` puede tener un piso mínimo (~490px en este entorno) — para simular un viewport angosto real, forzar el ancho por CSS en un iframe (`iframe.style.width = '390px'`) en vez de confiar en `--window-size`.
- **Publicidad**: tabla `ads` (global o por torneo) + `tournaments.ads_mode`; se administra en `/admin/ads`. `render_ads_bar($tid, $double=false)` (src/ads.php) imprime la barra en `division_view` y `match_display` (con `$double=true` en ambas: cinta arriba y abajo); `ads.js` rota con animaciones slide/fade/zoom/ticker y duración por aviso.
- **Llaves (bracket)**: `bracket_render.php` emite `data-id`/`data-next`/`data-bronze` en cada `.b-match` y un `--accent` (color por ronda) en cada `.b-round`; `bracket.js` mide la posición real de las tarjetas con `getBoundingClientRect()` y dibuja las líneas de conexión en un `<svg>` — no depende de alturas fijas. La vista proyector (`division_view.php`) NO usa meta-refresh: hace `fetch(...?_fragment=1)` cada 15s y reemplaza solo `#bracket-region` (la publicidad y el header quedan afuera), así no resetea el scroll del usuario. El layout usa `body.proj` + flexbox (`height:100vh; overflow:hidden`) para que el header y las cintas de ads queden fijos y solo la llave scrollee internamente si no entra completa.
  - `fitBracket()` (bracket.js) mide `#bracket-region` (alto disponible vs alto natural del contenido) y calcula un factor de zoom (`--bz`, piso 0.55) que aplica como custom property CSS; toda la geometría vertical de `.bracket-scroll`/`.b-round`/`.b-match`/`.b-side`/`.podium` está en `calc(Npx * var(--bz, 1))` en app.css, así la llave se achica lo justo para entrar sin scroll vertical (con rondas chicas normalmente no hace falta escala; con muchos partidos en ronda 1 sí). Llamar siempre `fitBracket()` (no `drawBracketLines()` directo) después de tocar el DOM de la llave — ya internamente llama a `drawBracketLines(factor)` con el zoom aplicado para que las líneas coincidan.
- **Marcador (scoreboard)**: mismo problema/solución que las llaves pero por otra vía — `.sb-timer`/`.sb-points` usaban `vh` crudo (no sabe restar el alto de las cintas de ads). `ads.js` mide el alto real de las cintas y lo expone como `--ads-h` en `<html>`; `.sb` define `--avail: calc(100vh - var(--ads-h,0px))` y el timer/puntos usan `calc(var(--avail) * fraccion)` en vez de `Nvh`. La barra de ganador (`.sb-winnerbar`) también se mide al mostrarla (`--wbar-h` en scoreboard.js) y se resta de `--sides-h` (mitad de `--avail` menos el ganador) para que el ADV/PEN de cada lado no quede tapado cuando termina la lucha. `body.sbpage` (igual que `body.proj`) fija `height:100vh;overflow:hidden` como red de seguridad.
- **Certificados**: dompdf con fuentes en `public/assets/fonts` (gótica + caligráfica, OFL), font cache en `storage/fonts`, patrón guilloche y logo generados con GD (cacheados como `storage/certificates/_pattern.png` y `_logo.png` — borrarlos para regenerar), código de verificación HMAC en `certificates.code`.

## Comandos

```bash
docker compose up -d --build          # levantar (app :8080, mailhog :8025, pma :8081)
docker compose exec app php scripts/seed_demo.php   # datos demo (re-ejecutable)
docker compose exec app composer install             # deps dentro del contenedor
```

Credenciales demo: admin@demo.local/admin123 · organizador@demo.local/demo123.

## Convenciones

- Textos SIEMPRE via `t('clave')` con la clave en `src/lang/es.php` y `src/lang/en.php` (fallback: muestra la clave).
- Datos de referencia (cinturones, edades, pesos) viven en la DB (`belts`, `age_divisions`, `weight_classes`), sembrados por `db/schema.sql`.
- Identidad de competidor para ranking: email en minúsculas.
- Byes = matches con un solo competidor y `method='wo'`; las "luchas reales" exigen `red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL`.
