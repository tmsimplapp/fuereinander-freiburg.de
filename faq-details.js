// FAQ als <details> für native Accessibility
// Progressive Enhancement: Konvertiert Button-FAQ zu nativen <details>

(function() {
  'use strict';

  function convertFAQ() {
    const faqContainer = document.querySelector('#faq dl');
    if (!faqContainer) return;

    // Bereits konvertiert?
    if (faqContainer.querySelector('details')) return;

    const faqs = [];
    faqContainer.querySelectorAll('div').forEach(faqItem => {
      const btn = faqItem.querySelector('.faq-btn');
      const answer = faqItem.querySelector('.faq-answer');

      if (btn && answer) {
        const question = btn.querySelector('span').textContent;
        const answerHTML = answer.innerHTML;
        faqs.push({ question, answerHTML });
      }
    });

    // Neu aufbauen mit <details>
    if (faqs.length > 0) {
      faqContainer.innerHTML = faqs.map(faq => `
        <details class="rounded-2xl overflow-hidden mb-4" style="border: 1px solid #E2C2A2;">
          <summary class="w-full flex items-center justify-between gap-4 px-7 py-5 cursor-pointer list-none"
                   style="background:#ffda69;">
            <span class="font-display text-base font-semibold" style="color:#3d3225;">${faq.question}</span>
            <svg class="details-icon w-5 h-5 flex-shrink-0 transition-transform" fill="none" stroke="#6f6047" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </summary>
          <div class="px-7 py-5" style="background:#FEFAE0;">
            ${faq.answerHTML}
          </div>
        </details>
      `).join('');
    }

    // Animiere Icon bei open/close
    document.querySelectorAll('#faq details').forEach(details => {
      details.addEventListener('toggle', () => {
        const icon = details.querySelector('.details-icon');
        if (icon) {
          icon.style.transform = details.open ? 'rotate(180deg)' : '';
        }
      });
    });
  }

  // Start
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', convertFAQ);
  } else {
    convertFAQ();
  }

  // Export für transitions.js (Re-Init nach View-Transition)
  window.convertFAQ = convertFAQ;
})();