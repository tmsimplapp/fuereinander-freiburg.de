# CLAUDE.md – Website Füreinander Freiburg

## Globale Regeln

Gilt zusätzlich: `C:\Users\tmass\OneDrive\Megamind\CLAUDE.md` (Token-Effizienz, Kommunikationsstil, Anrede per Du)

## Projektbeschreibung

Website der Selbsthilfegruppe **Füreinander Freiburg** – eine Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas.

**Mehrseitig, komplett PHP:** `index.php` (Startseite), `ausstieg-folgen.php`, `angehoerige.php`, `partner.php`, `themen.php` (Artikel), `galerie.php`, `termine.php`, `rechtliches.php`. Dazu Admin-Bereich (`admin/`) mit Termin-, Artikel-, Galerie- und Community-Verwaltung.

## Arbeitsordner

**Repo-Root = Web-Root.** Quellcode, Logos und alle Assets liegen direkt hier:
`C:\Users\tmass\OneDrive\KI Projekte_Laufwerk\füreinander-freiburg.de`

Kein separater Ordner unter `Dokumente\Selbsthilfegruppe` mehr – falls in älteren Notizen (Megamind-Vault) abweichende Pfade auftauchen, gilt dieser Ordner hier als aktuell.

**Vor der ersten Datei-Suche in diesem Projekt: `git ls-files` (bzw. `rtk git ls-files`) statt Glob/Grep auf Verdacht.** Das Repo enthält u. a. `.git/objects/*`-Treffer bei generischem `Glob *`, die die echte Struktur verdecken.

## Schnellreferenz – Dateistruktur

### Seiten (alle `.php`, keine `.html`)

| Datei | Zweck |
|-------|-------|
| `index.php` | Startseite (Hero, Über uns, Gruppenregeln, FAQ, Kontakt) |
| `ausstieg-folgen.php` | Infoseite Ausstiegsfolgen |
| `angehoerige.php` | Infoseite für Angehörige |
| `partner.php` | Partnerseite |
| `themen.php` | Artikelübersicht; leitet bei Slug (`/themen/slug`) an `themen-detail.php` weiter |
| `themen-detail.php` | Einzelartikel aus DB-Tabelle `artikel` |
| `galerie.php` | Bildergalerie aus DB-Tabelle `galerie` |
| `termine.php` | Terminübersicht, liest live aus DB-Tabelle `gruppentermine` |
| `rechtliches.php` | Rechtliche Hinweise |

### Gemeinsame Bausteine

| Datei | Zweck |
|-------|-------|
| `partials/nav.php` | Navigation (alle Seiten) – setzt `$active_page` voraus |
| `partials/footer.php` | Footer |
| `partials/sticky-phone-bar.php` | Fixierte Kontaktleiste (Telefon/WhatsApp/Telegram) |

### Backend / Endpoints

| Datei | Zweck |
|-------|-------|
| `buchung-config.php` | **Zentrale DB-Config** (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) – gitignored. Name historisch, wird von allen Seiten geladen |
| `telegram-config.php` | Telegram-Bot-Token/Chat-ID – gitignored |
| `naechster-termin.php` | JSON-Endpoint, nächster Termin (Fetch aus `index.php`) |
| `mailer.php` | Kontaktformular-Versand + Telegram-Benachrichtigung |
| `counter.php` | Seitenaufruf-Zähler (JSON), schreibt `statistiken_seitenaufrufe` |
| `cron_daily_stats.php` | Täglicher Cronjob (23:59), aggregiert nach `statistiken` |
| `check_imap_telegram.php` | IMAP-Postfach → Telegram-Weiterleitung |
| `themen-helpers.php` | `parse_markdown()`, `link_ziel_erlaubt()` |
| `galerie-helpers.php` | Upload, Skalierung, Löschen von Galeriebildern |

### Admin (`admin/`)

| Bereich | Dateien |
|---------|---------|
| Auth | `login.php`, `login_2fa.php`, `logout.php`, `auth.php`, `profil.php`, `config.php` |
| Layout | `header.php`, `nav.php`, `footer.php`, `admin.css`, `modal.js` |
| Termine | `termine.php`, `termin-bearbeiten.php`, `termin-loeschen.php`, `termin-toggle.php` |
| Artikel | `artikel.php`, `artikel-editor.php`, `artikel-loeschen.php`, `artikel-toggle.php`, `artikel-download.php` |
| Galerie | `galerie.php`, `galerie-loeschen.php`, `galerie-toggle.php` |
| Community | `community.php`, `community-bearbeiten.php`, `community-loeschen.php`, `community-toggle.php`, `community-regionen.php`, `community-tags.php`, `community-notiz-loeschen.php` |

### Assets & Sonstiges

| Datei/Ordner | Zweck |
|--------------|-------|
| `styles.css`, `tailwind.css`, `tailwind-src.css`, `tailwind.config.js` | Styling (Tailwind CLI-Build) |
| `transitions.css`, `transitions.js` | Seitenübergänge |
| `main.js`, `galerie.js` | Frontend-Logik |
| `grafik/` | Logo, Icons, OG-Image, Hero-Grafiken (PNG + WebP) |
| `galerie-bilder/` | Upload-Ziel der Galerie (eigene `.htaccess`) |
| `llms.txt`, `robots.txt`, `sitemap.xml`, `site.webmanifest` | Crawler/PWA-Metadaten |
| `telegrambot/telegrambot.txt` | Notizen zum Telegram-Bot |
| `grafik/Füreinander Freiburg.svg` | Logo-SVG – referenziert als `grafik/F%C3%BCreinander%20Freiburg.svg` |

**Wichtig für Änderungen:**
- Tailwind wird per CLI gebaut (`tailwind-src.css` → `tailwind.css`) – nach CSS-Änderungen `npm run build` (Watch: `npm run watch`)
- `tailwind.config.js` scannt `./*.php`, `./partials/*.php`, `./admin/*.php` – neue Ordner dort ergänzen, sonst werden Klassen wegoptimiert
- Custom-CSS-Klassen (`.btn-primary`, `.reveal`, `.card-hover`, etc.) → `styles.css`
- `styles.css` lädt nach `tailwind.css` und kann Utilities überschreiben
- Logo-Pfad ist immer `grafik/F%C3%BCreinander%20Freiburg.svg`

## Technischer Stack

- **Seiten**: durchgehend PHP (kein statisches HTML mehr)
- **CSS**: Tailwind CLI-Build (kein CDN) + eigene Klassen in `styles.css`
- **Backend**: PHP + MySQL via PDO, Config in `buchung-config.php` (nicht in Git)
- **Benachrichtigungen**: Telegram-Bot (Kontaktformular, IMAP-Weiterleitung)
- Kein JS-Framework, kein SPA-Build

### DB-Tabellen

`gruppentermine`, `artikel`, `galerie`, `admins`, `admin_rate_limit`, `statistiken`, `statistiken_seitenaufrufe`, `community_organisationen`, `community_personen`, `community_notizen`, `community_regionen`, `community_tags`, `community_organisation_regionen`, `community_organisation_tags`

Schema-Referenz: `db-struktur.pdf`

## Seitenstruktur (index.php)

1. **Hero** – Titel in Caveat Brush, Kernaussage, CTA
2. **`#ueber-uns`** – „Was ist Füreinander Freiburg?"
3. **`#gruppenregeln`** – Regeln der Gruppe
4. **`#faq`** – FAQ-Abschnitt
5. **`#kontakt`** / `#kontaktformular` – Kontaktformular, E-Mail `kontakt@fuereinander-freiburg.de`
6. **Footer** (`partials/footer.php`) – Link zur Selbsthilfekontaktstelle Freiburg, Navigation

Zusätzlich: `partials/sticky-phone-bar.php` auf allen Seiten.

## Kontaktkanäle

- Telefon/WhatsApp/Telegram: `+49 155 67465016`
- E-Mail: `kontakt@fuereinander-freiburg.de`

## Farbpalette & Typografie

Definiert in `tailwind.config.js`:

| Gruppe | Tailwind-Name | Hex |
|--------|---------------|-----|
| Text | `text-strong` | `#3d3225` |
| | `text-body` | `#5c4e3a` |
| | `text-muted` | `#6f6047` |
| | `text-dark` | `#1a2820` |
| | `text-footer` | `#5c3d1e` |
| Akzent | `accent` | `#5fa88a` |
| | `accent-dark` | `#4a8a6e` |
| | `mint` | `#a9e2cc` |
| | `mint-dark` | `#8ed4b8` |
| | `mint-soft` | `#d4f1e6` |
| | `mint-light` | `#f0faf6` |
| Warm | `warmyellow` | `#ffda69` |
| | `lightyellow` | `#fff4d6` |
| | `cream` | `#FEFAE0` |
| | `tan` | `#E2C2A2` |
| | `tan-dark` | `#d4b391` |
| | `tan-pale` | `#fffaf0` |
| Dunkel | `dark` | `#1a2820` |

**Fonts** (Google Fonts): `font-display` / `font-body` = Lato, `font-caveat` = Caveat Brush (Hero-Überschriften)

## Projektregeln

- **SEO**: Meta-Tags, Schema.org (`SupportGroup`), semantisches HTML, Core Web Vitals optimieren
- **Mobile-First**: Responsive Design – funktioniert auf Handy, Tablet und Desktop
- **KI-Auffindbarkeit**: Strukturierte Daten, `llms.txt` pflegen, klare Informationsarchitektur
- **Sprache**: Alle Inhalte ausschließlich auf Deutsch
- **Kein Framework-Overhead**: Kein React, kein SPA-Build
- **Sicherheit**: `buchung-config.php`, `telegram-config.php` und `admin/`-Login nicht öffentlich dokumentieren, keine Secrets in Commits

## Inhaltliche Grenzen (Website)

Die Gruppe hat folgende Regeln, die auf der Website widergespiegelt werden:
- Keine theologischen Diskussionen
- Kein Kritisieren der Wachtturm-Gesellschaft
- Neutraler, persönlicher Austausch
- Die Gruppe ist kein Ersatz für psychologische Therapie

## Bevorzugte Skills

Die folgenden Skills sind bevorzugt zu verwenden, sofern sie für die angeforderte Aufgabe nötig sind. Hole vor der Skillnutzung die Freigabe dafür bei mir ein:

1. frontend-design
2. design-taste-frontend
3. ui-ux-pro-max
4. web-design-guidelines
5. responsive-design

Schlage weitere passende Skills vor um eine Aufgabe ideal auszuführen.
