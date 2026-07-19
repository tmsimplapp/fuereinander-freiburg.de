# CLAUDE.md – Landingpage Füreinander Freiburg

## Globale Regeln

Gilt zusätzlich: `C:\Users\tmass\OneDrive\Megamind\CLAUDE.md` (Token-Effizienz, Kommunikationsstil, Anrede per Du)

## Projektbeschreibung

Landingpage für die Selbsthilfegruppe **Füreinander Freiburg** – eine Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas.

**Mehrseitig:** `index.html` (Hauptseite mit Buchungswidget), `ausstieg-folgen.html`, `angehoerige.html`, `partner.html`, `termine.php`, `rechtliches.html`. Dazu Admin-Bereich (`admin/`) und Rückruf-Buchungssystem (PHP + MySQL).

## Arbeitsordner

**Repo-Root = Web-Root.** Quellcode, Logos und alle Assets liegen direkt hier:
`C:\Users\tmass\OneDrive\KI Projekte\füreinander-freiburg.de`

Kein separater Ordner unter `Dokumente\Selbsthilfegruppe` mehr – falls in älteren Notizen (Megamind-Vault) abweichende Pfade auftauchen, gilt dieser Ordner hier als aktuell.

**Vor der ersten Datei-Suche in diesem Projekt: `git ls-files` (bzw. `rtk git ls-files`) statt Glob/Grep auf Verdacht.** Das Repo enthält u. a. `.git/objects/*`-Treffer bei generischem `Glob *`, die die echte Struktur verdecken.

## Schnellreferenz – Dateistruktur

| Datei/Ordner | Zweck |
|-------|-------|
| `index.html` | Startseite (Hero, Über uns, Buchungswidget, „Nächstes Treffen", FAQ, Footer) |
| `ausstieg-folgen.html` | Infoseite Ausstiegsfolgen |
| `angehoerige.html` | Infoseite für Angehörige |
| `partner.html` | Partnerseite |
| `rechtliches.html` | Rechtliche Hinweise |
| `termine.php` | Terminübersicht, liest live aus DB-Tabelle `gruppentermine` |
| `naechster-termin.php` | JSON-Endpoint, liefert nächsten Termin (für index.html-Fetch) |
| `buchung.php`, `buchung-helpers.php`, `buchung-config.php` | Rückruf-Buchungssystem (Buchungslogik, Config, DB-Zugangsdaten) |
| `cancel.php` | Terminstorno über Link |
| `db_setup.sql` | DB-Schema fürs Buchungssystem |
| `admin/` | Admin-Bereich (Login, 2FA, Termine anlegen/bearbeiten/löschen) |
| `mailer.php`, `counter.php` | Formular-Mailversand, Zähler |
| `styles.css`, `tailwind.css`, `tailwind-src.css`, `tailwind.config.js` | Styling (Tailwind CLI-Build, siehe unten) |
| `main.js`, `transitions.js` | Frontend-Logik, Seitenübergänge |
| `grafik/Füreinander Freiburg.svg` | Logo-SVG – referenziert als `grafik/F%C3%BCreinander%20Freiburg.svg` |

**Wichtig für Änderungen:**
- Tailwind wird per CLI gebaut (`package.json`/`tailwind-src.css` → `tailwind.css`) – nach CSS-Änderungen `npm run build` nicht vergessen
- Custom-CSS-Klassen (`.btn-primary`, `.reveal`, `.card-hover`, etc.) → `styles.css`
- Logo-Pfad ist immer `grafik/F%C3%BCreinander%20Freiburg.svg` (relativ zur jeweiligen HTML-Datei)
- `styles.css` lädt nach `tailwind.css` und kann Utilities überschreiben

## Technischer Stack

- **Seiten**: statisches HTML (`index.html`, `partner.html`, etc.) + dynamisches PHP (`termine.php`, `admin/*`, Buchungssystem)
- **CSS**: Tailwind CLI-Build (kein CDN mehr) + eigene Klassen in `styles.css`
- **Backend**: PHP + MySQL (Zugangsdaten in `buchung-config.php`, nicht in Git)
- Kein JS-Framework, kein SPA-Build

## Seitenstruktur (index.html)

1. **Hero Section** – Einladende Überschrift, Kernaussage, CTA-Button „Kontakt aufnehmen"
2. **Info-Abschnitt** – Drei Textkarten mit dem Quellentext der Gruppe
3. **Buchungswidget** – Rückruf-Anfrage mit Slot-Auswahl (Name, Telefon, E-Mail)
4. **„Nächstes Treffen"** – Datum/Zeit live via `naechster-termin.php`, Link zu allen Terminen
5. **FAQ-Abschnitt**
6. **Kontakt-Abschnitt** – E-Mail-Link: `kontakt@fuereinander-freiburg.de`
7. **Footer** – Link zur Selbsthilfekontaktstelle Freiburg (selbsthilfegruppen-freiburg.de), Navigation zu allen Unterseiten

## Farbpalette

| Name | Hex | Verwendung |
|------|-----|------------|
| Mint | `#a9e2cc` | Primäre Akzentfarbe (Buttons, Icons, visuelle Anker) |
| Warmgelb | `#ffda69` | Sekundärer Akzent (Karten, FAQ-Buttons, Highlights) |
| Cremeweiß | `#FEFAE0` | Haupt-Hintergrund |
| Hellgelb | `#fff4d6` | Card-Hintergründe (alternierend mit Warmgelb) |
| Warmes Tan | `#E2C2A2` | Borders, dekorative Elemente, Infostreifen |

Tailwind-Bezeichnungen: `mint`, `warmyellow`, `cream`, `lightyellow`, `tan`

## Projektregeln

- **SEO**: Meta-Tags, Schema.org (`SupportGroup`), semantisches HTML, Core Web Vitals optimieren
- **Mobile-First**: Responsive Design – funktioniert auf Handy, Tablet und Desktop
- **KI-Auffindbarkeit**: Strukturierte Daten, klare Informationsarchitektur, Machine-readable Content
- **Sprache**: Alle Inhalte ausschließlich auf Deutsch
- **Kein Framework-Overhead**: Kein React, kein SPA-Build
- **Sicherheit**: `buchung-config.php` (DB-Zugangsdaten) und `admin/`-Login nicht öffentlich dokumentieren, keine Secrets in Commits

## Inhaltliche Grenzen

Die Gruppe hat folgende Regeln, die auf der Website widergespiegelt werden sollen:
- Keine theologischen Diskussionen
- Kein Kritisieren der Wachtturm-Gesellschaft
- Neutraler, persönlicher Austausch
- Die Gruppe ist kein Ersatz für psychologische Therapie

## Quellentext der Gruppe (1:1 verwenden)

> Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas
>
> Zweifel an der Gemeinschaft oder der Entschluss, sie zu verlassen, können das Leben stark verändern. Viele verlieren ihr vertrautes Umfeld, Freunde oder Familie. Gefühle von Einsamkeit, Angst oder Orientierungslosigkeit sind keine Seltenheit.
>
> In unserer Gruppe treffen sich Menschen, die ähnliche Erfahrungen gemacht haben. In offener und wertschätzender Atmosphäre ist Raum für Austausch, gegenseitige Unterstützung und neue Perspektiven – ohne Verurteilung oder Druck.
>
> Eingeladen sind alle, die aktuell oder früher Teil der Zeugen Jehovas sind, die zweifeln oder bereits ausgestiegen sind.

## Offene Aufgaben

- [ ] Meta-Title/Description auf `index.html` und `termine.php` final verfeinern
