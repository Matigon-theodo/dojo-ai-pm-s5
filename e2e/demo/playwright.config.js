// Config Playwright pour le test de prise en main (Étape 0).
// Lancer : npx playwright test --config=demo/playwright.config.js --ui
module.exports = {
  testDir: '.',
  use: { baseURL: 'http://localhost:8080', headless: true, trace: 'on', video: 'on', screenshot: 'on' },
  reporter: [['list'], ['html', { open: 'never' }]],
  timeout: 20000,
};
