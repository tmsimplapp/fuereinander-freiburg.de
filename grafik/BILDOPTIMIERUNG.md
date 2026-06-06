# Bildoptimierung – Anleitung

## Hero-Bild: WebP + Responsive Srcset

**Aktueller Stand:**
- Original: `hero_grafik.png` (2816×1536, 3.5 MB)
- Benötigt: WebP-Versionen in 3 Breakpoints

### Schritt 1: WebP-Konvertierung

Mit ImageMagick (empfohlen):
```bash
cd grafik

# 560w (Mobile)
magick hero_grafik.png -resize 560x -quality 85 hero_grafik-560w.webp

# 720w (Tablet)
magick hero_grafik.png -resize 720x -quality 85 hero_grafik-720w.webp

# 900w (Desktop)
magick hero_grafik.png -resize 900x -quality 85 hero_grafik-900w.webp
```

Alternativ online: https://squoosh.app/

### Schritt 2: HTML aktualisieren

In `index.html` Zeile ~407 ersetzen:
```html
<!-- Alt (nur PNG): -->
<img src="grafik/hero_grafik.png" alt="" aria-hidden="true" class="hero-full-img">

<!-- Neu (WebP + Responsive): -->
<picture>
  <source srcset="grafik/hero_grafik-560w.webp 560w,
                  grafik/hero_grafik-720w.webp 720w,
                  grafik/hero_grafik-900w.webp 900w"
          sizes="(max-width: 640px) 85vw,
                 (max-width: 900px) 55vw,
                 900px"
          type="image/webp">
  <img src="grafik/hero_grafik.png"
       alt=""
       aria-hidden="true"
       class="hero-full-img"
       loading="eager"
       fetchpriority="high">
</picture>
```

**Erwartete Einsparung:** -2.8 MB (-80%)

---

## OG-Image erstellen

**Spezifikation:**
- Größe: 1200×630 px
- Format: PNG oder JPEG
- Inhalt: Logo + Tagline
- Pfad: `grafik/og-image.png`

### Design-Vorlage

```
┌─────────────────────────────────────────────────┐
│                                                 │
│        [Logo: grafik/Füreinander Freiburg.svg] │
│                                                 │
│    Selbsthilfegruppe für zweifelnde und        │
│      ausgestiegene Zeugen Jehovas              │
│                                                 │
│              Freiburg im Breisgau               │
│                                                 │
└─────────────────────────────────────────────────┘

Hintergrund: #FEFAE0 (Cremeweiß)
Text: #3d3225 (Dunkelbraun)
Akzent: #a9e2cc (Mint)
```

### Erstellung

**Option 1: Canva**
1. 1200×630 Template
2. Logo hochladen
3. Text-Layer hinzufügen
4. Export als PNG

**Option 2: Figma/Photoshop**
1. Neue Datei 1200×630
2. Hintergrund #FEFAE0
3. Logo zentriert (max 400px breit)
4. Text (Playfair Display + Source Serif 4)

**Option 3: ImageMagick (Script)**
```bash
magick -size 1200x630 xc:"#FEFAE0" \
  -gravity center \
  -pointsize 42 -font "Source-Serif-4" \
  -fill "#3d3225" \
  -annotate +0+100 "Selbsthilfegruppe für zweifelnde und\nausgestiegene Zeugen Jehovas" \
  -pointsize 28 \
  -annotate +0+180 "Freiburg im Breisgau" \
  og-image.png
```

### Nach Erstellung

Datei nach `grafik/og-image.png` speichern → automatisch von HTML referenziert.

---

## Checkliste

- [ ] Hero-WebP: 560w, 720w, 900w erstellt
- [ ] `index.html` aktualisiert (`<picture>`)
- [ ] OG-Image 1200×630 erstellt
- [ ] `grafik/og-image.png` gespeichert
- [ ] Browser-Test (Chrome DevTools → Network)
- [ ] Social-Media-Preview-Test (Facebook Debugger)
