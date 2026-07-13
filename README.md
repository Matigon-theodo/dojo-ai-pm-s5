# Dojo AI PM — Séance 5 : Tester avec l'IA (E2E Playwright)

Le **dojo** est un parcours de formation pour les **Product Managers** : apprendre à utiliser l'IA
dans les gestes du métier. Il s'appuie sur **Ketchup Compta** (l'appli legacy du keiko).

Cette séance — **« Tester avec l'IA »** : se servir de tests end-to-end (Playwright) pour **sécuriser
une migration** et **valider une feature**, sans écrire de code — en pilotant Claude et en lançant les
tests soi-même.

## Contenu

- `e2e/` — le kit de test : `package.json`, `playwright.config.js`, un dossier `tests/` **vide**
  (c'est toi, via Claude, qui le remplis), et `enquete/` (un test déjà écrit, pour l'étape 3).
- `e2e/README.md` — installer et lancer Playwright (les commandes que TU lances).
- `www/modules/entries/edit.php` — la page de saisie d'écriture (l'originale).
- `www/modules/entries/edit_cible.php` — sa réécriture (support de l'exercice de migration).
- `docs/lettrage-spec.md` — la spec du lettrage (rédigée en Séance 3), point de départ de l'étape 2.
- `fiche-trainee.md` — le pas-à-pas de la séance (les 3 étapes détaillées).
- Le reste = l'appli Ketchup Compta. Détails techniques dans `CLAUDE.md`.

## Prérequis

- Ketchup Compta tourne en local : `docker compose up -d` → http://localhost:8080 (`admin` / `admin123`).
- Node installé (pour Playwright).

## Démarrer

Récupère le kit (une seule commande, dépôt public) :

```bash
git clone https://github.com/Matigon-theodo/dojo-ai-pm-s5.git
cd dojo-ai-pm-s5
```

(Alternative : `gh repo clone Matigon-theodo/dojo-ai-pm-s5`.)

La séance est pilotée par la **fiche trainee** (`fiche-trainee.md`) : ouvre-la et suis les 3 étapes.

Setup, une seule fois :

```bash
docker compose up -d
cd e2e && npm install && npx playwright install chromium
```

Les 3 temps (détaillés dans la fiche) :

1. **Migration** — vérifier que la page réécrite se comporte comme l'originale.
2. **Feature neuve** — partir de `docs/lettrage-spec.md` et prouver que le lettrage respecte sa spec.
3. **Enquête** — lancer le test de `e2e/enquete/` et trancher : vrai bug, ou faux KO ?

Rappel : **c'est toi qui lances les commandes Playwright** dans ton terminal ; Claude écrit les tests
et répond à tes questions.
