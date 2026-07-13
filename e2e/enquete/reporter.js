// Reporter de sortie : affiche un résumé court et lisible du résultat des tests
// (nom du test, réussite/échec, et la ligne d'erreur utile), sans le détail technique.
const ESC = String.fromCharCode(27);
const stripAnsi = (s) =>
  String(s).split(ESC + '[').map((p, i) => (i ? p.replace(/^[0-9;]*m/, '') : p)).join('');

// Traduit le message d'erreur brut en une ligne compréhensible.
function explain(rawMessage) {
  const msg = stripAnsi(rawMessage || '');

  // 1) L'app ne répond pas (elle n'est pas démarrée).
  if (/ERR_CONNECTION_REFUSED|net::ERR|ECONNREFUSED/.test(msg)) {
    return "L'app ne répond pas sur http://localhost:8080 — démarre-la (docker compose up -d), puis relance.";
  }

  // 2) Un élément attendu n'est jamais apparu (mauvais sélecteur, page pas prête…).
  const waiting = msg.match(/waiting for (.+)/);
  if (/Timeout|toBeVisible|not found/i.test(msg) && waiting) {
    return `Le test attendait un élément qui n'est pas apparu sur la page : ${waiting[1].trim()}`;
  }

  // 3) Par défaut : la première ligne utile (ex. « attendu … / l'app a affiché … »).
  const firstLine = msg.split('\n').map((l) => l.trim()).find(Boolean) || '';
  return firstLine.replace(/^Error:\s*/, '');
}

class PMReporter {
  onBegin(_config, suite) {
    this._total = suite.allTests().length;
    this._failed = 0;
  }

  onTestEnd(test, result) {
    if (result.status === 'passed') {
      console.log(`\n✅  ${test.title}`);
      return;
    }
    this._failed++;
    console.log(`\n❌  ${test.title}`);
    const err = result.errors && result.errors[0];
    const line = err ? explain(err.message) : '';
    if (line) console.log(`    ${line}`);
  }

  onEnd() {
    const passed = this._total - this._failed;
    const suffix = this._failed ? `, ${this._failed} au rouge` : '';
    console.log(`\n— ${passed}/${this._total} test(s) au vert${suffix} —`);
  }
}

module.exports = PMReporter;
