-- Füreinander Freiburg – Admin-Tabellen
-- Alternativ zu setup_admin.php direkt in phpMyAdmin ausführen.
-- Danach Passwort-Hash per setup_admin.php oder manuell eintragen.

CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(64)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_rate_limit` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_hash`     CHAR(64)     NOT NULL,
  `erstellt_am` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_zeit` (`ip_hash`, `erstellt_am`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin-Benutzer: Hash mit setup_admin.php erzeugen lassen oder per PHP-CLI:
-- php -r "echo password_hash('DEIN_PASSWORT', PASSWORD_BCRYPT, ['cost'=>12]);"
-- Dann eintragen:
-- INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$12$...');
