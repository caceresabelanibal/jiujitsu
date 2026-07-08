# 🥋 BJJ Tournament Manager

Plataforma web (desktop + mobile) para torneos de Jiu-Jitsu brasileño: llaves, marcador para árbitros con timer, certificados PDF por mail, rankings y dashboard. Disponible en **español e inglés** (detección automática por navegador).

## Stack

- PHP 8.3 + Apache (Docker)
- MySQL 8
- dompdf (certificados PDF) + PHPMailer (SMTP)
- MailHog para ver los mails en desarrollo

## Levantar el entorno

```bash
docker compose up -d --build
# Esperar a que MySQL esté healthy y luego cargar datos demo:
docker compose exec app php scripts/seed_demo.php
```

| Servicio | URL |
|---|---|
| App | http://localhost:8080 |
| MailHog (mails) | http://localhost:8025 |
| phpMyAdmin | http://localhost:8081 |

**Credenciales demo**: `admin@demo.local / admin123` (admin) · `organizador@demo.local / demo123` (organizador del torneo demo).

## Funcionalidades

- **Registro con verificación de email** (una cuenta por dirección). El link de inscripción a un torneo también da de alta al usuario.
- **Torneos**: internos (una academia) u open (varias academias con logos, profesores y sedes). Límite configurable de 1 torneo por semana por usuario.
- **Categorías**: infantiles, juveniles, adultos y Master 1–6, femenino y masculino, cinturones IBJJF (adultos e infantiles) y categorías de peso IBJJF — todo en base de datos, editable.
- **Llaves**: eliminación simple con byes, siembra manual o botón 🎲 aleatorio por división, lucha por el 3er puesto, vista proyector que se auto-refresca.
- **Marcador/timer**: mesa de operador (puntos 2/3/4, ventajas, penalizaciones, deshacer) + display público a pantalla completa para monitor. El ganador se infiere por puntos→ventajas→penalizaciones o lo define el operador (finalización, decisión, DQ, W.O.).
- **Certificados PDF** con medalla, cinturón, academia, torneo y cantidad de luchas; se envían por mail a oro/plata/bronce y participación.
- **Ranking global** por combinación género + cinturón + edad + peso, con puntaje configurable desde el admin.
- **Dashboard del torneo**: academia ganadora, medallero, más luchas, más minutos en tatami, más finalizador, finalización más rápida, etc.
- **Panel admin**: usuarios (crear/editar/eliminar), configuración de puntajes, página de schedulers.
- **Personal del torneo**: el organizador agrega árbitros/mesa por email (Configuración del torneo); ellos ven "▶ Ir al torneo" en su panel y operan llaves, timer y resultados desde sus computadoras.
- **Tema claro/oscuro** con toggle (◐) y transiciones suaves entre páginas.

## Cron

La página `Admin → Schedulers` lista las tareas y las líneas listas para pegar en el crontab:

```
* * * * *    curl -s "https://tu-dominio/cron.php?task=emails&key=CRON_KEY"
*/5 * * * *  curl -s "https://tu-dominio/cron.php?task=certificates&key=CRON_KEY"
0 * * * *    curl -s "https://tu-dominio/cron.php?task=rankings&key=CRON_KEY"
0 4 * * *    curl -s "https://tu-dominio/cron.php?task=cleanup&key=CRON_KEY"
```

## Producción

Configurar por variables de entorno (`docker-compose.yml`): `APP_URL`, `CRON_KEY`, `SMTP_*`, `MAIL_FROM`.
