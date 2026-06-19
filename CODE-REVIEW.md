# Code-Review – Füreinander Freiburg
**Datum:** 2026-06-19  
**Scope:** Gesamtes Projekt inkl. neu hinzugefügtem Rückruf-Buchungssystem  
**Effort:** High (8 Finder-Angles × bis zu 6 Kandidaten, 1-Vote-Verify)

---

## Zusammenfassung

8 Kandidaten untersucht, 5 CONFIRMED / PLAUSIBLE nach Verifikation. Schwerpunkt liegt auf dem neu erstellten Buchungssystem (`buchung.php`, `cancel.php`, `db_setup.sql`, `rueckruf.js`). Die Landingpage selbst (HTML/CSS) hat nur einen Conventions-Hinweis.

---

## Findings (priorisiert nach Schwere)

### 🔴 KRITISCH

#### 1. Storno per GET – E-Mail-Prefetcher storniert Termin automatisch
**Datei:** `cancel.php` · **Status:** CONFIRMED

Der Storno-Link in der Bestätigungs-E-Mail führt unmittelbar beim Aufrufen per GET zum `UPDATE storniert = 1`. E-Mail-Clients wie Outlook (Safe Links) und Gmail (Link-Preview) fetchen URLs automatisch im Hintergrund – ohne Nutzerinteraktion. Folge: Termin wird storniert, obwohl der Nutzer nur die E-Mail geöffnet hat.

**Fix:** Storno-Link führt zu einer Bestätigungsseite (HTML mit Button), die per POST bestätigt wird. Der eigentliche UPDATE-Query erst im POST-Handler.

---

#### 2. UNIQUE KEY auf `(slot_datum, slot_uhrzeit, storniert)` – fehlerhafte Datenbank-Logik
**Datei:** `db_setup.sql` · **Status:** CONFIRMED

`storniert` ist Teil des Unique-Keys. Das bedeutet:
- `(datum, uhrzeit, 0)` = eine aktive Buchung → korrekt
- `(datum, uhrzeit, 1)` = eine stornierte Buchung → korrekt
- Zweite stornierte Buchung desselben Slots → **Constraint-Fehler**, da `(datum, uhrzeit, 1)` bereits belegt

In MySQL/MariaDB gibt es keinen Partial Index (`WHERE storniert = 0`). Korrekte Lösung: `storniert` aus dem Unique Key rausnehmen, stattdessen stornierte Zeilen explizit vom INSERT-Check ausschließen (bereits gelöst via `SELECT`-Check in `slots_laden()`).

**Fix:**
```sql
-- statt:
UNIQUE KEY uq_slot (slot_datum, slot_uhrzeit, storniert)
-- so:
UNIQUE KEY uq_slot_aktiv (slot_datum, slot_uhrzeit)
-- und stornierte Buchungen in eine Archivtabelle verschieben oder über NULL-Trick:
-- storniert TINYINT NULL DEFAULT 0, UNIQUE KEY uq_slot (slot_datum, slot_uhrzeit, storniert)
-- NULL-Werte sind nie unique in MySQL → storniert=NULL statt 1 setzen
```

---

### 🟠 MITTEL

#### 3. Race Condition: Check-then-Insert nicht atomar
**Datei:** `buchung.php` · **Status:** CONFIRMED (409 korrekt, aber Fenster existiert)

`slots_laden()` prüft via SELECT, danach folgt INSERT. Zwischen beiden Operationen kann ein zweiter Request denselben Slot buchen. Der UNIQUE-Key fängt die Kollision ab und gibt korrekterweise 409 zurück – aber nur wenn der Key korrekt gesetzt ist (siehe Finding 2). Solange Finding 2 besteht, kann der Fallback-409 nicht greifen.

**Fix (nach Finding 2-Fix):** Zusätzlich Transaktion mit `SELECT FOR UPDATE` verwenden:
```php
db()->beginTransaction();
// SELECT ... FOR UPDATE
// wenn belegt: rollback + 409
// INSERT
// COMMIT
```

---

#### 4. Kein Rate-Limiting – Slot-Erschöpfung und Mail-Bombing möglich
**Datei:** `buchung.php` / `rueckruf.js` · **Status:** PLAUSIBLE

Ohne IP-basiertes Rate-Limiting kann ein Skript:
- alle verfügbaren Slots mit gefakten Daten blockieren
- beliebige E-Mail-Adressen mit Bestätigungsmails fluten

**Fix:** Serverseitiges Rate-Limit, z. B. 3 Buchungen pro IP pro Stunde via Session oder `.htaccess`-Limit.

---

#### 5. `db()` doppelt definiert in `buchung.php` und `cancel.php`
**Datei:** `buchung.php`, `cancel.php` · **Status:** PLAUSIBLE

Beide Dateien definieren identische Funktion `db()`. Bei separatem HTTP-Aufruf kein Problem. Jeder künftige gemeinsame Include (z. B. Admin-Seite) wirft `Fatal Error: Cannot redeclare db()`.

**Fix:** Gemeinsame Datei `buchung-helpers.php` mit `db()`, `sende_mail()`, `email_style()`, `mail_wrapper()` – wird von beiden Dateien per `require` eingebunden.

---

### 🟡 HINWEISE / WARTUNG

#### 6. E-Mail-Styles in `cancel.php` dupliziert (nicht aus `buchung.php` geteilt)
**Datei:** `cancel.php`

`email_style()` ist in `cancel.php` separat inline definiert, mit leicht abweichendem CSS. Farbänderungen müssen an beiden Stellen synchron gepflegt werden. Hängt direkt mit Finding 5 zusammen.

---

#### 7. `SLOT_MONATE` als PHP-Konstante ungeeignet für Dauerbetrieb
**Datei:** `buchung-config.php`

Monatswechsel erfordert manuellen Datei-Edit + FTP-Upload. Kein Audit-Trail. Für Juli 2026 ausreichend, aber sobald weitere Monate hinzukommen wird das fehleranfällig.

**Empfehlung:** Mittelfristig Slots in der DB verwalten oder `buchung-config.php` als einzige Wartungsdatei klar dokumentieren.

---

#### 8. Inline-Farben als Hex statt OKLCH (CLAUDE.md-Verstoß)
**Datei:** `index.html` (Rückruf-Block)

Alle `style`-Attribute im neuen Rückruf-Block verwenden Hex-Werte (`#FEFAE0`, `#E2C2A2`, `#a9e2cc`). CLAUDE.md schreibt OKLCH vor. Kein Funktionsbruch, aber Inkonsistenz zu künftigen Theme-Änderungen.

---

## Nicht bestätigt (REFUTED)

| Kandidat | Grund |
|---|---|
| Header-Injection via `$name`/`$telefon` | Beide fließen nur in HTML-Body mit `htmlspecialchars()`, nicht in Mail-Header |
| Zweite Storno-Mail bei Doppel-Storno | Code prüft `storniert`-Flag und bricht mit `exit` ab |
| Doppelklick-Race auf Submit-Button | `btn.disabled = true` steht synchron vor `fetch()` |
| `$storno_link` XSS | Token ist `bin2hex` (hex-only), SITE_URL ist Konstante |

---

## Priorisierte To-Do-Liste

| Prio | Datei | Maßnahme |
|---|---|---|
| 🔴 1 | `cancel.php` | GET → Bestätigungsseite, Storno nur per POST |
| 🔴 2 | `db_setup.sql` | UNIQUE KEY ohne `storniert`, NULL-Trick für stornierte Zeilen |
| 🟠 3 | `buchung.php` | Transaktion + `SELECT FOR UPDATE` um Race Window zu schließen |
| 🟠 4 | `buchung.php` | IP-basiertes Rate-Limiting (3 Req/h) |
| 🟠 5 | beide PHP | `buchung-helpers.php` für geteilte Funktionen |
| 🟡 6 | `buchung-config.php` | Dokumentation Wartungsprozess für Monatswechsel |

---

*Generiert mit Claude Code – /code-review high*
