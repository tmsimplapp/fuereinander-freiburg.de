## Code-Änderungen (WICHTIG)

- **Niemals ohne Rücksprache Code ändern.** Hole immer erst die explizite Freigabe von Tobias ein, bevor du Skripte anpasst, erstellst oder löschst.
- **Datenbank-Anpassungen**: SQL-Befehle zur Anpassung der Datenbank (z. B. `ALTER TABLE`) immer direkt im Chat als kopierbaren Codeblock ausgeben.

## Token-Effizienz

- Verwende so wenig Tokens wie möglich, ohne die Verständlichkeit zu verlieren.
- Wiederhole meinen Prompt nicht; zitiere nur Code- oder Textausschnitte, die du wirklich brauchst.
- Erzeuge keine Zusammenfassungen oder Wiederholungen, außer ich frage explizit danach.
- **Standard-Output-Budget**: max. 60 Wörter. Bei einfacher Frage/Aktion: max. 25 Wörter.
- **Output-Form**: 1 Satz ODER 3 Bullets ODER nur Codeblock — nichts anderes.
- **Artikel weglassen** (ein/eine/der/die/das wo möglich), Füllwörter streichen (einfach/eigentlich/grundsätzlich/wirklich), keine Höflichkeitsfloskeln.
- **Selbst-Check vor jeder Antwort**: Filler/Hedging entfernt? Dopplung gemergt? Satz kürzer möglich? Frage in erster Zeile beantwortet?
- **Zahlen > Adjektive**: "3 Fixes" statt "ein paar wichtige Korrekturen".
- Symbole nutzen wenn eindeutig: `->`, `=`, `!=`.
- **Ausnahme**: "ausführlich"/"deep dive"/"Schritt für Schritt" hebt das Budget auf.
- **Sicherheitswarnungen und irreversible Aktionen** immer vollständig ausschreiben — kein Komprimieren.
- Niemals visuelle Vorher/Nachher-Vergleichsdatei anlegen
- Niemals ungefragt Protokolldateien deiner Tätigkeit anlegen

## Kommunikationsstil (BINDEND für alle Interaktionen)

- Sprache: Deutsch, Anrede per Du
- Locker und direkt
- **Standardumfang**: max. 3 kurze Sätze ODER 5 Stichpunkte
- **Bei Code-Fragen**: nur den Codeblock, keine Erklärung (außer Tobias fordert sie ausdrücklich an)
- **Bei fehlenden Infos**: gezielt nachfragen statt raten oder spekulieren
- **Ausnahme zur Längenbegrenzung**: Wenn Tobias "ausführlich", "Schritt für Schritt", "deep dive" oder ähnliches sagt, darfst du die Längenbegrenzung ignorieren

## Projektüberblick

Website der Selbsthilfegruppe **Füreinander Freiburg** (zweifelnde und ausgestiegene Zeugen Jehovas).

**Repo-Root = Web-Root.** Vor der ersten Datei-Suche `git ls-files` nutzen statt Glob/Grep auf Verdacht.

### Seiten (alle `.php`, keine `.html`)

`index.php` (Startseite), `ausstieg-folgen.php`, `angehoerige.php`, `partner.php`, `themen.php` + `themen-detail.php` (Artikel), `galerie.php`, `termine.php`, `rechtliches.php`

Gemeinsame Bausteine: `partials/nav.php`, `partials/footer.php`, `partials/sticky-phone-bar.php`

### Backend

| Datei | Zweck |
|-------|-------|
| `buchung-config.php` | **Zentrale DB-Config** – gitignored. Name historisch, wird von allen Seiten geladen |
| `telegram-config.php` | Telegram-Bot-Zugangsdaten – gitignored |
| `naechster-termin.php` | JSON-Endpoint, nächster Termin |
| `mailer.php` | Kontaktformular + Telegram-Benachrichtigung |
| `counter.php`, `cron_daily_stats.php` | Seitenaufruf-Statistik (Zähler + täglicher Cronjob) |
| `check_imap_telegram.php` | IMAP-Postfach → Telegram |
| `themen-helpers.php`, `galerie-helpers.php` | Markdown-Parsing bzw. Bild-Upload/-Skalierung |
| `admin/` | Login mit 2FA, Verwaltung von Terminen, Artikeln, Galerie, Community |

### Struktur index.php

Hero → `#ueber-uns` → `#gruppenregeln` → `#faq` → `#kontakt` → Footer

### Kontaktkanäle

Telefon/WhatsApp/Telegram `+49 155 67465016`, E-Mail `kontakt@fuereinander-freiburg.de`

## Technischer Stack

- **Seiten**: durchgehend PHP, kein statisches HTML
- **CSS**: Tailwind CLI-Build (`tailwind-src.css` → `tailwind.css`, `npm run build`) + eigene Klassen in `styles.css`. `tailwind.config.js` scannt `./*.php`, `./partials/*.php`, `./admin/*.php`
- **Backend**: PHP + MySQL via PDO
- **Fonts**: Lato (`font-display`/`font-body`), Caveat Brush (`font-caveat`, Hero)
- Kein JS-Framework, kein SPA-Build

### DB-Tabellen

`gruppentermine`, `artikel`, `galerie`, `admins`, `admin_rate_limit`, `statistiken`, `statistiken_seitenaufrufe`, `community_organisationen`, `community_personen`, `community_notizen`, `community_regionen`, `community_tags`, `community_organisation_regionen`, `community_organisation_tags` (Schema: `db-struktur.pdf`)

### Farbpalette (tailwind.config.js)

- **Text**: `text-strong` `#3d3225`, `text-body` `#5c4e3a`, `text-muted` `#6f6047`, `text-dark` `#1a2820`, `text-footer` `#5c3d1e`
- **Akzent**: `accent` `#5fa88a`, `accent-dark` `#4a8a6e`, `mint` `#a9e2cc`, `mint-dark` `#8ed4b8`, `mint-soft` `#d4f1e6`, `mint-light` `#f0faf6`
- **Warm**: `warmyellow` `#ffda69`, `lightyellow` `#fff4d6`, `cream` `#FEFAE0`, `tan` `#E2C2A2`, `tan-dark` `#d4b391`, `tan-pale` `#fffaf0`
- **Dunkel**: `dark` `#1a2820`

## Projektregeln

- **SEO**: Meta-Tags, Schema.org (`SupportGroup`), semantisches HTML, Core Web Vitals optimieren
- **Mobile-First**: Responsive Design – funktioniert auf Handy, Tablet und Desktop
- **KI-Auffindbarkeit**: Strukturierte Daten, `llms.txt` pflegen, klare Informationsarchitektur
- **Sprache**: Alle Inhalte ausschließlich auf Deutsch
- **Kein Framework-Overhead**: Kein React, kein SPA-Build – PHP/HTML + Tailwind CSS
- **Sicherheit**: `buchung-config.php`, `telegram-config.php` und `admin/`-Login nicht öffentlich dokumentieren, keine Secrets in Commits

## Inhaltliche Grenzen (Website)

Die Gruppe hat folgende Regeln, die auf der Website widergespiegelt werden sollen:
- Keine theologischen Diskussionen
- Kein Kritisieren der Wachtturm-Gesellschaft
- Neutraler, persönlicher Austausch
- Die Gruppe ist kein Ersatz für psychologische Therapie
