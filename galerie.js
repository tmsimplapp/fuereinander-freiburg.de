/**
 * Lightbox für die Bildergalerie: Klick, Pfeiltasten, Escape und Wischgesten.
 */
(function () {
  'use strict';

  function initGalerie() {
    const grid  = document.getElementById('galerie-grid');
    const box   = document.getElementById('lightbox');
    if (!grid || !box) return;
    // Nach einer View-Transition ist das DOM neu – dann erneut verdrahten.
    if (box.dataset.init === '1') return;
    box.dataset.init = '1';

    const bild    = document.getElementById('lightbox-bild');
    const titel   = document.getElementById('lightbox-titel');
    const zaehler = document.getElementById('lightbox-zaehler');
    const status  = document.getElementById('lightbox-status');
    const items   = Array.from(grid.querySelectorAll('.galerie-item'));

    let aktuell = 0;
    let ausloeser = null;

    function zeige(index) {
      aktuell = (index + items.length) % items.length;
      const el = items[aktuell];
      bild.src = el.dataset.voll;
      bild.alt = el.dataset.alt || '';
      titel.textContent = el.dataset.titel || '';
      zaehler.textContent = 'Bild ' + (aktuell + 1) + ' von ' + items.length;
      status.textContent = zaehler.textContent + (el.dataset.titel ? ': ' + el.dataset.titel : '');
      vorladen(aktuell + 1);
      vorladen(aktuell - 1);
    }

    function vorladen(index) {
      const el = items[(index + items.length) % items.length];
      if (el) new Image().src = el.dataset.voll;
    }

    function oeffne(index, quelle) {
      ausloeser = quelle || null;
      box.hidden = false;
      document.body.style.overflow = 'hidden';
      zeige(index);
      requestAnimationFrame(() => box.classList.add('is-open'));
      document.getElementById('lightbox-close').focus();
    }

    function schliesse() {
      box.classList.remove('is-open');
      box.hidden = true;
      document.body.style.overflow = '';
      bild.src = '';
      if (ausloeser) ausloeser.focus();
      ausloeser = null;
    }

    items.forEach((el, i) => {
      el.addEventListener('click', () => oeffne(i, el));
    });

    document.getElementById('lightbox-close').addEventListener('click', schliesse);
    document.getElementById('lightbox-prev').addEventListener('click', () => zeige(aktuell - 1));
    document.getElementById('lightbox-next').addEventListener('click', () => zeige(aktuell + 1));

    // Klick auf den Hintergrund schließt, Klick aufs Bild nicht
    box.addEventListener('click', (ev) => {
      if (ev.target === box) schliesse();
    });

    document.addEventListener('keydown', (ev) => {
      if (box.hidden) return;
      if (ev.key === 'Escape')     { ev.preventDefault(); schliesse(); }
      if (ev.key === 'ArrowLeft')  { ev.preventDefault(); zeige(aktuell - 1); }
      if (ev.key === 'ArrowRight') { ev.preventDefault(); zeige(aktuell + 1); }
      // Fokus innerhalb der Lightbox halten
      if (ev.key === 'Tab') {
        const fokussierbar = box.querySelectorAll('button');
        const erste = fokussierbar[0];
        const letzte = fokussierbar[fokussierbar.length - 1];
        if (ev.shiftKey && document.activeElement === erste) {
          ev.preventDefault(); letzte.focus();
        } else if (!ev.shiftKey && document.activeElement === letzte) {
          ev.preventDefault(); erste.focus();
        }
      }
    });

    // Wischgesten auf Touchgeräten
    let startX = 0;
    let startY = 0;
    box.addEventListener('touchstart', (ev) => {
      startX = ev.changedTouches[0].clientX;
      startY = ev.changedTouches[0].clientY;
    }, { passive: true });

    box.addEventListener('touchend', (ev) => {
      const dx = ev.changedTouches[0].clientX - startX;
      const dy = ev.changedTouches[0].clientY - startY;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
        zeige(dx < 0 ? aktuell + 1 : aktuell - 1);
      }
    }, { passive: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGalerie);
  } else {
    initGalerie();
  }

  // Nach View-Transitions erneut aufsetzen: transitions.js ruft nur
  // window.reinitializeScripts auf, deshalb die vorhandene Funktion umhüllen
  // statt sie zu ersetzen (main.js hängt seine init dort ein).
  const vorher = window.reinitializeScripts;
  window.reinitializeScripts = function () {
    if (typeof vorher === 'function') vorher();
    initGalerie();
  };
})();
