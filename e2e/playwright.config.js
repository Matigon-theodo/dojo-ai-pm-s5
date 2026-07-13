// Config Playwright pour les tests E2E de Ketchup Compta.
module.exports = {
  testDir: './tests',
  use: {
    baseURL: 'http://localhost:8080',
    headless: true,
    trace: 'on',        // rejeu image par image de chaque action
    video: 'on',        // vidéo du run
    screenshot: 'on',   // capture d'écran en fin de test
  },
  reporter: [['list'], ['html', { open: 'never' }]],
  timeout: 20000,
};
