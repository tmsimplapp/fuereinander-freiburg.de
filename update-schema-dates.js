// Dynamisches dateModified für Schema.org Article
// Performance-Optimierung: Nur wenn Schema vorhanden

(function() {
  const schemaScripts = document.querySelectorAll('script[type="application/ld+json"]');

  schemaScripts.forEach(script => {
    try {
      const data = JSON.parse(script.textContent);

      // Nur Article-Schema updaten
      if (data['@type'] === 'Article' && data.dateModified) {
        // Aktuelles Datum im ISO-Format
        const today = new Date().toISOString().split('T')[0];
        data.dateModified = today;
        script.textContent = JSON.stringify(data, null, 2);
      }
    } catch (e) {
      // Skip invalid JSON
    }
  });
})();
