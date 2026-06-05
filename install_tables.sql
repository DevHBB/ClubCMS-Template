-- ═══════════════════════════════════════════════════════════════
-- ClubCMS — Script SQL à exécuter dans phpMyAdmin
-- Crée toutes les tables manquantes
-- ═══════════════════════════════════════════════════════════════

-- Tables planning critères
CREATE TABLE IF NOT EXISTS cc_planning_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    color VARCHAR(7) NOT NULL DEFAULT '#6366f1',
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0
);

INSERT IGNORE INTO cc_planning_types (slug,label,color,is_system,sort_order) VALUES
    ('open','Libre','#22c55e',1,1),
    ('training','Entraînement','#3b82f6',1,2),
    ('event','Événement','#f59e0b',1,3),
    ('maintenance','Fermé','#6b7280',1,4),
    ('competition','Compétition','#ef4444',1,5);

CREATE TABLE IF NOT EXISTS cc_planning_criteria (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    field_type  VARCHAR(20)  NOT NULL DEFAULT 'text',
    options     TEXT         DEFAULT NULL,
    use_color   TINYINT(1)   NOT NULL DEFAULT 0,
    color       VARCHAR(7)   NOT NULL DEFAULT '#6366f1',
    range_min   INT          DEFAULT NULL,
    range_max   INT          DEFAULT NULL,
    range_unit  VARCHAR(30)  NOT NULL DEFAULT '',
    required    TINYINT(1)   NOT NULL DEFAULT 1,
    allow_other TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order  INT          NOT NULL DEFAULT 0,
    active      TINYINT(1)   NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS cc_planning_criteria_values (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    criteria_id INT NOT NULL,
    value       VARCHAR(255) NOT NULL DEFAULT '',
    value2      VARCHAR(255) NOT NULL DEFAULT '',
    UNIQUE KEY uq_user_crit (user_id, criteria_id)
);

-- Colonnes manquantes sur tables existantes
ALTER TABLE cc_planning_slots ADD COLUMN IF NOT EXISTS criteria_ids TEXT DEFAULT NULL;
ALTER TABLE cc_planning_slots ADD COLUMN IF NOT EXISTS criteria_required TEXT DEFAULT NULL;
ALTER TABLE cc_planning_bookings ADD COLUMN IF NOT EXISTS criteria_data TEXT DEFAULT NULL;
ALTER TABLE cc_planning_bookings ADD COLUMN IF NOT EXISTS is_waitlist TINYINT(1) NOT NULL DEFAULT 0;

-- Tables bénévoles
CREATE TABLE IF NOT EXISTS cc_benv_events (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(200) NOT NULL,
    description    TEXT,
    location       VARCHAR(200),
    date_start     DATETIME NOT NULL,
    date_end       DATETIME,
    max_volunteers INT DEFAULT 0,
    created_by     INT NOT NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    recurring      VARCHAR(20) DEFAULT 'none',
    color          VARCHAR(7) DEFAULT '#6366f1'
);

CREATE TABLE IF NOT EXISTS cc_benv_participations (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    event_id   INT NOT NULL,
    user_id    INT NOT NULL,
    status     VARCHAR(20) DEFAULT 'confirmed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evt_user (event_id, user_id)
);

CREATE TABLE IF NOT EXISTS cc_benv_tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    status      VARCHAR(20) DEFAULT 'todo',
    priority    VARCHAR(10) DEFAULT 'normal',
    assigned_to INT DEFAULT NULL,
    due_date    DATE DEFAULT NULL,
    created_by  INT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    recurring   VARCHAR(20) DEFAULT 'none',
    color       VARCHAR(7) DEFAULT '#6366f1'
);

CREATE TABLE IF NOT EXISTS cc_benv_chat (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    TEXT NOT NULL,
    channel    VARCHAR(50) DEFAULT 'general',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cc_benv_alerts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    message    TEXT,
    level      VARCHAR(10) DEFAULT 'info',
    active     TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS cc_benv_alerts_seen (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    user_id  INT NOT NULL,
    UNIQUE KEY uq_alert_user (alert_id, user_id)
);

CREATE TABLE IF NOT EXISTS cc_benv_folders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(200) NOT NULL,
    parent_id  INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cc_benv_docs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    folder_id  INT DEFAULT NULL,
    title      VARCHAR(200) NOT NULL,
    type       VARCHAR(10) DEFAULT 'note',
    content    TEXT,
    filename   VARCHAR(255) DEFAULT NULL,
    filesize   INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cc_benv_profiles (
    user_id          INT PRIMARY KEY,
    skills           TEXT,
    notes            TEXT,
    blacklisted      TINYINT(1) DEFAULT 0,
    blacklist_reason TEXT,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cc_benv_coach_access (
    coach_id      INT PRIMARY KEY,
    can_access    TINYINT(1) DEFAULT 0,
    see_blacklist TINYINT(1) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cc_benv_reminders_sent (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id  INT NOT NULL,
    sent_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rem (event_id, user_id)
);

-- Colonne rôle bénévole : rien à faire, c'est juste une valeur VARCHAR
-- Vérifier que la colonne role accepte la valeur 'benevole'
ALTER TABLE cc_users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'member';

