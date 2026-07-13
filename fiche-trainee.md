# Séance 5 — Tester avec Claude (E2E)

> 5️⃣ Séance 5 · Tests E2E avec Claude — brouillon v1

## Bienvenue

Aujourd'hui tu vas apprendre à **écrire et lancer des tests avec Claude** pour vérifier qu'une fonctionnalité se comporte comme prévu, sans rentrer trop dans la technique. Un test E2E, c'est un robot qui **clique dans l'app comme un vrai utilisateur** et vérifie le résultat. Tu travailles en binôme, chacun sur sa machine.

**Ce que tu sauras faire à la fin** : demander un test E2E à Claude, le lancer, **lire le rapport**, et distinguer un **vrai bug** d'un **test mal réalisé**.

## Ton objectif du jour

Utiliser les tests E2E pour deux moments très concrets de ta vie de PM :

- **Sécuriser une migration** : quand une feature est réécrite, vérifier que la nouvelle version se comporte **comme l'ancienne**.
- **Valider une feature neuve** : partir de ta spec et prouver que ce qui a été codé respecte la règle.

**Le contexte** : l'outil s'appelle **Playwright**. Tu n'écris pas une ligne de code : Claude écrit le test à partir de 1) le code de la feature legacy, 2) la spec écrite de la nouvelle feature. Puis il le lance et te rend un rapport que tu sais lire.

> 🤖 Tu décris en français ce que tu veux tester, **Claude écrit le test**. **C'est toi qui le lances** dans ton terminal et qui lis le résultat (vert / rouge), puis tu reviens vers Claude avec tes questions. Tu gardes la main sur les runs — Claude ne pilote pas ton terminal.

> 📂 **Au départ, le dossier `e2e/tests/` est VIDE.** C'est normal : c'est Claude qui va le remplir aux Étapes 1 et 2. Tant qu'aucun test n'existe, si tu ouvres le rapport tu verras « No tests found ». Ce n'est pas un bug, il n'y a juste encore rien à montrer. L'ordre : **1) faire écrire un test, 2) le lancer, 3) regarder le résultat.**

> 👀 **Pour voir ce qui se passe (une fois qu'un test existe).** Un test tourne sans rien afficher par défaut (juste vert / rouge).
>
> - Pour le **regarder agir** : `npx playwright test --ui` → le navigateur clique sous tes yeux, étape par étape.
> - Après coup : `npx playwright show-report` → **rejeu image par image** de chaque clic + une **vidéo**. C'est ton point d'accroche visuel.

---

## Étape 0 - Setup + prise en main (~15 min)

### 0.a Prépare ton setup (~5 min)

Va dans le dossier **`legacy-trainer-session5`** = *le kit de la séance*. C'est ici que tu travailles.

- Lance Claude Code depuis le dossier `legacy-trainer-session5`.
- 🖥️ Lance l'app avec Docker : `docker compose up -d` puis <http://localhost:8080> (`admin` / `admin123`).
- Prépare Playwright une seule fois : dans le dossier `e2e/`, `npm install` puis `npx playwright install chromium`.

### 0.b Ton premier test : un « hello world » (~10 min)

Avant de comparer quoi que ce soit, prenons 10 min pour voir **un** test tourner de bout en bout. Le plus simple qui soit : **se connecter à l'app**. Une action, une vérification, aucune donnée à préparer.

**Bonne nouvelle : ce test est déjà écrit pour toi.** Tu n'as rien à coder ni à demander à Claude : il est fourni dans le kit, tu n'as qu'à le **lancer**. Il est ici :

```
e2e/demo/login.spec.js
```

Ouvre-le : c'est court, et chaque ligne est un geste que tu ferais toi-même à la main. Traduit en français :

```js
test('je peux me connecter', async ({ page }) => {
  await page.goto('/login.php');                        // va sur la page de connexion
  await page.fill('#username', 'admin');                 // tape l'identifiant
  await page.fill('#password', 'admin123');              // tape le mot de passe
  await page.click('button:has-text("Se connecter")');   // clique sur le bouton
  await expect(page.locator('#nav')).toContainText('Tableau de bord'); // LA vérification
});
```

Ça se lit comme une recette : *va sur la page de connexion → tape l'identifiant → tape le mot de passe → clique → **vérifie** qu'on est bien arrivé sur le tableau de bord.* La dernière ligne (`expect`) est la seule qui *juge* : c'est elle qui décide vert ou rouge.

**Maintenant, à toi de le lancer dans ton terminal.** Étape par étape :

1. Place-toi dans le dossier `e2e/` (depuis `legacy-trainer-session5`) :
   ```bash
   cd e2e
   ```
2. Vérifie que l'app tourne : ouvre <http://localhost:8080>. Si ça s'affiche, c'est bon. (Sinon : `docker compose up -d` depuis `legacy-trainer-session5`.)
3. Lance le test **en mode visible** :
   ```bash
   npx playwright test --config=demo/playwright.config.js --ui
   ```
4. ⚠️ **Le test ne démarre pas tout seul.** Une fenêtre Playwright s'ouvre avec la liste des tests à gauche :
   - **Clique sur le ▶ (play)** à côté de « je peux me connecter » (ou le ▶ tout en haut).
   - **Regarde le panneau de droite** : le navigateur remplit le formulaire et bascule sur le tableau de bord, étape par étape. Tu peux cliquer sur chaque action de la liste pour revoir l'état de la page à ce moment-là.
   - Le test passe **vert** ✓.
5. **Ferme la fenêtre** pour rendre la main à ton terminal.

> 💡 Si tu lances la même commande **sans** `--ui`, le test tourne quand même… mais **en invisible** (juste vert/rouge dans le terminal). C'est normal, c'est le réglage par défaut. Pour *voir* le navigateur agir, c'est bien `--ui` qu'il faut.

Tu viens de lire et lancer ton premier test E2E — le reste de la séance, c'est le même geste, sur des cas plus intéressants (et là, c'est Claude qui écrira les tests).

<details><summary>🔤 Les 3 mots à retenir (ça te servira toute la séance)</summary>

- **`#truc` / `.machin`** = l'« adresse » d'un élément sur la page (son identifiant `#`, ou sa catégorie `.`). C'est ainsi que le robot retrouve un champ ou un bouton.
- **`expect(…)`** = **l'assertion** : le seul moment où le test *juge*. Avant, il ne fait qu'agir (aller, remplir, cliquer) ; c'est à `expect` que ça devient vert ou rouge.
- **`await`** = « attends que ce soit fini avant de continuer » (une page met un instant à répondre).

👉 Retiens surtout : un test = **une suite d'actions** qui se termine par **une vérification**. C'est la dernière ligne (`expect`) qui décide rouge / vert — d'où l'intérêt de bien regarder CE qu'elle vérifie.
</details>

---

## Étape 1 - Sécuriser une migration (~20 min)

L'équipe a réécrit la page de **saisie d'écriture** (migration technique). Avant de basculer en prod, on te demande côté PM / QA, de **vérifier que la nouvelle version se comporte exactement comme l'ancienne**.

> 💬 On a migré `edit.php` vers `edit_cible.php`. Tu peux vérifier que le comportement n'a pas bougé avant qu'on bascule ? Si quelque chose diffère, il faut le voir **maintenant**, pas en prod.

La page existe donc en deux versions : l'**originale** (`edit.php`) = la **référence**, et la **réécriture** (`edit_cible.php`) à valider.

> 🔀 **Pas de spec ici : l'original fait référence.** On capture ce que fait l'ancienne page dans des tests, puis on rejoue les mêmes sur la nouvelle. Tout ce qui diffère = un risque de migration.

**Le but** : repérer **où** la version réécrite s'écarte de l'originale.

> ✍️ **À toi d'abord (2 min).** Avant de lancer Claude, écris **toi-même** ce que « même comportement » veut dire à la consultation d'une écriture. Liste 3-4 points observables, par exemple :
> - le montant saisi au **débit** s'affiche bien dans la colonne **Débit** (et le crédit dans la colonne Crédit) ;
> - Total débit = Total crédit ;
> - le n° de pièce et le libellé s'affichent.
>
> Ces points **sont** tes vérifications. C'est toi qui décides ce qui compte, pas Claude.

Ensuite, donne **ta liste** à Claude et demande-lui d'écrire des tests Playwright qui **vérifient ces points**. Puis fais-les lancer : d'abord sur l'original (ils doivent être **verts** = référence), puis exactement les mêmes sur la cible.

Tu peux lui donner les fichiers de code correspondants :

- legacy : `www/modules/entries/edit.php`
- cible : `www/modules/entries/edit_cible.php`

**Tu as fini quand** tu peux nommer la divergence — et tu verras qu'elle tombe sur **un des points que tu as toi-même écrits**. Pour la voir, utilise le **rapport de divergences** de Claude, à revoir en images (`npx playwright show-report` dans ton terminal). Tu peux aussi lui demander de générer un .html, plus lisible.

<details><summary>💯 Solution (à n'ouvrir que quand tu penses avoir trouvé)</summary>

A l'enregistrement tout est identique (mêmes règles respectées), mais **à la consultation d'une écriture, la réécriture inverse les colonnes Débit et Crédit** → les données sont bonnes, l'affichage est faux.

→ Le genre de bug que même un PM très rigoureux peut rater à l'œil nu, mais le test catche !
</details>

<details><summary>💡 Tips : bien comparer</summary>

- **Liste tes vérifs à la main d'abord**, puis fais-les coder : tu gardes la main sur ce qui compte.
- Fais d'abord **passer les tests sur l'original** : verts, ils décrivent bien la référence.
- Demande **le même test** des deux côtés, sinon la comparaison ne vaut rien.
- Une divergence n'est pas toujours un bug : demande-toi si c'est **voulu** ou non (ici, non).
- Si Claude ne trouve pas un bouton, indique-lui son vrai nom (voir Étape 3).
</details>

---

## Étape 2 - Valider une feature neuve depuis sa spec (~20 min)

On repart de **ta** spec de lettrage (la feature de la Séance 3), `docs/lettrage-spec.md` — désormais au **format de l'usine**. Elle contient tout : le pourquoi, les cas d'usage, les règles… **sauf une chose : la section « Scénarios de validation »**. C'est **toi** qui l'écris. Le PM décide le *quoi* (les scénarios), Claude fait le *comment* (le test).

> 📋 **Concentre-toi sur UC1 — « lettrer une facture avec son règlement »** (dans `docs/lettrage-spec.md`). Sa règle clé : le bouton **« Lettrer »** ne s'active que lorsque l'**Écart = 0**. Ignore UC2/UC3 pour aujourd'hui.

> 👀 **Va voir avant d'écrire.** Ouvre le lettrage : <http://localhost:8080/modules/lettrage/index.php>, compte **411000 - Clients**, onglet **Non lettré**. Coche et décoche des lignes, regarde le compteur et le bouton. **Quand le bouton s'active-t-il ?** Et quel cas doit rester **bloqué** ? C'est ta matière pour écrire.

<details><summary>🥒 Pour les curieux : c'est quoi Gherkin ?</summary>

Gherkin, c'est une façon d'écrire un scénario de test en **langage quasi-naturel**, structuré, que tout le monde comprend (PM, dev, métier). Trois mots-clés :

- **Étant donné…** → le contexte de départ (l'état avant l'action)
- **Quand…** → l'action que tu fais
- **Alors…** → le résultat attendu

Par exemple :

- **Étant donné** une facture de 1234 € et son règlement de 1234 € non lettrés
- **Quand** je coche les deux lignes
- **Alors** le bouton « Lettrer » devient actif

L'intérêt : le même texte sert de **critère d'acceptation** (côté spec) ET de **base du test** (côté technique). C'est le pont entre ce que tu décris et ce que Claude automatise.
</details>

### 2.a Écris tes scénarios (toi d'abord)

Ouvre `docs/lettrage-spec.md` et **remplis la section « Scénarios de validation »** (elle est vide exprès). Écris **au moins deux** scénarios pour UC1, avec ce squelette :

```
Scénario : <nom>
  Étant donné <situation de départ>
  Quand <ce que je fais>
  Alors <ce que j'attends>
```

- un **cas nominal** — le happy path des **Étapes** d'UC1 (facture + règlement de même montant → lettrés) ;
- un **garde-fou** — un **Cas d'erreur** d'UC1 : le bouton **doit rester bloqué**. Lequel te semble le plus risqué à laisser passer — montants différents ? une seule ligne cochée ? À toi de trancher.

**Écris-les d'abord toi-même** — c'est là que tu réfléchis produit. Bloqué sur un cas ou une formulation ? Tu peux demander un coup de main à Claude (te débloquer, reformuler) — mais **c'est toi qui décides ce qui compte**, ne le laisse pas choisir les cas à ta place. Besoin de voir à quoi ça ressemble ? Un **exemple rempli (pour UC3 — délettrage)** t'attend déjà dans la section « Scénarios de validation » de la spec : calque-toi dessus pour la **forme**, mais les scénarios d'UC1 restent à toi. (Rappel du format aussi dans le toggle 🥒 ci-dessus.)

### 2.b Fais traduire par Claude, puis lance

Donne à Claude **tes scénarios** (la section « Scénarios de validation » que tu viens de remplir) : « **traduis ces scénarios en test Playwright, sans en ajouter ni en retirer** ». Claude **traduit** — il ne décide pas quoi tester, tu l'as déjà fait. Puis lance le test toi-même :

```bash
npx playwright test
```

Puis **regarde-le tourner** pour voir ce que ça donne — c'est le moment sympa :

```bash
npx playwright test --ui       # le navigateur clique sous tes yeux, étape par étape
npx playwright show-report     # après le run : rejeu en images + vidéo
```

**Tu as fini quand tes deux scénarios passent au vert** : le nominal (ça se lettre) **et** ton garde-fou (le bouton reste bloqué).

**La pièce** : **TES** scénarios Gherkin + le test Playwright qui les joue.

<details><summary>💡 Tips : écrire de bons scénarios</summary>

- **Écris d'abord, fais traduire ensuite.** Le geste PM, c'est décider quoi prouver — pas dicter du code à Claude.
- **Ne te contente pas du cas qui marche.** Un nominal tout seul ne prouve pas grand-chose ; ton garde-fou est le plus intéressant.
- Demande à Claude que le test **prépare ses données** (une facture + son règlement) tout seul, pour qu'il soit rejouable sans base dans un état précis.
</details>

---

## Étape 3 - Quand ça coince : vrai bug ou test mal écrit ? (~15 min)

Quentin, le dev de Ketchup Compta, part en congés et te laisse un message :

> 💬 J'ai écrit des tests E2E avant de filer, ils sont dans le dossier `enquete/`. Tu peux les lancer pour le QA ? Y en a un qui vire au rouge chez moi, j'ai pas eu le temps de creuser. Dis-moi si c'est un **vrai bug** ou juste le **test**. Merci ! 🙏

Te voilà **côté QA**, avec un test que tu n'as pas écrit et dont tu connais mal le contenu. Un test rouge ne veut pas toujours dire « l'app est cassée » : parfois c'est **le test** qui cherche mal. Savoir faire la différence, c'est le vrai super-pouvoir.

> 🧭 **Rouge = deux causes possibles.** Soit un **vrai bug** dans l'app (comme à l'Étape 1). Soit un **faux KO** : le test cherche mal (mauvais nom de bouton, données pas prêtes…). Le réflexe : demander à Claude lequel des deux.

Quentin t'a laissé son test dans `e2e/enquete/`. **Lance-le toi-même** dans ton terminal :

```bash
npx playwright test --config=enquete/playwright.config.js
```

Ou, pour le **voir tourner à l'écran** (le navigateur clique sous tes yeux), ajoute `--ui` :

```bash
npx playwright test --ui --config=enquete/playwright.config.js
```

Il est **rouge**. À toi de mener l'enquête, c'est **toi** qui tranches.

Tu peux investiguer toi-même en mettant le nez dans les différents fichiers :

| Fichier | Ce que le fichier fait | A quoi ça te sert |
| --- | --- | --- |
| `e2e/enquete/enquete.spec.js` | Le test de Quentin : les actions du robot (se connecter, saisir une écriture, valider), puis **la vérification finale** : le message qu'il attend après l'enregistrement. | Voir **ce que le test cherche** exactement… et donc s'il cherche la bonne chose. |
| `www/modules/entries/edit.php` | Le code de la page « saisie d'écriture » que le test pilote. | Voir **ce que l'app fait vraiment** (ex. le vrai message qu'elle affiche). L'écart entre « ce que le test attend » et « ce que l'app fait » = ta réponse. *(Encore plus simple : refais l'action toi-même dans l'app et lis le message à l'écran.)* |
| Le **rapport Playwright** (`npx playwright show-report`) | Après un run, il rejoue le test en images et garde un **instantané de la page au moment où ça a coincé** (+ la vidéo et la trace). | Voir **ce que l'app affichait exactement** à l'instant du test — pratique quand tu ne veux ni lire de code, ni refaire l'action à la main. |

Discute avec Claude, demande-lui les faits, pas la réponse. Va voir dans l'app ce qui se passe pour de vrai.

**Tu as fini quand** tu as tranché *« vrai bug ou faux KO ? »* en t'appuyant sur ce que fait **vraiment** l'app (pas sur la couleur rouge) et corrigé ce qui devait l'être afin de relancer le test et qu'il soit **vert.**

<details><summary>💯 Solution (à n'ouvrir que quand tu penses avoir trouvé)</summary>

C'est un **FAUX KO**. L'écriture est bien enregistrée — l'app affiche « Écriture enregistrée : … » avec un numéro de pièce — mais le test attendait le message « Opération validée », qui n'existe pas. Le test cherchait le **mauvais texte** → on corrige **le test**, pas l'app.

À l'opposé, la divergence de l'Étape 1 (colonnes inversées) était un **vrai bug**. Sentir la différence entre les deux, c'est tout l'enjeu.
</details>

<details><summary>🔍 Pour les curieux : décoder le test de Quentin</summary>

Rappel des 3 mots de l'Étape 0 (`#truc`/`.machin`, `expect`, `await`) : le test de Quentin, c'est le même principe — une **suite d'actions** qui se termine par **une vérification**. Traduit ligne à ligne :

- `page.goto('/login.php')` → va sur la page de connexion
- `page.fill('#username', 'admin')` → tape « admin » dans le champ identifiant
- `page.click('button…')` → clique sur le bouton
- `page.selectOption('#journal_id', …)` → choisit une option dans une liste déroulante
- `page.locator('.flash-success')` → **repère** l'encart vert de confirmation sur la page
- `expect(…).toContain('Opération validée')` → **LA vérification** : « le texte doit contenir *Opération validée* »

👉 Ici, tout le début (aller, remplir, cliquer) marche très bien. C'est **la dernière ligne** — la vérification — qui décide rouge / vert. D'où l'intérêt de bien regarder CE qu'elle vérifie.
</details>

---

## Key takeaways (en groupe)

Wrap up ! Quelques amorces :

- **Migration (Étape 1)** : en quoi rejouer les mêmes tests sur l'ancien et le nouveau te rassure avant une refonte ?
- **Scénarios → test (Étape 2)** : qu'est-ce que ça change d'**écrire toi-même** tes scénarios plutôt que de laisser Claude les inventer ? Ton garde-fou a-t-il attrapé un cas auquel tu n'avais pas pensé au départ ?
- **Vrai KO vs faux KO** : comment tu as su (ou demandé à Claude) que c'était l'un ou l'autre ?
- Quand un test E2E vaut-il le coup, et quand est-ce trop ?

> ✅ **Bravo pour cette cinquième session !**
