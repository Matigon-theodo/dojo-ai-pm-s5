const { test, expect } = require('@playwright/test');

// Test de prise en main (Étape 0). Déjà écrit pour toi : tu n'as qu'à le LANCER.
// Un test = une suite d'actions qui se termine par UNE vérification.
test('je peux me connecter', async ({ page }) => {
  await page.goto('/login.php');                        // va sur la page de connexion
  await page.fill('#username', 'admin');                 // tape l'identifiant
  await page.fill('#password', 'admin123');              // tape le mot de passe
  await page.click('button:has-text("Se connecter")');   // clique sur le bouton
  await expect(page.locator('#nav')).toContainText('Tableau de bord'); // LA vérification
});
