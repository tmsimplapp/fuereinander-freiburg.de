/**
 * Gemeinsames Verhalten für die Lösch-Modals im Admin-Bereich.
 *
 * Die Seiten bringen ihr Modal-Markup und ihre Texte selbst mit; dieses Script
 * ergänzt nur das, was überall gleich ist: Escape schließt, der Fokus bleibt im
 * Dialog gefangen und kehrt danach auf den auslösenden Button zurück.
 *
 * Wird das Overlay per classList.add('active') geöffnet, greift alles
 * automatisch – die Seiten müssen dafür nichts aufrufen.
 */
(function () {
  'use strict';

  var FOKUSSIERBAR = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  var offeneOverlays = [];
  var ausloeser = null;

  function sichtbareFokusZiele(overlay) {
    return Array.prototype.filter.call(
      overlay.querySelectorAll(FOKUSSIERBAR),
      function (el) { return el.offsetParent !== null; }
    );
  }

  function schliessen(overlay) {
    overlay.classList.remove('active');
  }

  // Fokus im Dialog halten: Tab am Ende springt zurück an den Anfang.
  function fokusFalle(e, overlay) {
    if (e.key !== 'Tab') return;
    var ziele = sichtbareFokusZiele(overlay);
    if (!ziele.length) return;

    var erstes = ziele[0];
    var letztes = ziele[ziele.length - 1];

    if (e.shiftKey && document.activeElement === erstes) {
      e.preventDefault();
      letztes.focus();
    } else if (!e.shiftKey && document.activeElement === letztes) {
      e.preventDefault();
      erstes.focus();
    }
  }

  document.addEventListener('keydown', function (e) {
    if (!offeneOverlays.length) return;
    var oben = offeneOverlays[offeneOverlays.length - 1];

    if (e.key === 'Escape') {
      e.preventDefault();
      schliessen(oben);
    } else {
      fokusFalle(e, oben);
    }
  });

  // Klick auf den Hintergrund schließt.
  document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('modal-overlay')) {
      schliessen(e.target);
    }
  }, true);

  // Merken, von wo aus geöffnet wurde – dorthin kehrt der Fokus zurück.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('button, a');
    if (btn) ausloeser = btn;
  }, true);

  function beobachte(overlay) {
    var beobachter = new MutationObserver(function () {
      var offen = overlay.classList.contains('active');
      var index = offeneOverlays.indexOf(overlay);

      if (offen && index === -1) {
        offeneOverlays.push(overlay);
        overlay.setAttribute('aria-hidden', 'false');

        var ziele = sichtbareFokusZiele(overlay);
        // Bei Löschdialogen bewusst nicht den Bestätigen-Button vorbelegen:
        // Enter soll nicht versehentlich löschen.
        if (ziele.length) ziele[0].focus();

      } else if (!offen && index !== -1) {
        offeneOverlays.splice(index, 1);
        overlay.setAttribute('aria-hidden', 'true');

        // Die Seite kann darauf hören, um ihren eigenen Zustand
        // (z. B. eine vorgemerkte ID) zurückzusetzen.
        overlay.dispatchEvent(new CustomEvent('modal:geschlossen', { bubbles: true }));

        if (ausloeser && document.contains(ausloeser)) {
          ausloeser.focus();
        }
        ausloeser = null;
      }
    });

    beobachter.observe(overlay, { attributes: true, attributeFilter: ['class'] });
  }

  function init() {
    var overlays = document.querySelectorAll('.modal-overlay');

    Array.prototype.forEach.call(overlays, function (overlay) {
      var dialog = overlay.querySelector('.modal');
      if (!dialog) return;

      // Dialog-Semantik nachrüsten, damit Screenreader den Kontext bekommen.
      dialog.setAttribute('role', 'dialog');
      dialog.setAttribute('aria-modal', 'true');

      var titel = dialog.querySelector('h2');
      if (titel) {
        if (!titel.id) titel.id = 'modal-titel-' + Math.random().toString(36).slice(2, 9);
        dialog.setAttribute('aria-labelledby', titel.id);
      }

      var text = dialog.querySelector('p');
      if (text) {
        if (!text.id) text.id = 'modal-text-' + Math.random().toString(36).slice(2, 9);
        dialog.setAttribute('aria-describedby', text.id);
      }

      overlay.setAttribute('aria-hidden', 'true');
      beobachte(overlay);
    });
  }

  // Das Script liegt am Body-Ende – DOMContentLoaded kann bereits durch sein.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
