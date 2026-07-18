<?php
/**
 * Migrador idempotente: lleva una base existente (creada con un schema viejo)
 * al schema actual, agregando columnas/índices/tablas que falten. MySQL no
 * soporta "ADD COLUMN IF NOT EXISTS", así que verificamos contra
 * information_schema antes de cada cambio. Seguro de correr siempre: si ya
 * está todo, no hace nada. Lo llama el entrypoint en cada arranque.
 *
 *   docker compose exec app php scripts/migrate.php
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

function col_exists(string $table, string $col): bool {
    return (int)scalar('SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $col]) > 0;
}
function index_cols(string $table, string $index): array {
    // alias explícito: information_schema devuelve la columna como COLUMN_NAME en algunos
    // entornos y column_name en otros; con AS c la key es siempre 'c'.
    return array_column(rows('SELECT column_name AS c FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? ORDER BY seq_in_index', [$table, $index]), 'c');
}
function ensure_col(string $table, string $col, string $ddl): void {
    if (!col_exists($table, $col)) { db()->exec("ALTER TABLE `$table` ADD COLUMN $ddl"); echo "  + $table.$col\n"; }
}

$done = [];

// --- tournaments: config gi/nogi por torneo ---
ensure_col('tournaments', 'discipline', "discipline ENUM('gi','nogi') NOT NULL DEFAULT 'gi' AFTER type");
ensure_col('tournaments', 'division_order', 'division_order TEXT NULL');
ensure_col('tournaments', 'belt_durations', 'belt_durations TEXT NULL');
ensure_col('tournaments', 'age_thresholds', 'age_thresholds TEXT NULL');
ensure_col('tournaments', 'age_order', 'age_order TEXT NULL');
ensure_col('tournaments', 'weight_order', 'weight_order TEXT NULL');
ensure_col('tournaments', 'nogi_tiers', 'nogi_tiers TEXT NULL');
ensure_col('tournaments', 'nogi_division_order', 'nogi_division_order TEXT NULL');
ensure_col('tournaments', 'nogi_tier_durations', 'nogi_tier_durations TEXT NULL');
ensure_col('tournaments', 'is_demo', 'is_demo TINYINT(1) NOT NULL DEFAULT 0');
ensure_col('tournaments', 'reg_close_date', 'reg_close_date DATE NULL');
ensure_col('tournaments', 'regs_closed_at', 'regs_closed_at DATETIME NULL');
// marcar como demo los torneos de muestra ya sembrados (por slug) — idempotente
q("UPDATE tournaments SET is_demo = 1 WHERE slug IN ('demo-gi-2026','demo-nogi-2026') AND is_demo = 0");

// --- registrations: foto + categoria/absoluto ---
ensure_col('registrations', 'photo', 'photo VARCHAR(255) NULL');
ensure_col('registrations', 'competes_in', "competes_in ENUM('category','absolute','both') NOT NULL DEFAULT 'category'");
// asegurar que el enum incluya 'both' aunque la columna ya existiera de una version intermedia
db()->exec("ALTER TABLE registrations MODIFY COLUMN competes_in ENUM('category','absolute','both') NOT NULL DEFAULT 'category'");

// --- divisions: tier/kind/name + nullables ---
ensure_col('divisions', 'tier', 'tier VARCHAR(20) NULL');
ensure_col('divisions', 'kind', "kind ENUM('standard','absolute','special') NOT NULL DEFAULT 'standard'");
ensure_col('divisions', 'name', 'name VARCHAR(160) NULL');
// belt/age/weight pasan a NULLABLE (nogi-tier / absoluto / especial)
foreach (['belt_id', 'age_division_id', 'weight_class_id'] as $c) {
    $nullable = scalar('SELECT is_nullable FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', ['divisions', $c]);
    if ($nullable === 'NO') { db()->exec("ALTER TABLE divisions MODIFY COLUMN `$c` INT NULL"); echo "  ~ divisions.$c -> NULL\n"; }
}
// unique key para categorias especiales
if (!index_cols('divisions', 'uq_special')) {
    // limpiar posibles duplicados especiales antes de crear el indice
    db()->exec("ALTER TABLE divisions ADD UNIQUE KEY uq_special (tournament_id, kind, gender, name)");
    echo "  + divisions.uq_special\n";
}

// --- division_members (tabla nueva) ---
db()->exec("CREATE TABLE IF NOT EXISTS division_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NOT NULL,
    registration_id INT NOT NULL,
    UNIQUE KEY uq_member (division_id, registration_id),
    FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// --- ranking_points: foto + disciplina + tier, y unique key nuevo ---
ensure_col('ranking_points', 'photo', 'photo VARCHAR(255) NULL');
ensure_col('ranking_points', 'discipline', "discipline ENUM('gi','nogi') NOT NULL DEFAULT 'gi'");
ensure_col('ranking_points', 'tier', "tier VARCHAR(20) NOT NULL DEFAULT ''");
// el unique key debe incluir discipline y tier (7 columnas); si tiene la forma vieja, recrear
$uq = index_cols('ranking_points', 'uq_rank');
if (count($uq) !== 7) {
    if ($uq) db()->exec('ALTER TABLE ranking_points DROP INDEX uq_rank');
    db()->exec('ALTER TABLE ranking_points ADD UNIQUE KEY uq_rank (email, gender, belt_id, age_division_id, weight_class_id, discipline, tier)');
    echo "  ~ ranking_points.uq_rank (con discipline+tier)\n";
}

// --- name_pt en datos de referencia (+ sembrar valores) ---
foreach (['belts' => 50, 'age_divisions' => 60, 'weight_classes' => 60] as $tbl => $len) {
    ensure_col($tbl, 'name_pt', "name_pt VARCHAR($len) NOT NULL DEFAULT ''");
}
// sembrar portugues donde este vacio (idempotente)
$pt = [
    'belts' => ['white' => 'Branca', 'blue' => 'Azul', 'purple' => 'Roxa', 'brown' => 'Marrom', 'black' => 'Preta',
        'k_white' => 'Branca (inf.)', 'k_grey' => 'Cinza', 'k_yellow' => 'Amarela', 'k_orange' => 'Laranja', 'k_green' => 'Verde'],
    'age_divisions' => ['inf_a' => 'Infantil A (4-6)', 'inf_b' => 'Infantil B (7-9)', 'inf_c' => 'Infantil C (10-12)',
        'inf_d' => 'Infanto-Juvenil (13-15)', 'juvenil' => 'Juvenil (16-17)', 'adulto' => 'Adulto (18-29)',
        'master1' => 'Master 1 (30-35)', 'master2' => 'Master 2 (36-40)', 'master3' => 'Master 3 (41-45)',
        'master4' => 'Master 4 (46-50)', 'master5' => 'Master 5 (51-55)', 'master6' => 'Master 6 (56+)'],
    'weight_classes' => ['m_galo' => 'Galo (-57.5)', 'm_pluma' => 'Pluma (-64)', 'm_pena' => 'Pena (-70)', 'm_leve' => 'Leve (-76)',
        'm_medio' => 'Médio (-82.3)', 'm_mediopesado' => 'Meio-Pesado (-88.3)', 'm_pesado' => 'Pesado (-94.3)',
        'm_superpesado' => 'Super Pesado (-100.5)', 'm_pesadisimo' => 'Pesadíssimo (+100.5)', 'm_absoluto' => 'Absoluto',
        'f_galo' => 'Galo (-48.5)', 'f_pluma' => 'Pluma (-53.5)', 'f_pena' => 'Pena (-58.5)', 'f_leve' => 'Leve (-64)',
        'f_medio' => 'Médio (-69)', 'f_mediopesado' => 'Meio-Pesado (-74)', 'f_pesado' => 'Pesado (-79.3)',
        'f_superpesado' => 'Super Pesado (+79.3)', 'f_absoluto' => 'Absoluto'],
];
foreach ($pt as $tbl => $map) {
    foreach ($map as $code => $namePt) {
        q("UPDATE $tbl SET name_pt = ? WHERE code = ? AND (name_pt = '' OR name_pt IS NULL)", [$namePt, $code]);
    }
}
// pesos infantiles: mismo texto que es (-24 kg, etc.)
q("UPDATE weight_classes SET name_pt = name_es WHERE is_kids = 1 AND (name_pt = '' OR name_pt IS NULL)");

echo "Migración completa.\n";
