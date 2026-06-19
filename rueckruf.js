(function () {
  const WOCHENTAGE = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
  const MONATE_LANG = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
  const MONATE_KURZ = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];

  let gewaehltDatum   = null;
  let gewaehltUhrzeit = null;

  function datumLesbar(datum, uhrzeit) {
    const d  = new Date(datum + 'T00:00:00');
    const uh = uhrzeit.substring(0, 5);
    return WOCHENTAGE[d.getDay()] + ', ' + d.getDate() + '. ' + MONATE_KURZ[d.getMonth()] + ' ' + d.getFullYear() + ', ' + uh + ' Uhr';
  }

  function slotsGruppiertNachTag(slots) {
    const tage = {};
    slots.forEach(s => {
      if (!tage[s.datum]) tage[s.datum] = [];
      tage[s.datum].push(s);
    });
    return tage;
  }

  function renderSlots(slots) {
    const loading = document.getElementById('rb-slots-loading');
    const grid    = document.getElementById('rb-slots-grid');
    const empty   = document.getElementById('rb-slots-empty');

    loading.classList.add('hidden');

    const freie = slots.filter(s => !s.belegt);
    if (freie.length === 0) {
      empty.classList.remove('hidden');
      return;
    }

    const tage = slotsGruppiertNachTag(slots);
    grid.innerHTML = '';
    grid.classList.remove('hidden');
    grid.style.cssText = 'display:flex; flex-direction:column; gap:8px;';

    Object.entries(tage).forEach(([datum, tagesSlots]) => {
      const d         = new Date(datum + 'T00:00:00');
      const wochentag = WOCHENTAGE[d.getDay()];
      const tag       = d.getDate();
      const mon       = MONATE_KURZ[d.getMonth()];
      const hatFreie  = tagesSlots.some(s => !s.belegt);
      const anzahlFrei = tagesSlots.filter(s => !s.belegt).length;

      // Accordion-Zeile
      const zeile = document.createElement('div');
      zeile.style.cssText = 'border-radius:14px; overflow:hidden; border:1px solid #E2C2A2; background:#fff;';

      // Toggle-Button (gesamte Kopfzeile)
      const toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.style.cssText = [
        'width:100%',
        'display:flex',
        'align-items:center',
        'justify-content:space-between',
        'padding:14px 16px',
        'background:transparent',
        'border:none',
        'cursor:pointer',
        'font-family:inherit',
        'min-height:56px',
        'text-align:left',
      ].join(';');

      const datumLabel = document.createElement('span');
      datumLabel.style.cssText = 'font-size:15px; font-weight:600; color:#3d3225;';
      datumLabel.textContent = wochentag + ', ' + tag + '. ' + mon;

      const rechts = document.createElement('span');
      rechts.style.cssText = 'display:flex; align-items:center; gap:10px; flex-shrink:0;';

      const badge = document.createElement('span');
      badge.style.cssText = hatFreie
        ? 'font-size:12px; font-weight:600; color:#1a6645; background:#d4f1e6; border-radius:20px; padding:3px 10px;'
        : 'font-size:12px; font-weight:600; color:#b8a88a; background:#f0ebe0; border-radius:20px; padding:3px 10px;';
      badge.textContent = hatFreie ? anzahlFrei + (anzahlFrei === 1 ? ' Termin frei' : ' Termine frei') : 'ausgebucht';

      const pfeil = document.createElement('span');
      pfeil.style.cssText = 'font-size:12px; color:#6f6047; transition:transform 0.2s ease; display:inline-block;';
      pfeil.textContent = '▶';

      rechts.appendChild(badge);
      rechts.appendChild(pfeil);
      toggle.appendChild(datumLabel);
      toggle.appendChild(rechts);

      // Uhrzeit-Panel (eingeklappt)
      const panel = document.createElement('div');
      panel.style.cssText = 'display:none; padding:0 16px 14px; display:none; flex-direction:column; gap:8px;';
      panel.setAttribute('aria-hidden', 'true');

      tagesSlots.forEach(slot => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.textContent = slot.uhrzeit.substring(0, 5) + ' Uhr';
        chip.dataset.datum   = slot.datum;
        chip.dataset.uhrzeit = slot.uhrzeit;
        chip.style.cssText = [
          'width:100%',
          'padding:12px 16px',
          'border-radius:10px',
          'font-family:inherit',
          'font-size:15px',
          'font-weight:500',
          'line-height:1',
          'transition:all 0.15s ease',
          'min-height:48px',
          'text-align:left',
          slot.belegt
            ? 'background:#f0ebe0; color:#b8a88a; border:1px solid #E2C2A2; cursor:not-allowed; text-decoration:line-through;'
            : 'background:#d4f1e6; color:#1a2820; border:1px solid #a9e2cc; cursor:pointer;'
        ].join(';');

        if (slot.belegt) {
          chip.disabled = true;
          chip.title = 'Bereits gebucht';
        } else {
          chip.addEventListener('click', () => slotWaehlen(slot.datum, slot.uhrzeit, chip));
        }
        panel.appendChild(chip);
      });

      // Accordion-Toggle
      let offen = false;
      toggle.addEventListener('click', () => {
        if (!hatFreie) return;
        offen = !offen;
        panel.style.display = offen ? 'flex' : 'none';
        pfeil.style.transform = offen ? 'rotate(90deg)' : 'rotate(0deg)';
        zeile.style.borderColor = offen ? '#a9e2cc' : '#E2C2A2';
      });

      zeile.appendChild(toggle);
      zeile.appendChild(panel);
      grid.appendChild(zeile);
    });
  }

  function slotWaehlen(datum, uhrzeit, chip) {
    // Vorherige Auswahl zurücksetzen
    document.querySelectorAll('#rb-slots-grid button.slot-aktiv').forEach(b => {
      b.classList.remove('slot-aktiv');
      b.style.cssText = [
        'width:100%', 'padding:10px 8px', 'border-radius:10px',
        'font-family:inherit', 'font-size:14px', 'font-weight:500',
        'line-height:1', 'transition:all 0.15s ease', 'min-height:44px',
        'background:#d4f1e6; color:#1a2820; border:1px solid #a9e2cc; cursor:pointer;'
      ].join(';');
    });

    chip.classList.add('slot-aktiv');
    chip.style.cssText = [
      'width:100%', 'padding:10px 8px', 'border-radius:10px',
      'font-family:inherit', 'font-size:14px', 'font-weight:700',
      'line-height:1', 'transition:all 0.15s ease', 'min-height:44px',
      'background:#a9e2cc; color:#1a2820; border:2px solid #5fa88a; cursor:pointer;',
      'box-shadow:0 2px 8px rgba(95,168,138,0.25);'
    ].join(';');

    // Elternkarte hervorheben
    const karte = chip.closest('div[style*="border-radius:16px"]');
    if (karte) karte.style.borderColor = '#a9e2cc';

    gewaehltDatum   = datum;
    gewaehltUhrzeit = uhrzeit;

    document.getElementById('rb-selected-label').textContent = datumLesbar(datum, uhrzeit);
    document.getElementById('rb-step2').classList.remove('hidden');
    document.getElementById('rb-step2').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function init() {
    const container = document.getElementById('rueckruf');
    if (!container) return;

    fetch('buchung.php?action=slots')
      .then(r => r.json())
      .then(data => {
        if (data.status === 'ok') renderSlots(data.slots);
        else document.getElementById('rb-slots-loading').textContent = 'Termine konnten nicht geladen werden.';
      })
      .catch(() => {
        document.getElementById('rb-slots-loading').textContent = 'Termine konnten nicht geladen werden.';
      });

    document.getElementById('rb-change').addEventListener('click', () => {
      document.getElementById('rb-step2').classList.add('hidden');
      // Kartenrahmen zurücksetzen
      document.querySelectorAll('#rb-slots-grid button.slot-aktiv').forEach(b => {
        const karte = b.closest('div[style*="border-radius:16px"]');
        if (karte) karte.style.borderColor = '#E2C2A2';
      });
      gewaehltDatum = gewaehltUhrzeit = null;
    });

    document.getElementById('rb-submit').addEventListener('click', () => {
      const name     = document.getElementById('rb-name').value.trim();
      const telefon  = document.getElementById('rb-telefon').value.trim();
      const email    = document.getElementById('rb-email').value.trim();
      const feedback = document.getElementById('rb-feedback');

      if (!gewaehltDatum || !gewaehltUhrzeit || !name || !telefon || !email) {
        showFeedback(feedback, 'error', 'Bitte alle Felder ausfüllen.');
        return;
      }

      const btn = document.getElementById('rb-submit');
      btn.disabled = true;
      btn.textContent = 'Wird gebucht …';

      fetch('buchung.php?action=buchen', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ datum: gewaehltDatum, uhrzeit: gewaehltUhrzeit, name, telefon, email })
      })
        .then(r => r.json())
        .then(data => {
          if (data.status === 'ok') {
            showFeedback(feedback, 'success', data.message);
            document.getElementById('rb-step2').querySelectorAll('input').forEach(i => i.value = '');
            // Gebuchten Chip sperren
            document.querySelectorAll('#rb-slots-grid button').forEach(b => {
              if (b.dataset.datum === gewaehltDatum && b.dataset.uhrzeit === gewaehltUhrzeit) {
                b.disabled = true;
                b.classList.remove('slot-aktiv');
                b.style.cssText = [
                  'width:100%', 'padding:10px 8px', 'border-radius:10px',
                  'font-family:inherit', 'font-size:14px', 'font-weight:500',
                  'line-height:1', 'min-height:44px',
                  'background:#f0ebe0; color:#b8a88a; border:1px solid #E2C2A2; cursor:not-allowed; text-decoration:line-through;'
                ].join(';');
                const karte = b.closest('div[style*="border-radius:16px"]');
                if (karte) karte.style.borderColor = '#E2C2A2';
              }
            });
            gewaehltDatum = gewaehltUhrzeit = null;
            setTimeout(() => document.getElementById('rb-step2').classList.add('hidden'), 4000);
          } else {
            showFeedback(feedback, 'error', data.message);
            btn.disabled = false;
            btn.textContent = 'Termin verbindlich anfragen';
          }
        })
        .catch(() => {
          showFeedback(feedback, 'error', 'Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
          btn.disabled = false;
          btn.textContent = 'Termin verbindlich anfragen';
        });
    });
  }

  function showFeedback(el, type, msg) {
    el.textContent = msg;
    el.classList.remove('hidden');
    el.style.cssText = type === 'success'
      ? 'background:#d4f1e6; color:#1a2820; border-color:#a9e2cc; border:1px solid; border-radius:12px; padding:16px; font-size:14px;'
      : 'background:#fde8e8; color:#7f1d1d; border-color:#fca5a5; border:1px solid; border-radius:12px; padding:16px; font-size:14px;';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
