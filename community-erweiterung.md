# Community-Erweiterung – SQL-Befehle

Manuell in phpMyAdmin oder per MySQL-CLI ausführen. Ergänzt `db_setup.sql` um die Tabellen für den Community-Bereich (Kontaktdatenbank für externe Organisationen/Initiativen).

```sql
-- Regionen (frei pflegbar, z. B. "Niederbayern", "Schwaben")
CREATE TABLE IF NOT EXISTS `community_regionen` (
  `id`   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80)   NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Spezialisierungs-Tags (frei pflegbar, z. B. "ohne theologischen Hintergrund")
CREATE TABLE IF NOT EXISTS `community_tags` (
  `id`   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80)   NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organisationen/Initiativen für Zeugen-Jehovas-Aussteiger
CREATE TABLE IF NOT EXISTS `community_organisationen` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(160)    NOT NULL COMMENT 'Organisation/Initiative',
  `website`         VARCHAR(255)    NULL,
  `strasse`         VARCHAR(120)    NULL,
  `plz_ort`         VARCHAR(120)    NULL COMMENT 'Freitext, z. B. "79098 Freiburg"',
  `vermittlung`     ENUM('direkt','ueber_uns') NOT NULL DEFAULT 'direkt' COMMENT 'direkt = Kontakt darf weitergegeben werden, ueber_uns = nur Vermittlung durch uns',
  `bundesweit`      TINYINT(1)      NOT NULL DEFAULT 0,
  `notizen`         TEXT            NULL,
  `aktiv`           TINYINT(1)      NOT NULL DEFAULT 1,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Personen (Ansprechpartner), 1:n Verknüpfung zu Organisationen
CREATE TABLE IF NOT EXISTS `community_personen` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `organisation_id` INT UNSIGNED    NOT NULL,
  `name`            VARCHAR(120)    NOT NULL,
  `telefon`         VARCHAR(40)     NULL,
  `handy`           VARCHAR(40)     NULL,
  `email`           VARCHAR(200)    NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_person_org` FOREIGN KEY (`organisation_id`) REFERENCES `community_organisationen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zuordnung Organisation ↔ Region (n:m)
CREATE TABLE IF NOT EXISTS `community_organisation_regionen` (
  `organisation_id` INT UNSIGNED NOT NULL,
  `region_id`       INT UNSIGNED NOT NULL,
  PRIMARY KEY (`organisation_id`, `region_id`),
  KEY `idx_region` (`region_id`),
  CONSTRAINT `fk_or_org`    FOREIGN KEY (`organisation_id`) REFERENCES `community_organisationen` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_or_region` FOREIGN KEY (`region_id`)       REFERENCES `community_regionen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zuordnung Organisation ↔ Tag (n:m)
CREATE TABLE IF NOT EXISTS `community_organisation_tags` (
  `organisation_id` INT UNSIGNED NOT NULL,
  `tag_id`          INT UNSIGNED NOT NULL,
  PRIMARY KEY (`organisation_id`, `tag_id`),
  KEY `idx_tag` (`tag_id`),
  CONSTRAINT `fk_ot_org` FOREIGN KEY (`organisation_id`) REFERENCES `community_organisationen` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ot_tag` FOREIGN KEY (`tag_id`)          REFERENCES `community_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Migration (für bestehende Datenbanken von V1 auf V2)

Führe diese Befehle aus, um die bestehenden Daten zu migrieren, ohne etwas zu verlieren:

```sql
-- 1. Tabelle umbenennen
RENAME TABLE `community_kontakte` TO `community_organisationen`;
RENAME TABLE `community_kontakt_regionen` TO `community_organisation_regionen`;
RENAME TABLE `community_kontakt_tags` TO `community_organisation_tags`;

-- 2. Spalten in den Verknüpfungstabellen umbenennen
ALTER TABLE `community_organisation_regionen` CHANGE `kontakt_id` `organisation_id` INT UNSIGNED NOT NULL;
ALTER TABLE `community_organisation_tags` CHANGE `kontakt_id` `organisation_id` INT UNSIGNED NOT NULL;

-- 3. Neue Personen-Tabelle erstellen
CREATE TABLE IF NOT EXISTS `community_personen` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `organisation_id` INT UNSIGNED    NOT NULL,
  `name`            VARCHAR(120)    NOT NULL,
  `telefon`         VARCHAR(40)     NULL,
  `handy`           VARCHAR(40)     NULL,
  `email`           VARCHAR(200)    NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_person_org` FOREIGN KEY (`organisation_id`) REFERENCES `community_organisationen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bisherige Ansprechpartner in die Personen-Tabelle kopieren (nur wo es Daten gibt)
INSERT INTO `community_personen` (`organisation_id`, `name`, `telefon`, `handy`, `email`)
SELECT `id`, IFNULL(`ansprechpartner`, 'Unbekannt'), `telefon`, `handy`, `email`
FROM `community_organisationen`
WHERE `ansprechpartner` IS NOT NULL OR `telefon` IS NOT NULL OR `email` IS NOT NULL;

-- 5. Alte Kontaktspalten aus der Organisationen-Tabelle entfernen
ALTER TABLE `community_organisationen`
  DROP COLUMN `ansprechpartner`,
  DROP COLUMN `telefon`,
  DROP COLUMN `handy`,
  DROP COLUMN `email`;
```

## Hinweise
- `vermittlung`: `direkt` = Kontaktdaten dürfen an Ratsuchende weitergegeben werden, `ueber_uns` = Vermittlung läuft über die Gruppe.
- `bundesweit`: Flag zusätzlich zu Regionszuordnungen, z. B. für bundesweite Telefonseelsorgen.
- Soft-Delete: `aktiv = 0` statt Löschen (analog `slot_konfiguration`).
- `updated_at` aktualisiert sich automatisch bei jedem `UPDATE` (`ON UPDATE CURRENT_TIMESTAMP`).
