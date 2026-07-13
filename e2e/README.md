# Kit Séance 5 — tests E2E (mode cobaye)

Tu joues la séance comme un participant. La fiche pas-à-pas est dans Notion.
Le dossier `tests/` est **volontairement vide** : c'est toi, via Claude, qui vas y faire écrire les tests.
Le dossier `enquete/` contient **un** test déjà écrit, pour l'Étape 3.

## Setup (une seule fois)

1. **L'app** — à la racine `dojo-ai-pm-s5` :
   ```bash
   docker compose up -d          # http://localhost:8080  (admin / admin123)
   ```
   ⚠️ Le port 8080 ne peut servir qu'à une app à la fois. Si tu as déjà l'autre kit qui tourne,
   coupe-le avant : `docker compose down` dans le dossier de l'autre app.

2. **Playwright** — ici, dans `e2e/` :
   ```bash
   npm install
   npx playwright install chromium
   ```

## Lancer les tests (une fois que Claude les a écrits)

```bash
npx playwright test          # lance tout (invisible : juste vert / rouge)
```

## Voir ce que fait le test (le point d'accroche PM)

⚠️ Ces commandes n'ont d'intérêt **qu'une fois qu'un test existe**. Tant que `tests/` est vide,
tu obtiendras « No tests found » — c'est normal, commence par faire écrire un test (Étape 1 ou 2).

Par défaut le test tourne sans rien afficher. Pour le **regarder agir** :

```bash
npx playwright test --ui       # mode visuel : tu vois le navigateur cliquer, étape par étape
npx playwright test --headed   # ouvre le vrai navigateur pendant le run
npx playwright show-report     # APRÈS le run : rejoue chaque action EN IMAGES (trace) + vidéo
```

- **`--ui`** : le plus parlant. Un panneau montre les tests ; tu cliques, tu regardes, tu rembobines.
- **`show-report`** : ouvre le rapport, clique un test → sa **trace** (captures avant / après chaque clic) et sa **vidéo**.

Pour le test de l'**Étape 3** (dossier `enquete/`), garde le `--config` et ajoute `--ui` :

```bash
npx playwright test --ui --config=enquete/playwright.config.js
```

## Reset de la base (entre deux essais)

À la racine `dojo-ai-pm-s5` :
```bash
rm -f www/data/compta.db && docker compose restart
```
(`docker compose down -v` ne suffit PAS : la base est un fichier sur le disque.)

## Ce que tu vas faire

Les étapes sont **dans la fiche Notion** — c'est elle qui guide. En résumé :

- Étapes 1 et 2 : tu fais écrire des tests par Claude dans `tests/`, puis tu les lances.
- Étape 3 : un test t'attend déjà dans `enquete/` ; lance-le avec
  ```bash
  npx playwright test --config=enquete/playwright.config.js
  ```
