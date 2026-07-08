-- ============================================================
-- BJJ Tournament Manager - Esquema de base de datos
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64) PRIMARY KEY,
  v TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  locale VARCHAR(5) NOT NULL DEFAULT 'es',
  verified_at DATETIME NULL,
  verify_token VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cinturones (adultos e infantiles)
CREATE TABLE IF NOT EXISTS belts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name_es VARCHAR(50) NOT NULL,
  name_en VARCHAR(50) NOT NULL,
  color_hex VARCHAR(7) NOT NULL,
  is_kids TINYINT(1) NOT NULL DEFAULT 0,
  default_duration_sec INT NOT NULL DEFAULT 300,
  sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Divisiones de edad
CREATE TABLE IF NOT EXISTS age_divisions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name_es VARCHAR(60) NOT NULL,
  name_en VARCHAR(60) NOT NULL,
  min_age INT NOT NULL,
  max_age INT NULL,
  is_kids TINYINT(1) NOT NULL DEFAULT 0,
  sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categorias de peso ('M','F','A'=ambos/infantil)
CREATE TABLE IF NOT EXISTS weight_classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gender ENUM('M','F','A') NOT NULL DEFAULT 'A',
  code VARCHAR(30) NOT NULL,
  name_es VARCHAR(60) NOT NULL,
  name_en VARCHAR(60) NOT NULL,
  max_kg DECIMAL(5,2) NULL, -- NULL = sin limite (pesadisimo / absoluto)
  is_absolute TINYINT(1) NOT NULL DEFAULT 0,
  is_kids TINYINT(1) NOT NULL DEFAULT 0,
  sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tournaments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(64) NOT NULL UNIQUE,
  type ENUM('internal','open') NOT NULL DEFAULT 'internal',
  logo VARCHAR(255) NULL,
  event_date DATE NULL,
  max_participants INT NOT NULL DEFAULT 200,
  default_duration_sec INT NOT NULL DEFAULT 300,
  status ENUM('draft','open','running','finished') NOT NULL DEFAULT 'open',
  certs_requested TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tournament_academies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  name VARCHAR(160) NOT NULL,
  logo VARCHAR(255) NULL,
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Profesores / sedes de cada academia
CREATE TABLE IF NOT EXISTS tournament_professors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  academy_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  sede VARCHAR(120) NULL,
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (academy_id) REFERENCES tournament_academies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personal del torneo (arbitros / mesa de control): mismos permisos de operacion que el dueno
CREATE TABLE IF NOT EXISTS tournament_staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_staff (tournament_id, user_id),
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  user_id INT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  gender ENUM('M','F') NOT NULL,
  birthdate DATE NOT NULL,
  weight_kg DECIMAL(5,2) NOT NULL,
  belt_id INT NOT NULL,
  age_division_id INT NOT NULL,
  weight_class_id INT NOT NULL,
  academy_id INT NULL,
  professor_id INT NULL,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  verify_token VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reg (tournament_id, email),
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (belt_id) REFERENCES belts(id),
  FOREIGN KEY (age_division_id) REFERENCES age_divisions(id),
  FOREIGN KEY (weight_class_id) REFERENCES weight_classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Division = combinacion genero + cinturon + edad + peso dentro de un torneo
CREATE TABLE IF NOT EXISTS divisions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  gender ENUM('M','F') NOT NULL,
  belt_id INT NOT NULL,
  age_division_id INT NOT NULL,
  weight_class_id INT NOT NULL,
  duration_sec INT NOT NULL DEFAULT 300,
  status ENUM('pending','bracketed','done') NOT NULL DEFAULT 'pending',
  UNIQUE KEY uq_div (tournament_id, gender, belt_id, age_division_id, weight_class_id),
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (belt_id) REFERENCES belts(id),
  FOREIGN KEY (age_division_id) REFERENCES age_divisions(id),
  FOREIGN KEY (weight_class_id) REFERENCES weight_classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS matches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  division_id INT NOT NULL,
  round INT NOT NULL,               -- 1 = primera ronda; la final es la ronda mas alta
  slot INT NOT NULL,                -- posicion dentro de la ronda (0-based)
  is_bronze TINYINT(1) NOT NULL DEFAULT 0,
  red_reg_id INT NULL,
  blue_reg_id INT NULL,
  winner_reg_id INT NULL,
  method ENUM('points','advantages','submission','decision','dq','wo') NULL,
  red_points INT NOT NULL DEFAULT 0,
  blue_points INT NOT NULL DEFAULT 0,
  red_adv INT NOT NULL DEFAULT 0,
  blue_adv INT NOT NULL DEFAULT 0,
  red_pen INT NOT NULL DEFAULT 0,
  blue_pen INT NOT NULL DEFAULT 0,
  duration_sec INT NOT NULL DEFAULT 300,
  timer_remaining INT NOT NULL DEFAULT 300,
  timer_running TINYINT(1) NOT NULL DEFAULT 0,
  timer_started_at DATETIME NULL,
  elapsed_sec INT NOT NULL DEFAULT 0,
  status ENUM('pending','live','done') NOT NULL DEFAULT 'pending',
  next_match_id INT NULL,
  next_slot ENUM('red','blue') NULL,
  bronze_match_id INT NULL,         -- adonde va el perdedor de semifinal
  bronze_slot ENUM('red','blue') NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS match_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_id INT NOT NULL,
  side ENUM('red','blue') NULL,
  type VARCHAR(30) NOT NULL,        -- takedown, sweep, knee_on_belly, guard_pass, mount, back_control, advantage, penalty, undo, timer...
  value INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(190) NOT NULL,
  to_name VARCHAR(120) NULL,
  subject VARCHAR(200) NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  attachment_path VARCHAR(255) NULL,
  status ENUM('pending','sent','error') NOT NULL DEFAULT 'pending',
  error TEXT NULL,
  attempts INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  registration_id INT NOT NULL,
  type ENUM('gold','silver','bronze','participation') NOT NULL,
  pdf_path VARCHAR(255) NOT NULL,
  emailed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cert (tournament_id, registration_id, type),
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ranking global (recalculado por cron): identidad por email
CREATE TABLE IF NOT EXISTS ranking_points (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  name VARCHAR(120) NOT NULL,
  gender ENUM('M','F') NOT NULL,
  belt_id INT NOT NULL,
  age_division_id INT NOT NULL,
  weight_class_id INT NOT NULL,
  points INT NOT NULL DEFAULT 0,
  golds INT NOT NULL DEFAULT 0,
  silvers INT NOT NULL DEFAULT 0,
  bronzes INT NOT NULL DEFAULT 0,
  wins INT NOT NULL DEFAULT 0,
  submissions INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rank (email, gender, belt_id, age_division_id, weight_class_id),
  FOREIGN KEY (belt_id) REFERENCES belts(id),
  FOREIGN KEY (age_division_id) REFERENCES age_divisions(id),
  FOREIGN KEY (weight_class_id) REFERENCES weight_classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cron_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task VARCHAR(40) NOT NULL,
  detail VARCHAR(255) NULL,
  ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Datos de referencia
-- ============================================================

-- Cinturones adultos (duracion IBJJF por defecto) e infantiles
INSERT INTO belts (code, name_es, name_en, color_hex, is_kids, default_duration_sec, sort) VALUES
('white',  'Blanco',  'White',  '#f5f5f5', 0, 300, 10),
('blue',   'Azul',    'Blue',   '#1e5bb8', 0, 360, 20),
('purple', 'Violeta', 'Purple', '#7b2fbe', 0, 420, 30),
('brown',  'Marrón',  'Brown',  '#6b4423', 0, 480, 40),
('black',  'Negro',   'Black',  '#1a1a1a', 0, 600, 50),
('k_white', 'Blanco (inf.)',  'White (kids)',  '#f5f5f5', 1, 240, 110),
('k_grey',  'Gris',           'Grey',          '#8a8a8a', 1, 240, 120),
('k_yellow','Amarillo',       'Yellow',        '#f2c500', 1, 240, 130),
('k_orange','Naranja',        'Orange',        '#f28c00', 1, 240, 140),
('k_green', 'Verde',          'Green',         '#2e8b57', 1, 240, 150);

-- Divisiones de edad
INSERT INTO age_divisions (code, name_es, name_en, min_age, max_age, is_kids, sort) VALUES
('inf_a',   'Infantil A (4-6)',    'Kids A (4-6)',      4, 6, 1, 10),
('inf_b',   'Infantil B (7-9)',    'Kids B (7-9)',      7, 9, 1, 20),
('inf_c',   'Infantil C (10-12)',  'Kids C (10-12)',   10, 12, 1, 30),
('inf_d',   'Infanto-Juvenil (13-15)', 'Teens (13-15)',13, 15, 1, 40),
('juvenil', 'Juvenil (16-17)',     'Juvenile (16-17)', 16, 17, 0, 50),
('adulto',  'Adulto (18-29)',      'Adult (18-29)',    18, 29, 0, 60),
('master1', 'Master 1 (30-35)',    'Master 1 (30-35)', 30, 35, 0, 70),
('master2', 'Master 2 (36-40)',    'Master 2 (36-40)', 36, 40, 0, 80),
('master3', 'Master 3 (41-45)',    'Master 3 (41-45)', 41, 45, 0, 90),
('master4', 'Master 4 (46-50)',    'Master 4 (46-50)', 46, 50, 0, 100),
('master5', 'Master 5 (51-55)',    'Master 5 (51-55)', 51, 55, 0, 110),
('master6', 'Master 6 (56+)',      'Master 6 (56+)',   56, NULL, 0, 120);

-- Pesos IBJJF con kimono - Masculino
INSERT INTO weight_classes (gender, code, name_es, name_en, max_kg, is_absolute, is_kids, sort) VALUES
('M','m_galo',       'Gallo (-57.5)',        'Rooster (-57.5)',       57.50, 0, 0, 10),
('M','m_pluma',      'Pluma (-64)',          'Light Feather (-64)',   64.00, 0, 0, 20),
('M','m_pena',       'Pena (-70)',           'Feather (-70)',         70.00, 0, 0, 30),
('M','m_leve',       'Leve (-76)',           'Light (-76)',           76.00, 0, 0, 40),
('M','m_medio',      'Medio (-82.3)',        'Middle (-82.3)',        82.30, 0, 0, 50),
('M','m_mediopesado','Medio Pesado (-88.3)', 'Medium Heavy (-88.3)',  88.30, 0, 0, 60),
('M','m_pesado',     'Pesado (-94.3)',       'Heavy (-94.3)',         94.30, 0, 0, 70),
('M','m_superpesado','Súper Pesado (-100.5)','Super Heavy (-100.5)', 100.50, 0, 0, 80),
('M','m_pesadisimo', 'Pesadísimo (+100.5)',  'Ultra Heavy (+100.5)',   NULL, 0, 0, 90),
('M','m_absoluto',   'Absoluto',             'Open Class',             NULL, 1, 0, 100),
-- Pesos IBJJF con kimono - Femenino
('F','f_galo',       'Gallo (-48.5)',        'Rooster (-48.5)',       48.50, 0, 0, 10),
('F','f_pluma',      'Pluma (-53.5)',        'Light Feather (-53.5)', 53.50, 0, 0, 20),
('F','f_pena',       'Pena (-58.5)',         'Feather (-58.5)',       58.50, 0, 0, 30),
('F','f_leve',       'Leve (-64)',           'Light (-64)',           64.00, 0, 0, 40),
('F','f_medio',      'Medio (-69)',          'Middle (-69)',          69.00, 0, 0, 50),
('F','f_mediopesado','Medio Pesado (-74)',   'Medium Heavy (-74)',    74.00, 0, 0, 60),
('F','f_pesado',     'Pesado (-79.3)',       'Heavy (-79.3)',         79.30, 0, 0, 70),
('F','f_superpesado','Súper Pesado (+79.3)', 'Super Heavy (+79.3)',    NULL, 0, 0, 80),
('F','f_absoluto',   'Absoluto',             'Open Class',             NULL, 1, 0, 100),
-- Infantiles (ambos generos)
('A','k_24', '-24 kg', '-24 kg', 24.00, 0, 1, 10),
('A','k_27', '-27 kg', '-27 kg', 27.00, 0, 1, 20),
('A','k_30', '-30 kg', '-30 kg', 30.00, 0, 1, 30),
('A','k_33', '-33 kg', '-33 kg', 33.00, 0, 1, 40),
('A','k_36', '-36 kg', '-36 kg', 36.00, 0, 1, 50),
('A','k_40', '-40 kg', '-40 kg', 40.00, 0, 1, 60),
('A','k_44', '-44 kg', '-44 kg', 44.00, 0, 1, 70),
('A','k_48', '-48 kg', '-48 kg', 48.00, 0, 1, 80),
('A','k_53', '-53 kg', '-53 kg', 53.00, 0, 1, 90),
('A','k_53p','+53 kg', '+53 kg',  NULL, 0, 1, 100);

-- Configuracion por defecto
INSERT INTO settings (k, v) VALUES
('scoring', '{"takedown":2,"sweep":2,"knee_on_belly":2,"guard_pass":3,"mount":4,"back_control":4}'),
('ranking', '{"gold":9,"silver":3,"bronze":1,"win":2,"submission_bonus":1}'),
('tournament_weekly_limit', '1'),
('site_name', 'BJJ Tournament Manager');
