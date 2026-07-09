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
- **Publicidad**: tabla `ads` (global o por torneo) + `tournaments.ads_mode`; se administra en `/admin/ads`. `render_ads_bar($tid)` (src/ads.php) imprime la barra en `division_view` y `match_display`; `ads.js` rota con animaciones slide/fade/zoom/ticker y duración por aviso.
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
