-- Migration: plz_ort aufteilen in plz + ort, Organisations-Telefon ergänzen
-- Tabelle: community_organisationen
-- Vor Ausführung Backup anlegen.

-- 1. Neue Spalten
ALTER TABLE `community_organisationen`
  ADD COLUMN `plz`     VARCHAR(10)  NULL COMMENT 'Postleitzahl' AFTER `strasse`,
  ADD COLUMN `ort`     VARCHAR(120) NULL COMMENT 'Ort'          AFTER `plz`,
  ADD COLUMN `telefon` VARCHAR(40)  NULL COMMENT 'Zentrale Telefonnummer der Organisation' AFTER `website`;

-- 2. Bestehende Freitext-Werte aufteilen
--    Annahme: Format "PLZ Ort" (z. B. "79098 Freiburg"). Erste Zahlengruppe = PLZ, Rest = Ort.
UPDATE `community_organisationen`
SET
  `plz` = NULLIF(TRIM(SUBSTRING_INDEX(`plz_ort`, ' ', 1)), ''),
  `ort` = NULLIF(TRIM(SUBSTRING(`plz_ort`, LENGTH(SUBSTRING_INDEX(`plz_ort`, ' ', 1)) + 2)), '')
WHERE `plz_ort` IS NOT NULL AND `plz_ort` <> '';

-- 2b. Sonderfall: kein Leerzeichen -> alles in ort, plz leeren
UPDATE `community_organisationen`
SET `ort` = `plz_ort`, `plz` = NULL
WHERE `plz_ort` IS NOT NULL AND `plz_ort` <> '' AND LOCATE(' ', `plz_ort`) = 0;

-- 3. Alte Spalte entfernen (erst nach Prüfung der Daten ausführen)
ALTER TABLE `community_organisationen` DROP COLUMN `plz_ort`;
