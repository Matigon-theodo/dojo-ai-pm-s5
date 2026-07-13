// Test E2E de la saisie d'écriture.
const { test, expect } = require('@playwright/test');

async function login(page) {
  await page.goto('/login.php');
  await page.fill('#username', 'admin');
  await page.fill('#password', 'admin123');
  await page.click('button[type="submit"]');
  await expect(page).not.toHaveURL(/login\.php/);
}

test('une écriture équilibrée est bien enregistrée', async ({ page }) => {
  await login(page);
  await page.goto('/modules/entries/edit.php');
  await page.selectOption('#journal_id', { value: '3' });          // Journal de Banque
  await page.fill('#label', 'Apport en banque');
  await page.click('.btn-add-line');
  let row = page.locator('#entry-lines-body tr').last();
  await row.locator('.line-account').selectOption({ index: 6 });   // 512000 Banque
  await row.locator('.line-debit').fill('100.00');
  await page.click('.btn-add-line');
  row = page.locator('#entry-lines-body tr').last();
  await row.locator('.line-account').selectOption({ index: 1 });   // 101000 Capital
  await row.locator('.line-credit').fill('100.00');
  await page.click('button[type="submit"]');

  // On vérifie que l'app confirme l'enregistrement.
  // Le 2e argument de expect() est le message lisible affiché si le test échoue.
  const message = (await page.locator('.flash-success').innerText()).trim();
  expect(
    message,
    `Le test attendait le message « Opération validée » — l'app a affiché « ${message} ».`
  ).toContain('Opération validée');
});
