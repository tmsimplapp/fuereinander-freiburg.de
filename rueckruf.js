(function () {
  const WOCHENTAGE = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
  const MONATE     = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];

  let gewaehltDatum   = null;
  let gewaehltUhrzeit = null;

  function datumLesbar(datum, uhrzeit) {
    const d  = new Date(datum + 'T00:00:00');
    const uh = uhrzeit.substring(0, 5);
    return WOCHENTAGE[d.getDay()] + ', ' + d.getDate() + '. ' + MONATE[d.getMonth()] + ' ' + d.getFullYear() + ', ' + uh + ' Uhr';
  }

  function slotsGruppiertNachDatum(slots) {
    const gruppen = {};
    slots.forEach(s => {
      if (!gruppen[s.datum]) gruppen[s.datum] = [];
      gruppen[s.datum].push(s);
    });
    return gruppen;
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

    const gruppen = slotsGruppiertNachDatum(slots);
    grid.innerHTML = '';
    grid.classList.remove('hidden');

    Object.entries(gruppen).forEach(([datum, tagesSlots]) => {
      const d   = new Date(datum + 'T00:00:00');
      const tag = WOCHENTAGE[d.getDay()] + ', ' + d.getDate() + '. ' + MONATE[d.getMonth()];

      const wrap = document.createElement('div');
      wrap.className = 'mb-4';
      wrap.innerHTML = '<p class="font-body text-sm font-semibold mb-2" style="color:#5c4e3a;">' + tag + '</p>';

      const row = document.createElement('div');
      row.className = 'flex flex-wrap gap-2';

      tagesSlots.forEach(slot => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = slot.uhrzeit.substring(0, 5) + ' Uhr';
        btn.className = 'font-body text-sm px-5 py-2.5 rounded-full transition-all';
        btn.dataset.datum   = slot.datum;
        btn.dataset.uhrzeit = slot.uhrzeit;

        if (slot.belegt) {
          btn.disabled = true;
          btn.style.cssText = 'background:#f0ebe0; color:#b8a88a; border:1px solid #E2C2A2; cursor:not-allowed;';
          btn.title = 'Bereits gebucht';
        } else {
          btn.style.cssText = 'background:#d4f1e6; color:#1a2820; border:1px solid #a9e2cc; cursor:pointer;';
          btn.addEventListener('click', () => slotWaehlen(slot.datum, slot.uhrzeit, btn));
        }
        row.appendChild(btn);
      });

      wrap.appendChild(row);
      grid.appendChild(wrap);
    });
  }

  function slotWaehlen(datum, uhrzeit, btn) {
    // Vorherige Auswahl zurücksetzen
    document.querySelectorAll('#rb-slots-grid button.slot-aktiv').forEach(b => {
      b.classList.remove('slot-aktiv');
      b.style.cssText = 'background:#d4f1e6; color:#1a2820; border:1px solid #a9e2cc; cursor:pointer;';
    });

    btn.classList.add('slot-aktiv');
    btn.style.cssText = 'background:#a9e2cc; color:#1a2820; border:1px solid #8ed4b8; cursor:pointer; font-weight:600;';

    gewaehltDatum   = datum;
    gewaehltUhrzeit = uhrzeit;

    document.getElementById('rb-selected-label').textContent = datumLesbar(datum, uhrzeit);
    document.getElementById('rb-step2').classList.remove('hidden');
    document.getElementById('rb-step2').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function init() {
    const container = document.getElementById('rueckruf');
    if (!container) return;

    // Slots laden
    fetch('buchung.php?action=slots')
      .then(r => r.json())
      .then(data => {
        if (data.status === 'ok') renderSlots(data.slots);
        else {
          document.getElementById('rb-slots-loading').textContent = 'Termine konnten nicht geladen werden.';
        }
      })
      .catch(() => {
        document.getElementById('rb-slots-loading').textContent = 'Termine konnten nicht geladen werden.';
      });

    // Slot ändern
    document.getElementById('rb-change').addEventListener('click', () => {
      document.getElementById('rb-step2').classList.add('hidden');
      gewaehltDatum = gewaehltUhrzeit = null;
    });

    // Buchung absenden
    document.getElementById('rb-submit').addEventListener('click', () => {
      const name    = document.getElementById('rb-name').value.trim();
      const telefon = document.getElementById('rb-telefon').value.trim();
      const email   = document.getElementById('rb-email').value.trim();
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
            // Gebuchten Slot visuell sperren
            document.querySelectorAll('#rb-slots-grid button').forEach(b => {
              if (b.dataset.datum === gewaehltDatum && b.dataset.uhrzeit === gewaehltUhrzeit) {
                b.disabled = true;
                b.classList.remove('slot-aktiv');
                b.style.cssText = 'background:#f0ebe0; color:#b8a88a; border:1px solid #E2C2A2; cursor:not-allowed;';
              }
            });
            gewaehltDatum = gewaehltUhrzeit = null;
            setTimeout(() => document.getElementById('rb-step2').classList.add('hidden'), 3000);
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
    if (type === 'success') {
      el.style.cssText = 'background:#d4f1e6; color:#1a2820; border-color:#a9e2cc;';
    } else {
      el.style.cssText = 'background:#fde8e8; color:#7f1d1d; border-color:#fca5a5;';
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
