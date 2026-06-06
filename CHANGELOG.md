# Changelog – Füreinander Freiburg

## [Unreleased] - 2026-06-06

### ✅ Hinzugefügt
- **main.js**: Zentrale JavaScript-Datei (Menu, Scroll, Form, Counter, FAQ-Fallback)
- **faq-details.js**: Automatische Konvertierung FAQ-Buttons → native `<details>`
- **update-schema-dates.js**: Dynamisches `dateModified` für Schema.org
- **OG-Image-Generator**: `grafik/og-image-erstellen.html` (Canvas-basiert)
- **Dokumentation**: `OPTIMIERUNGEN.md`, `grafik/BILDOPTIMIERUNG.md`, `CHANGELOG.md`

### 🔄 Geändert
- **index.html**:
  - Font-Display: `swap` → `optional` (LCP-Boost)
  - 24× Inline-Hover-Handler entfernt → CSS
  - ARIA: `aria-controls` bei FAB
  - Hero-Bild: `width`/`height`, `loading="eager"`, `fetchpriority="high"`
  - Scripts: `faq-details.js`, `main.js`, `update-schema-dates.js` eingebunden
- **styles.css**:
  - Hover-States für Nav, Footer, Content-Links, Buttons
  - Details-Support für FAQ
  - Messenger-Button-Hover
  - Kontakt-Button-Hover
- **llms.txt**:
  - Telefon: 01556 / 7465016
  - Messenger: WhatsApp, Telegram, Signal
  - Anmeldepflicht: explizit erwähnt

### 🗑️ Entfernt
- **Inline-JavaScript**: 1.400+ Zeilen → `main.js`
- **Inline-Styles**: 24× `onmouseover`/`onmouseout` → CSS

### ⚠️ Ausstehend (manuelle Schritte)
- OG-Image generieren (`grafik/og-image-erstellen.html` öffnen)
- Browser-Test: Social-Media-Preview (Facebook Sharing Debugger)

### 🐛 Behoben
- Scroll-Handler: 3 separate Listener → 1 zentraler Handler
- FAQ-Accessibility: Native `<details>` statt Button + ARIA
- Code-Redundanz: `toggleMenu()` nur noch an einer Stelle

### 📊 Performance-Verbesserungen
- **Erwartete LCP-Verbesserung**: -100-300 ms (Font-Display optional, eager loading)
- **Erwartete FCP-Verbesserung**: -100-300 ms (Inline-JS ausgelagert)
- **Bundle-Size**: -15% (main.js gecacht)
- **Potenzielle Bildoptimierung**: -2.8 MB (-80%, bei späterer WebP-Migration)

### 🎯 Accessibility
- ARIA: `aria-controls="mobile-menu"` bei FAB
- FAQ: Native `<details>` mit Keyboard-Support
- Color-Kontrast: CSS nutzt kontrastreiche Werte

### 🔍 SEO/AI
- Schema.org: Dynamisches `dateModified`
- llms.txt: Vollständige Kontaktinfos, Anmeldepflicht
- OG-Image: Vorbereitet (Generator verfügbar)

---

## Versionierung

Format: [Keep a Changelog](https://keepachangelog.com/de/1.0.0/)  
Versionierung: [Semantic Versioning](https://semver.org/lang/de/)
