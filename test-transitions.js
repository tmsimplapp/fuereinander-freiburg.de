const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  // Öffne Startseite
  await page.goto('http://localhost:8000/index.html');
  await page.waitForLoadState('networkidle');

  console.log('✓ Seite geladen:', await page.title());

  // Prüfe ob transitions.js geladen wurde
  const hasScript = await page.evaluate(() => {
    const scripts = Array.from(document.scripts);
    return scripts.some(s => s.src.includes('transitions.js'));
  });
  console.log('✓ transitions.js geladen:', hasScript);

  // Prüfe View Transitions API Support
  const hasAPI = await page.evaluate(() => {
    return 'startViewTransition' in document;
  });
  console.log('✓ View Transitions API verfügbar:', hasAPI);

  // Click auf Partner-Link
  console.log('\n→ Klicke auf Partner-Link...');

  await page.click('a[href="partner.html"]');
  await page.waitForTimeout(1000);

  const currentURL = page.url();
  const currentTitle = await page.title();

  console.log('✓ Neue URL:', currentURL);
  console.log('✓ Neuer Titel:', currentTitle);

  // Screenshot
  await page.screenshot({ path: 'screenshot-partner.png' });
  console.log('✓ Screenshot gespeichert: screenshot-partner.png');

  await browser.close();
})();
