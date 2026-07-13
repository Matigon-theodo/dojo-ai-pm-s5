// Config Playwright pour les tests du dossier enquete/.
// Lancer : npx playwright test --config=enquete/playwright.config.js
module.exports = {
  testDir: '.',
  use: { baseURL: 'http://localhost:8080', headless: true, trace: 'on', video: 'on', screenshot: 'on' },
  reporter: [['./reporter.js'], ['html', { open: 'never' }]],
  timeout: 20000,
};
