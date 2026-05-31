# AGENTS.md – Landingpage Füreinander Freiburg

## Globale Regeln

Gilt zusätzlich: `C:\Users\tmass\OneDrive\Megamind\AGENTS.md` (Token-Effizienz, Kommunikationsstil, Anrede per Du)

## Projektbeschreibung

Landingpage für die Selbsthilfegruppe **Füreinander Freiburg** – eine Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas.

**Startseite mit verknüpften Unterseiten** (Termine, Partner, Rechtliche Hinweise).

## Projektinformationen

Weiterführende Projektdokumentation und Entscheidungen befinden sich hier:
`C:\Users\tmass\OneDrive\Megamind\02 Projekte\Website Selbsthilfegruppe\`

- `Website Selbsthilfegruppe.md` – Hauptdokumentation, Ziel, Design, Farbpalette, Quelltexte
- `Mindestangaben Website Selbsthilfegruppe.md` – Vorlage für die Seite „Rechtliche Hinweise"

## Arbeitsordner

| Inhalt | Pfad |
|--------|------|
| Landingpage (Quellcode) | `C:\Users\tmass\OneDrive\Dokumente\Selbsthilfegruppe\Landingpage` |
| Logos (eigen & fremd) | `C:\Users\tmass\OneDrive\Dokumente\Selbsthilfegruppe\Logos` |
| Werbematerialien | `C:\Users\tmass\OneDrive\Dokumente\Selbsthilfegruppe` |

## Schnellreferenz – Dateistruktur

| Datei | Zweck |
|-------|-------|
| `index.html` | Hauptseite (Hero, Über uns, Gruppenregeln, Kontakt, Footer) |
| `partner.html` | Partnerseite (Platzhalter, noch im Aufbau) |
| `rechtliches.html` | Rechtliche Hinweise (Platzhalter, noch im Aufbau) |
| `styles.css` | Gemeinsame Custom-CSS-Klassen aller drei Seiten |
| `grafik/Füreinander Freiburg.svg` | Logo-SVG – wird auf allen Seiten als `grafik/F%C3%BCreinander%20Freiburg.svg` referenziert |
| `grafik/hero_grafik.png` | Pusteblume-Bild im Hero (nur index.html) |

**Wichtig für Änderungen:**
- Tailwind-Config (`tailwind.config`) bleibt als `<script>`-Block in jeder HTML-Datei (CDN-Anforderung)
- Custom-CSS-Klassen (`.btn-primary`, `.reveal`, `.card-hover`, etc.) → `styles.css`
- Logo-Pfad ist immer `grafik/F%C3%BCreinander%20Freiburg.svg` (relativ zur jeweiligen HTML-Datei)

## Technischer Stack

- **HTML-Dateien**: `index.html`, `termine.html`, `partner.html`, `rechtliches.html`
- **CSS**: Tailwind CSS via CDN + eigene Klassen in `styles.css`
- Kein Build-Prozess, kein JavaScript-Framework, keine externen Abhängigkeiten
- Die Seite wird als statische Datei ausgeliefert

## Seitenstruktur

1. **Hero Section** – Einladende Überschrift, Kernaussage, CTA-Button „Kontakt aufnehmen"
2. **Info-Abschnitt** – Drei Textkarten mit dem Quellentext der Gruppe
3. **Kontakt-Abschnitt** – E-Mail-Link: `kontakt@fuereinander-freiburg.de`
4. **Footer** – Hinweis auf Zusammenarbeit mit selbsthilfegruppen-freiburg.de und zebra-bw.com
5. **Seite „Rechtliche Hinweise"** – Separate Seite (Link im Footer), noch zu befüllen mit Name, Ort, E-Mail

## Farbpalette

| Name | Hex | Verwendung |
|------|-----|------------|
| Sage Green | `#CCD5AE` | Primäre Akzentfarbe, Buttons, Highlights |
| Hellgrün-Gelb | `#E9EDC9` | Sekundärer Hintergrund, Abschnittstrennungen |
| Cremeweiß | `#FEFAE0` | Haupt-Hintergrund |
| Warmes Pfirsich | `#FAEDCD` | Card-Hintergründe, Hero-Section |
| Warmes Tan | `#E2C2A2` | Borders, dekorative Elemente, Textakzente |

Tailwind-Bezeichnungen: `sage`, `lightgreen`, `cream`, `peach`, `tan`

## Projektregeln

- **Textgenerierung**: Für das Generieren von Texten in diesem Projekt MUSS der Skill `/humanizer` verwendet werden.
- **SEO**: Der Skill `/ai-seo` MUSS für SEO-Aufgaben verwendet werden. Meta-Tags, Schema.org (`SupportGroup`), semantisches HTML, Core Web Vitals optimieren
- **Mobile-First**: Responsive Design – funktioniert auf Handy, Tablet und Desktop
- **KI-Auffindbarkeit**: Strukturierte Daten, klare Informationsarchitektur, Machine-readable Content
- **Sprache**: Alle Inhalte ausschließlich auf Deutsch
- **Kein Framework-Overhead**: Kein React, kein Build-Tool – einfaches HTML + Tailwind CDN

## Inhaltliche Grenzen

Die Gruppe hat folgende Regeln, die auf der Website widergespiegelt werden sollen:
- Keine theologischen Diskussionen
- Kein Kritisieren der Wachtturm-Gesellschaft
- Neutraler, persönlicher Austausch
- Die Gruppe ist kein Ersatz für psychologische Therapie
- Teilnahme nur mit vorheriger Anmeldung über die Kontaktmöglichkeiten

## Quellentext der Gruppe (1:1 verwenden)

> Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas
>
> Zweifel an der Gemeinschaft oder der Entschluss, sie zu verlassen, können das Leben stark verändern. Viele verlieren ihr vertrautes Umfeld, Freunde oder Familie. Gefühle von Einsamkeit, Angst oder Orientierungslosigkeit sind keine Seltenheit.
>
> In unserer Gruppe treffen sich Menschen, die ähnliche Erfahrungen gemacht haben. In offener und wertschätzender Atmosphäre ist Raum für Austausch, gegenseitige Unterstützung und neue Perspektiven – ohne Verurteilung oder Druck.
>
> Eingeladen sind alle, die aktuell oder früher Teil der Zeugen Jehovas sind, die zweifeln oder bereits ausgestiegen sind.

## Offene Aufgaben

- [ ] Platzhalter in der Seite „Rechtliche Hinweise" befüllen (Name, Ort, E-Mail)
- [x] Ort und Zeit der Treffen auf der Terminseite eintragen
- [ ] Zukünftige Idee: Ein kleines Anmeldeformular integrieren
