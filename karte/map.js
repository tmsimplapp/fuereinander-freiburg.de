// ═══════════════════════════════════════
// LEAFLET KARTE – SÜDDEUTSCHLAND
// Interaktive Open-Source-Karte mit OpenStreetMap
// ═══════════════════════════════════════

// 1. KARTE INITIALISIEREN
// Zentriert auf Freiburg im Breisgau
// Zoom-Level 10 zeigt die Stadt und Umgebung optimal
const map = L.map('map').setView([48.020812, 7.809163], 10);

// 2. OPENSTREETMAP HUMANITARIAN TILELAYER EINBINDEN
// Humanitärer Kartenstil – optimiert für Lesbarkeit und Katastrophenhilfe
// DSGVO-konform, keine API-Keys nötig
L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors, Tiles style by <a href="https://www.hotosm.org/" target="_blank">Humanitarian OpenStreetMap Team</a>',
  maxZoom: 19,
  minZoom: 6
}).addTo(map);

// 3. STANDORTE DEFINIEREN
// Array von Objekten – jeder Standort hat Name, Koordinaten und Beschreibung
// Füge hier weitere Standorte hinzu, indem du neue Objekte in das Array einfügst
const standorte = [
  {
    name: 'Freiburg',
    coords: [48.020812, 7.809163],
    beschreibung: '<strong>Freiburg</strong><br>Hier leben wir: Die Initiatoren der Selbsthilfegruppe.<br><em>Selbsthilfegruppe fuereinander-freiburg.de</em>'
  },

];

// 4. MARKER AUF DER KARTE PLATZIEREN
// Schleife durch das Array und erstelle für jeden Standort einen Marker mit Popup
standorte.forEach((standort) => {
  // Marker erstellen
  const marker = L.marker(standort.coords);

  // Popup mit Beschreibung an Marker binden
  marker.bindPopup(standort.beschreibung);

  // Marker zur Karte hinzufügen
  marker.addTo(map);
});

// ═══════════════════════════════════════
// FERTIG! 🎉
// ═══════════════════════════════════════
// So fügst du weitere Standorte hinzu:
//
// 1. Koordinaten finden (z.B. auf https://www.latlong.net/)
// 2. Neues Objekt zum 'standorte'-Array hinzufügen:
//
//    {
//      name: 'Freiburg',
//      coords: [47.9990, 7.8421],
//      beschreibung: '<strong>Freiburg</strong><br>Stadt im Breisgau.<br><em>Beispiel-Text</em>'
//    }
//
// 3. Speichern – fertig! Der Marker erscheint automatisch.
