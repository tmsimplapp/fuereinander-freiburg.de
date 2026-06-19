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

-- Slot-Konfiguration: Monate, Wochentag, Uhrzeiten – pflegbar ohne FTP
-- Neuen Monat hinzufügen: INSERT INTO slot_konfiguration (monat, wochentag, uhrzeiten, slot_laenge_min) VALUES ('2026-08', 3, '["18:00","19:00"]', 60);
-- Monat deaktivieren: UPDATE slot_konfiguration SET aktiv = 0 WHERE monat = '2026-07';
CREATE TABLE IF NOT EXISTS `slot_konfiguration` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `monat`           CHAR(7)       NOT NULL COMMENT 'Format: YYYY-MM',
  `wochentag`       TINYINT       NOT NULL COMMENT '1=Mo … 7=So',
  `uhrzeiten`       JSON          NOT NULL COMMENT 'Array: ["18:00","19:00"]',
  `slot_laenge_min` SMALLINT      NOT NULL DEFAULT 60,
  `aktiv`           TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_monat` (`monat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initialer Eintrag: Juli 2026, Mittwoch, 18+19 Uhr
INSERT IGNORE INTO `slot_konfiguration` (monat, wochentag, uhrzeiten, slot_laenge_min)
VALUES ('2026-07', 3, '["18:00","19:00"]', 60);

-- Rate-Limiting: max. 3 Buchungsversuche pro IP pro 10 Minuten
CREATE TABLE IF NOT EXISTS `buchung_rate_limit` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_hash`    CHAR(64)     NOT NULL,
  `erstellt_am` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_zeit` (`ip_hash`, `erstellt_am`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
