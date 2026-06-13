// View Transitions für interne Navigation

document.addEventListener('DOMContentLoaded', () => {
  // Prüfe Browser-Support
  if (!document.startViewTransition) {
    return;
  }

  // Funktion zum Ersetzen der Seitenelemente
  function replacePageElements(doc) {
    const selectors = [
      'nav',
      '#nav-toggle-fab',
      '#mobile-menu',
      'main',
      'footer',
      '#sticky-phone-bar'
    ];

    selectors.forEach(selector => {
      const newEl = doc.querySelector(selector);
      const oldEl = document.querySelector(selector);
      if (newEl && oldEl) {
        oldEl.replaceWith(newEl);
      } else if (newEl && !oldEl) {
        document.body.appendChild(newEl);
      } else if (!newEl && oldEl) {
        oldEl.remove();
      }
    });
  }

  // Alle internen Links abfangen
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a');

    // Nur interne Links, keine Mailto/Tel/Externe
    if (!link || !link.href || link.href.startsWith('mailto:') || link.href.startsWith('tel:') || link.target === '_blank') {
      return;
    }

    // Prüfe ob Link zur gleichen Domain/Protokoll gehört
    let url;
    try {
      url = new URL(link.href);
      // Erlaube nur same-origin ODER relative Pfade bei file://
      if (url.origin !== location.origin && location.protocol !== 'file:') {
        return;
      }
    } catch {
      return;
    }

    // Wenn es nur ein Hash-Link auf derselben Seite ist, keine Transition ausführen (Standardverhalten erlaubt)
    if (decodeURIComponent(url.pathname) === decodeURIComponent(location.pathname) && url.search === location.search && url.hash) {
      return;
    }

    e.preventDefault();

    // Starte Transition
    document.startViewTransition(async () => {
      // Lade neue Seite
      const response = await fetch(link.href);
      const html = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      // Ersetze Titel
      document.title = doc.title;

      // Ersetze Seitenelemente
      replacePageElements(doc);

      // Update URL
      history.pushState(null, '', link.href);

      // Scroll nach oben oder zum Hash
      if (url.hash) {
        const targetEl = document.getElementById(url.hash.substring(1));
        if (targetEl) {
          targetEl.scrollIntoView({ behavior: 'auto' });
        } else {
          window.scrollTo({ top: 0, behavior: 'instant' });
        }
      } else {
        window.scrollTo({ top: 0, behavior: 'instant' });
      }
      window.dispatchEvent(new Event('scroll'));

      // Event-Listener neu binden (mobile menu etc.)
      reinitializeScripts();
    });
  });

  // Zurück/Vor-Buttons im Browser
  window.addEventListener('popstate', () => {
    document.startViewTransition(async () => {
      const response = await fetch(location.href);
      const html = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      document.title = doc.title;

      // Ersetze Seitenelemente
      replacePageElements(doc);

      const popUrl = new URL(location.href);
      if (popUrl.hash) {
        const targetEl = document.getElementById(popUrl.hash.substring(1));
        if (targetEl) {
          targetEl.scrollIntoView({ behavior: 'auto' });
        } else {
          window.scrollTo({ top: 0, behavior: 'instant' });
        }
      } else {
        window.scrollTo({ top: 0, behavior: 'instant' });
      }
      window.dispatchEvent(new Event('scroll'));
      reinitializeScripts();
    });
  });

  // Funktion für Script-Reinitialisierung nach Transition
  function reinitializeScripts() {
    if (typeof window.reinitializeScripts === 'function') {
      window.reinitializeScripts();
    }
  }
});
