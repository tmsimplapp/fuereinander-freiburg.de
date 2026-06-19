-- Füreinander Freiburg – Rückruf-Buchungssystem
-- Ausführen in phpMyAdmin oder per MySQL-CLI

-- NULL-Trick: storniert=0 = aktive Buchung, storniert=NULL = storniert
-- MySQL behandelt NULL-Werte als nie-duplicate → UNIQUE KEY greift nur für aktive Buchungen (storniert=0)
-- Nach Storno: UPDATE storniert = NULL → Slot sofort wieder buchbar

CREATE TABLE IF NOT EXISTS `rueckruf_buchungen` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `slot_datum`    DATE            NOT NULL,
  `slot_uhrzeit`  TIME            NOT NULL,
  `name`          VARCHAR(120)    NOT NULL,
  `telefon`       VARCHAR(40)     NOT NULL,
  `email`         VARCHAR(200)    NOT NULL,
  `cancel_token`  CHAR(64)        NOT NULL,
  `storniert`     TINYINT(1)      NULL     DEFAULT 0,
  `erstellt_am`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slot` (`slot_datum`, `slot_uhrzeit`, `storniert`),
  UNIQUE KEY `uq_token` (`cancel_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Slot-Konfiguration: Einzeltermine – pflegbar ohne FTP
-- Neuen Termin: INSERT INTO slot_konfiguration (termin_datum, uhrzeiten) VALUES ('2026-09-03', '["18:00","19:00"]');
-- Termin deaktivieren: UPDATE slot_konfiguration SET aktiv = 0 WHERE termin_datum = '2026-08-05';
CREATE TABLE IF NOT EXISTS `slot_konfiguration` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `termin_datum`    DATE          NOT NULL COMMENT 'Konkretes Datum des Termins',
  `uhrzeiten`       JSON          NOT NULL COMMENT 'Array: ["18:00","19:00"]',
  `slot_laenge_min` SMALLINT      NOT NULL DEFAULT 60,
  `aktiv`           TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_termin` (`termin_datum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Juli 2026: alle Mittwoche
INSERT IGNORE INTO `slot_konfiguration` (termin_datum, uhrzeiten) VALUES
  ('2026-07-01', '["18:00","19:00"]'),
  ('2026-07-08', '["18:00","19:00"]'),
  ('2026-07-15', '["18:00","19:00"]'),
  ('2026-07-22', '["18:00","19:00"]'),
  ('2026-07-29', '["18:00","19:00"]');

-- August 2026: nur erster Mittwoch (05.08.)
INSERT IGNORE INTO `slot_konfiguration` (termin_datum, uhrzeiten) VALUES
  ('2026-08-05', '["18:00","19:00"]');

-- Rate-Limiting: max. 3 Buchungsversuche pro IP pro 10 Minuten
CREATE TABLE IF NOT EXISTS `buchung_rate_limit` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_hash`    CHAR(64)     NOT NULL,
  `erstellt_am` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_zeit` (`ip_hash`, `erstellt_am`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
