// View Transitions für interne Navigation

document.addEventListener('DOMContentLoaded', () => {
  // Prüfe Browser-Support
  if (!document.startViewTransition) {
    return;
  }

  // Alle internen Links abfangen
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a');

    // Nur interne Links, keine Mailto/Tel/Externe
    if (!link || !link.href || link.href.startsWith('mailto:') || link.href.startsWith('tel:') || link.target === '_blank') {
      return;
    }

    // Prüfe ob Link zur gleichen Domain/Protokoll gehört
    try {
      const url = new URL(link.href);
      // Erlaube nur same-origin ODER relative Pfade bei file://
      if (url.origin !== location.origin && location.protocol !== 'file:') {
        return;
      }
    } catch {
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

      // Ersetze main-Content
      const newMain = doc.querySelector('main');
      const oldMain = document.querySelector('main');
      if (newMain && oldMain) {
        oldMain.replaceWith(newMain);
      }

      // Update URL
      history.pushState(null, '', link.href);

      // Scroll nach oben
      window.scrollTo({ top: 0, behavior: 'instant' });

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

      const newMain = doc.querySelector('main');
      const oldMain = document.querySelector('main');
      if (newMain && oldMain) {
        oldMain.replaceWith(newMain);
      }

      window.scrollTo({ top: 0, behavior: 'instant' });
      reinitializeScripts();
    });
  });

  // Funktion für Script-Reinitialisierung nach Transition
  function reinitializeScripts() {
    // Mobile Menu Toggle
    const fabButton = document.getElementById('nav-toggle-fab');
    const mobileMenu = document.getElementById('mobile-menu');

    if (fabButton && mobileMenu) {
      fabButton.addEventListener('click', () => {
        const isHidden = mobileMenu.classList.contains('hidden');
        mobileMenu.classList.toggle('hidden', !isHidden);
        fabButton.setAttribute('aria-expanded', isHidden);
      });
    }

    // Sticky Bar (falls vorhanden)
    const stickyBar = document.querySelector('.sticky-phone-bar');
    if (stickyBar && typeof window.updateStickyBar === 'function') {
      window.updateStickyBar();
    }
  }
});
