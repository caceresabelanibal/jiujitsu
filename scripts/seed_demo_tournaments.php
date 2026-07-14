<?php
/**
 * Crea dos torneos demo (uno Gi, uno NoGi) asignados al admin, con 15
 * competidores ficticios cada uno, la mitad de las luchas jugadas y los
 * certificados generados. Idempotente por slug: si ya existen, no hace nada.
 * Toda la logica vive en src/demo.php (la comparten el reset por boton y el cron).
 *
 *   docker compose exec app php scripts/seed_demo_tournaments.php
 *
 * El entrypoint lo corre solo si la variable de entorno SEED_DEMO=1.
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

$adminEmail = strtolower(trim(getenv('ADMIN_EMAIL') ?: 'admin@taninzu.com'));
$owner = row('SELECT id FROM users WHERE email = ? AND role = "admin"', [$adminEmail])
      ?: row('SELECT id FROM users WHERE role = "admin" ORDER BY id LIMIT 1');
if (!$owner) { echo "No hay usuario admin todavía; corré seed_admin.php primero.\n"; exit; }

foreach (seed_demo_tournaments((int)$owner['id']) as $line) echo $line . "\n";
echo "Rankings recalculados.\n";
