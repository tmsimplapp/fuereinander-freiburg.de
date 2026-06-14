// Zentrale JavaScript-Datei für füreinander-freiburg.de
// Alle Interaktionen und Animationen

(function() {
  'use strict';

  // ═══════════════════════════════════════
  // INITIALISIERUNG
  // ═══════════════════════════════════════

  function init() {
    updateYear();
    initMobileMenu();
    initScrollEffects();
    initRevealAnimations();
    initContactForm();
  }

  // ═══════════════════════════════════════
  // JAHR IM FOOTER
  // ═══════════════════════════════════════

  function updateYear() {
    const yearEl = document.getElementById('year');
    if (yearEl) {
      yearEl.textContent = new Date().getFullYear();
    }
  }

  // ═══════════════════════════════════════
  // MOBILE MENÜ
  // ═══════════════════════════════════════

  function initMobileMenu() {
    const fabButton = document.getElementById('nav-toggle-fab');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuStatus = document.getElementById('menu-status');

    if (!fabButton || !mobileMenu) return;
    if (fabButton.dataset.initialized === 'true') return;
    fabButton.dataset.initialized = 'true';

    function toggleMenu() {
      const isHidden = mobileMenu.classList.contains('hidden');

      mobileMenu.classList.toggle('hidden');
      fabButton.setAttribute('aria-expanded', String(isHidden));
      fabButton.setAttribute('aria-label', isHidden ? 'Menü schließen' : 'Menü öffnen');
      fabButton.classList.toggle('active');
      fabButton.classList.toggle('hidden-fab', isHidden);

      document.body.style.overflow = isHidden ? 'hidden' : '';

      if (menuStatus) {
        menuStatus.textContent = isHidden ? 'Menü geöffnet' : 'Menü geschlossen';
      }
    }

    fabButton.addEventListener('click', toggleMenu);

    fabButton.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleMenu();
      }
    });

    mobileMenu.addEventListener('click', (e) => {
      if (e.target === mobileMenu) {
        toggleMenu();
      }
    });

    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        if (!mobileMenu.classList.contains('hidden')) {
          toggleMenu();
        }
      });
    });

    initSwipeToClose(mobileMenu, toggleMenu);
  }

  function initSwipeToClose(mobileMenu, toggleMenu) {
    const sheet = mobileMenu?.querySelector('.mobile-menu-sheet');
    if (!sheet) return;

    let touchStartY = 0;
    let touchCurrentY = 0;
    let isDragging = false;

    sheet.addEventListener('touchstart', (e) => {
      touchStartY = e.touches[0].clientY;
      isDragging = true;
      sheet.style.transition = 'none';
    }, { passive: true });

    sheet.addEventListener('touchmove', (e) => {
      if (!isDragging || mobileMenu.classList.contains('hidden')) return;

      touchCurrentY = e.touches[0].clientY;
      const deltaY = touchCurrentY - touchStartY;

      if (deltaY > 0) {
        sheet.style.transform = `translateY(${deltaY}px)`;
      }
    }, { passive: true });

    sheet.addEventListener('touchend', () => {
      if (!isDragging) return;

      const deltaY = touchCurrentY - touchStartY;
      sheet.style.transition = '';

      if (deltaY > 100) {
        toggleMenu();
      } else {
        sheet.style.transform = '';
      }

      isDragging = false;
      touchStartY = 0;
      touchCurrentY = 0;
    });

    sheet.addEventListener('touchcancel', () => {
      sheet.style.transition = '';
      sheet.style.transform = '';
      isDragging = false;
    });
  }

  // ═══════════════════════════════════════
  // SCROLL-EFFEKTE (vereinheitlicht)
  // ═══════════════════════════════════════

  let scrollListenerBound = false;

  function initScrollEffects() {
    let ticking = false;
    const isMobile = () => window.innerWidth <= 900;

    function updateScrollEffects() {
      const scrollY = window.scrollY;
      const navbar = document.querySelector('nav');
      const heroImg = document.querySelector('.hero-full-img');
      const stickyBar = document.getElementById('sticky-phone-bar');

      // Header schrumpft
      if (navbar) {
        navbar.classList.toggle('scrolled', scrollY > 50);
      }

      // Parallax Hero-Bild (nur Desktop)
      if (heroImg && scrollY < window.innerHeight) {
        if (isMobile()) {
          heroImg.style.transform = 'translate(-50%, -50%)';
        } else {
          const parallaxOffset = scrollY * 0.3;
          heroImg.style.transform = `translateY(calc(-50% + ${parallaxOffset}px))`;
        }
      }

      // Sticky Phone Bar
      if (stickyBar) {
        stickyBar.classList.toggle('visible', scrollY > 200);
      }

      ticking = false;
    }

    function onScroll() {
      if (!ticking) {
        window.requestAnimationFrame(updateScrollEffects);
        ticking = true;
      }
    }

    // Listener nur einmal binden; updateScrollEffects greift nach jedem Body-Tausch auf aktuelle Elemente zu
    if (!scrollListenerBound) {
      window.addEventListener('scroll', onScroll, { passive: true });
      scrollListenerBound = true;
    }

    // Initialer Check (auch nach View-Transition, bezieht sich auf neue Elemente)
    updateScrollEffects();
  }

  // ═══════════════════════════════════════
  // REVEAL-ANIMATIONEN (Lazy-Init)
  // ═══════════════════════════════════════

  function initRevealAnimations() {
    // Warte bis Hero-Section sichtbar war (Performance-Boost)
    const heroSection = document.querySelector('.hero-full');
    if (!heroSection) {
      startRevealObservers();
      return;
    }

    const heroObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          setTimeout(startRevealObservers, 300);
          heroObserver.disconnect();
        }
      });
    }, { threshold: 0.1 });

    heroObserver.observe(heroSection);
  }

  function startRevealObservers() {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    document.querySelectorAll('.reveal, .reveal-slide-left, .reveal-slide-right, .reveal-scale')
      .forEach(el => revealObserver.observe(el));

    const ruleObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const delay = parseInt(entry.target.dataset.delay || 0);
          setTimeout(() => {
            entry.target.classList.add('visible');
          }, delay);
          ruleObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.rule-card').forEach(el => ruleObserver.observe(el));
  }

  // ═══════════════════════════════════════
  // KONTAKTFORMULAR
  // ═══════════════════════════════════════

  function initContactForm() {
    const contactForm = document.getElementById('contact-form');
    if (!contactForm) return;

    const contactMethod = document.getElementById('contact-method');
    const dynamicField = document.getElementById('dynamic-contact-field');
    const dynamicLabel = document.getElementById('dynamic-label');
    const contactValue = document.getElementById('contact-value');
    const formFeedback = document.getElementById('form-feedback');
    const submitBtn = document.getElementById('submit-btn');

    contactMethod.addEventListener('change', function() {
      const method = this.value;
      dynamicField.style.display = 'block';
      contactValue.required = true;

      if (method === 'email') {
        dynamicLabel.innerHTML = 'Deine E-Mail-Adresse <span style="color:#e05252;">*</span>';
        contactValue.type = 'email';
        contactValue.placeholder = 'name@beispiel.de';
      } else {
        dynamicLabel.innerHTML = 'Deine Telefonnummer <span style="color:#e05252;">*</span>';
        contactValue.type = 'tel';
        contactValue.placeholder = '+49 123 4567890';
      }
    });

    contactForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      submitBtn.disabled = true;
      submitBtn.textContent = 'Wird gesendet...';
      formFeedback.classList.add('hidden');

      try {
        const formData = new FormData(contactForm);
        const response = await fetch('mailer.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        formFeedback.classList.remove('hidden');
        if (result.status === 'success') {
          formFeedback.style.backgroundColor = '#d4f1e6';
          formFeedback.style.borderColor = '#a9e2cc';
          formFeedback.style.color = '#3d3225';
          formFeedback.innerHTML = `<strong>Vielen Dank!</strong> Deine Nachricht wurde gesendet.<br><br>Folgende Daten wurden übermittelt: Name, <strong>${contactMethod.options[contactMethod.selectedIndex].text}</strong>, Nachricht.<br>Diese Daten werden ausschließlich zur gewünschten Kontaktaufnahme gespeichert.`;
          contactForm.reset();
          dynamicField.style.display = 'none';
        } else {
          throw new Error(result.message || 'Ein Fehler ist aufgetreten.');
        }
      } catch (error) {
        formFeedback.classList.remove('hidden');
        formFeedback.style.backgroundColor = '#ffe5e5';
        formFeedback.style.borderColor = '#ffb3b3';
        formFeedback.style.color = '#cc0000';
        formFeedback.textContent = error.message;
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Nachricht senden';
      }
    });
  }

  // ═══════════════════════════════════════
  // SEITENZÄHLER
  // ═══════════════════════════════════════

  function initPageCounter() {
    const SESSION_KEY = 'fuereinander_counted';
    const alreadyCounted = sessionStorage.getItem(SESSION_KEY) === '1';
    const body = alreadyCounted ? '' : 'increment=1';

    fetch('counter.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
      .then(r => r.json())
      .then(() => {
        if (!alreadyCounted) {
          sessionStorage.setItem(SESSION_KEY, '1');
        }
      })
      .catch(() => {});
  }

  // ═══════════════════════════════════════
  // BFCACHE: Scroll-Sperre beim Zurücknavigieren aufheben
  // ═══════════════════════════════════════

  window.addEventListener('pageshow', () => {
    document.body.style.overflow = '';
  });

  // ═══════════════════════════════════════
  // START
  // ═══════════════════════════════════════

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Seitenzähler nur einmal pro echtem Seitenaufruf, NICHT bei View-Transitions
  initPageCounter();

  // Export für transitions.js
  window.reinitializeScripts = init;
})();
