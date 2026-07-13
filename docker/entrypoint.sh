#!/bin/sh
# Entrypoint del contenedor app: antes de arrancar Apache, asegura el esquema
# de la base y crea el usuario admin inicial (idempotente). Así, en cualquier
# instalación, con `docker compose up` la app queda lista para hacer login sin
# pasos manuales.
set -e

# Esperar a que la base responda y a que el esquema exista (la tabla users).
# Reintenta ~60s: cubre el primer arranque, cuando MySQL todavía está
# importando db/schema.sql desde /docker-entrypoint-initdb.d.
echo "[entrypoint] esperando la base de datos..."
i=0
until php -r '
    require "/var/www/html/src/bootstrap.php";
    // lanza excepción si no conecta o si la tabla users todavía no existe
    scalar("SELECT 1 FROM users LIMIT 1");
' >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
        echo "[entrypoint] la base no estuvo lista a tiempo; arranco igual y que la app reintente."
        break
    fi
    sleep 1
done

# Migrar el esquema: lleva una base creada con un schema viejo al actual
# (agrega columnas/índices/tablas que falten). Idempotente.
echo "[entrypoint] migrando esquema si hace falta..."
php /var/www/html/scripts/migrate.php || echo "[entrypoint] migrate.php falló (revisar logs)."

# Crear/asegurar el admin inicial (no pisa la contraseña si ya existe).
echo "[entrypoint] asegurando usuario admin inicial..."
php /var/www/html/scripts/seed_admin.php || echo "[entrypoint] seed_admin no pudo correr (se puede correr a mano luego)."

# Torneos demo (solo si SEED_DEMO=1). Idempotente: no duplica si ya existen.
if [ "$SEED_DEMO" = "1" ]; then
    echo "[entrypoint] sembrando torneos demo..."
    php /var/www/html/scripts/seed_demo_tournaments.php || echo "[entrypoint] seed_demo_tournaments falló (revisar logs)."
fi

echo "[entrypoint] iniciando Apache."
exec "$@"
