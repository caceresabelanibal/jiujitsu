<?php
/**
 * Crea (o actualiza) el usuario administrador inicial para producción, sin
 * ningún dato demo. Idempotente: se puede correr las veces que haga falta.
 *
 *   docker compose exec app php scripts/seed_admin.php
 *
 * Toma email/contraseña de las variables de entorno ADMIN_EMAIL y
 * ADMIN_PASSWORD (definidas en docker-compose.prod.yml); si no están, usa
 * los valores por defecto de abajo. Cambiá la contraseña después del primer
 * login desde /admin/users.
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

$email = strtolower(trim(getenv('ADMIN_EMAIL') ?: 'admin@taninzu.com'));
$pass  = getenv('ADMIN_PASSWORD') ?: 'Taninzu2026!';
$name  = getenv('ADMIN_NAME') ?: 'Administrador';

$u = row('SELECT id FROM users WHERE email = ?', [$email]);
if ($u) {
    // Ya existe: solo aseguramos rol admin + email verificado (no piso la contraseña)
    q('UPDATE users SET role = "admin", verified_at = COALESCE(verified_at, NOW()) WHERE id = ?', [$u['id']]);
    echo "Admin ya existía ({$email}) — rol/verificación asegurados. Contraseña sin cambios.\n";
} else {
    q('INSERT INTO users (name, email, pass_hash, role, verified_at) VALUES (?,?,?,?,NOW())',
        [$name, $email, password_hash($pass, PASSWORD_DEFAULT), 'admin']);
    echo "Admin creado.\n";
    echo "  Usuario:     {$email}\n";
    echo "  Contraseña:  {$pass}\n";
    echo "  Cambiala desde /admin/users después del primer login.\n";
}
