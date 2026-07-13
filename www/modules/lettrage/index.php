<?php
/**
 * Lettrage (reconciliation of customer/supplier accounts) - Legacy style
 *
 * Rapproche les factures de leurs règlements sur un compte de tiers (classe 4).
 * Ce qui reste non lettré = impayés (clients) / restes à payer (fournisseurs).
 */

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/utils.php';

require_login();

// ---- POST: letter selected lines ----------------------------------------
if (is_post() && post('action') === 'letter') {
    csrf_verify();

    $account_id = intval(post('account_id'));
    $ids = post('line_ids', array());
    if (!is_array($ids)) {
        $ids = array();
    }
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function ($v) { return $v > 0; });

    $redirect = '/modules/lettrage/index.php?account_id=' . $account_id . '&filter=unlettered';

    if (count($ids) < 2) {
        set_flash('error', 'Sélectionnez au moins deux lignes à rapprocher.');
        redirect($redirect);
    }

    // Re-load the selected lines server-side (never trust client totals)
    $id_list = implode(',', $ids);
    $sql = "SELECT id, debit, credit, lettrage FROM entry_lines
            WHERE account_id = $account_id AND id IN ($id_list)";
    $lines = db_fetch_all(db_query($sql));

    $total_debit = 0;
    $total_credit = 0;
    $already = false;
    foreach ($lines as $l) {
        if (!empty($l['lettrage'])) {
            $already = true;
        }
        $total_debit += $l['debit'];
        $total_credit += $l['credit'];
    }

    if (count($lines) !== count($ids) || $already) {
        set_flash('error', 'Certaines lignes ne sont plus disponibles. Veuillez réessayer.');
        redirect($redirect);
    }

    if (!validate_double_entry($total_debit, $total_credit) || $total_debit <= 0) {
        set_flash('error', 'Les lignes sélectionnées ne s\'équilibrent pas (débit ≠ crédit).');
        redirect($redirect);
    }

    // Assign next lettering code for this account
    $code = next_lettrage_code($account_id);
    $code_esc = db_escape($code);
    db_query("UPDATE entry_lines SET lettrage = '$code_esc'
              WHERE account_id = $account_id AND id IN ($id_list)
              AND (lettrage IS NULL OR lettrage = '')");

    $n = count($ids);
    audit_log('LETTER', 'entry_lines', $account_id,
        "Lettrage $code sur compte #$account_id : lignes $id_list ($n lignes, " . format_money($total_debit) . ")");
    set_flash('success', "$n lignes lettrées sous le code « $code ».");
    redirect($redirect);
}

// ---- POST: unletter a code ----------------------------------------------
if (is_post() && post('action') === 'unletter') {
    csrf_verify();

    $account_id = intval(post('account_id'));
    $code = db_escape(trim(post('code')));

    $sql = "SELECT COUNT(*) as count FROM entry_lines
            WHERE account_id = $account_id AND lettrage = '$code'";
    $n = db_fetch_assoc(db_query($sql))['count'];

    db_query("UPDATE entry_lines SET lettrage = NULL
              WHERE account_id = $account_id AND lettrage = '$code'");

    audit_log('UNLETTER', 'entry_lines', $account_id,
        "Délettrage $code sur compte #$account_id ($n lignes)");
    set_flash('success', "Lettrage « $code » annulé — $n lignes redeviennent disponibles.");
    redirect('/modules/lettrage/index.php?account_id=' . $account_id . '&filter=lettered');
}

// ---- GET: build the view -------------------------------------------------
// Auxiliary accounts = class 4 (tiers) that actually carry entry lines
$accounts = db_fetch_all(db_query(
    "SELECT DISTINCT a.id, a.code, a.label
     FROM accounts a
     INNER JOIN entry_lines el ON el.account_id = a.id
     WHERE a.code LIKE '4%'
     ORDER BY a.code"
));

// Selected account (default: 411000 if present, else first available)
$account_id = intval(get('account_id'));
$current = null;
foreach ($accounts as $a) {
    if ($a['id'] == $account_id) { $current = $a; break; }
}
if (!$current && count($accounts) > 0) {
    foreach ($accounts as $a) {
        if ($a['code'] === '411000') { $current = $a; break; }
    }
    if (!$current) {
        $current = $accounts[0];
    }
    $account_id = intval($current['id']);
}

$filter = get('filter', 'unlettered');
if (!in_array($filter, array('unlettered', 'lettered', 'all'))) {
    $filter = 'unlettered';
}

// Load the lines for the selected account
$lines = array();
if ($current) {
    $lines = db_fetch_all(db_query(
        "SELECT el.id, el.label, el.debit, el.credit, el.lettrage,
                e.entry_date, e.piece_number, j.code as journal_code
         FROM entry_lines el
         INNER JOIN entries e ON el.entry_id = e.id
         LEFT JOIN journals j ON e.journal_id = j.id
         WHERE el.account_id = $account_id
         ORDER BY e.entry_date, e.id, el.line_no"
    ));
}

$lettered = array();
$unlettered = array();
foreach ($lines as $l) {
    if (!empty($l['lettrage'])) {
        $lettered[] = $l;
    } else {
        $unlettered[] = $l;
    }
}

// Group lettered lines by code
$groups = array();
foreach ($lettered as $l) {
    $groups[$l['lettrage']][] = $l;
}
ksort($groups);

function lettrage_tab_url($account_id, $filter) {
    return '/modules/lettrage/index.php?account_id=' . $account_id . '&filter=' . $filter;
}

$page_title = 'Lettrage';
require_once __DIR__ . '/../../header.php';
?>

<h2>Lettrage</h2>

<div class="flash flash-info">
    Rapprochez les factures de leurs règlements. Ce qui reste <strong>non lettré</strong>
    = vos impayés (clients) et restes à payer (fournisseurs).
</div>

<?php if (count($accounts) === 0): ?>
    <div class="empty">Aucun compte de tiers (classe 4) ne contient d'écritures à lettrer.</div>
    <?php require_once __DIR__ . '/../../footer.php'; exit; ?>
<?php endif; ?>

<!-- Account picker + tabs -->
<div class="filters">
    <form method="get" action="/modules/lettrage/index.php" style="display:inline;">
        <label for="account_id">Compte :</label>
        <select id="account_id" name="account_id" onchange="this.form.submit()">
            <?php foreach ($accounts as $a): ?>
            <option value="<?php echo $a['id']; ?>" <?php echo $a['id'] == $account_id ? 'selected' : ''; ?>>
                <?php echo h($a['code'] . ' — ' . $a['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="filter" value="<?php echo h($filter); ?>">
    </form>

    <div class="tabs">
        <a href="<?php echo lettrage_tab_url($account_id, 'unlettered'); ?>"
           class="<?php echo $filter === 'unlettered' ? 'active' : ''; ?>">
            Non lettré <span class="count">(<?php echo count($unlettered); ?>)</span>
        </a>
        <a href="<?php echo lettrage_tab_url($account_id, 'lettered'); ?>"
           class="<?php echo $filter === 'lettered' ? 'active' : ''; ?>">
            Lettré <span class="count">(<?php echo count($lettered); ?>)</span>
        </a>
        <a href="<?php echo lettrage_tab_url($account_id, 'all'); ?>"
           class="<?php echo $filter === 'all' ? 'active' : ''; ?>">
            Tout <span class="count">(<?php echo count($lines); ?>)</span>
        </a>
    </div>
</div>

<?php if ($filter === 'unlettered'): ?>
    <?php if (count($unlettered) === 0): ?>
        <div class="empty">Tout est lettré sur ce compte 🎉 — aucun impayé.</div>
    <?php else: ?>
    <form id="lettrage-form" method="post" action="<?php echo lettrage_tab_url($account_id, 'unlettered'); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="letter">
        <input type="hidden" name="account_id" value="<?php echo $account_id; ?>">

        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-check"></th>
                    <th>Date</th>
                    <th>Jrnl</th>
                    <th>N° Pièce</th>
                    <th>Libellé</th>
                    <th>Débit</th>
                    <th>Crédit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unlettered as $l): ?>
                <tr class="lettering-line">
                    <td class="col-check">
                        <input type="checkbox" name="line_ids[]" value="<?php echo $l['id']; ?>"
                               data-debit="<?php echo $l['debit']; ?>"
                               data-credit="<?php echo $l['credit']; ?>">
                    </td>
                    <td><?php echo format_date($l['entry_date']); ?></td>
                    <td><?php echo h($l['journal_code']); ?></td>
                    <td><?php echo h($l['piece_number']); ?></td>
                    <td><?php echo h($l['label']); ?></td>
                    <td class="number"><?php echo $l['debit'] > 0 ? format_money($l['debit']) : ''; ?></td>
                    <td class="number"><?php echo $l['credit'] > 0 ? format_money($l['credit']) : ''; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="lettering-summary">
            <div class="totals">
                <div class="total-item">Total débit<strong id="sum-debit">0,00 EUR</strong></div>
                <div class="total-item">Total crédit<strong id="sum-credit">0,00 EUR</strong></div>
                <div class="total-item ecart">Écart<strong id="sum-ecart" class="unbalanced">0,00 EUR</strong></div>
            </div>
            <div class="hint" id="lettrage-hint">Cochez les lignes qui se compensent (factures + règlements).</div>
            <div class="actions">
                <button type="button" class="btn btn-small" id="btn-clear">Tout décocher</button>
                <button type="button" class="btn btn-success" id="btn-letter" disabled>Lettrer</button>
            </div>
        </div>
    </form>
    <?php endif; ?>

<?php elseif ($filter === 'lettered'): ?>
    <?php if (count($lettered) === 0): ?>
        <div class="empty">Aucun lettrage sur ce compte pour l'instant.</div>
    <?php else: ?>
        <?php foreach ($groups as $code => $grp): ?>
            <?php
            $gd = 0;
            foreach ($grp as $l) { $gd += $l['debit']; }
            ?>
            <div class="letter-group">
                <div class="letter-group-head">
                    <span class="code-badge"><?php echo h($code); ?></span>
                    <span class="grp-title"><?php echo count($grp); ?> lignes — <?php echo format_money($gd); ?> soldées</span>
                    <form method="post" action="<?php echo lettrage_tab_url($account_id, 'lettered'); ?>" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="unletter">
                        <input type="hidden" name="account_id" value="<?php echo $account_id; ?>">
                        <input type="hidden" name="code" value="<?php echo h($code); ?>">
                        <button type="submit" class="btn btn-danger btn-small confirm-action"
                                data-confirm="Délettrer le code <?php echo h($code); ?> ?">Délettrer</button>
                    </form>
                </div>
                <table class="data-table">
                    <tbody>
                        <?php foreach ($grp as $l): ?>
                        <tr>
                            <td><?php echo format_date($l['entry_date']); ?></td>
                            <td><?php echo h($l['journal_code']); ?></td>
                            <td><?php echo h($l['piece_number']); ?></td>
                            <td><?php echo h($l['label']); ?></td>
                            <td class="number"><?php echo $l['debit'] > 0 ? format_money($l['debit']) : ''; ?></td>
                            <td class="number"><?php echo $l['credit'] > 0 ? format_money($l['credit']) : ''; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php else: /* all */ ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Jrnl</th>
                <th>N° Pièce</th>
                <th>Libellé</th>
                <th>Débit</th>
                <th>Crédit</th>
                <th class="col-code">Lettr.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $l): ?>
            <tr>
                <td><?php echo format_date($l['entry_date']); ?></td>
                <td><?php echo h($l['journal_code']); ?></td>
                <td><?php echo h($l['piece_number']); ?></td>
                <td><?php echo h($l['label']); ?></td>
                <td class="number"><?php echo $l['debit'] > 0 ? format_money($l['debit']) : ''; ?></td>
                <td class="number"><?php echo $l['credit'] > 0 ? format_money($l['credit']) : ''; ?></td>
                <td class="col-code">
                    <?php if (!empty($l['lettrage'])): ?>
                        <span class="code-badge"><?php echo h($l['lettrage']); ?></span>
                    <?php else: ?>
                        <span class="impaye-tag">non soldé</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
// Live reconciliation summary for the "Non lettré" tab.
// Plain JS (no jQuery dependency: this runs before the footer loads jQuery).
(function () {
    var form = document.getElementById('lettrage-form');
    if (!form) { return; }

    function boxes() {
        return form.querySelectorAll('input[name="line_ids[]"]');
    }

    function money(n) {
        var s = Math.abs(n).toFixed(2).replace('.', ',');
        s = s.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        return (n < 0 ? '-' : '') + s + ' EUR';
    }

    function recompute() {
        var d = 0, c = 0, count = 0;
        Array.prototype.forEach.call(boxes(), function (b) {
            if (b.checked) {
                d += parseFloat(b.getAttribute('data-debit')) || 0;
                c += parseFloat(b.getAttribute('data-credit')) || 0;
                count++;
            }
            var row = b.closest('tr');
            if (row) { row.classList.toggle('selected', b.checked); }
        });

        var ecart = Math.round((d - c) * 100) / 100;
        var balanced = count >= 2 && Math.abs(ecart) < 0.005 && d > 0;

        document.getElementById('sum-debit').textContent = money(d);
        document.getElementById('sum-credit').textContent = money(c);
        var ec = document.getElementById('sum-ecart');
        ec.textContent = money(ecart);
        ec.className = balanced ? 'balanced' : 'unbalanced';

        var hint, cls;
        if (count === 0) {
            hint = 'Cochez les lignes qui se compensent (factures + règlements).'; cls = '';
        } else if (balanced) {
            hint = '✓ Équilibré — prêt à lettrer ' + count + ' lignes.'; cls = 'balanced';
        } else if (ecart > 0) {
            hint = 'Reste ' + money(ecart) + ' à sélectionner en CRÉDIT.'; cls = 'unbalanced';
        } else {
            hint = 'Reste ' + money(-ecart) + ' à sélectionner en DÉBIT.'; cls = 'unbalanced';
        }
        var h = document.getElementById('lettrage-hint');
        h.textContent = hint;
        h.className = 'hint ' + cls;
        document.getElementById('btn-letter').disabled = !balanced;
    }

    // Click anywhere on the row toggles its checkbox
    Array.prototype.forEach.call(form.querySelectorAll('tr.lettering-line'), function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.tagName !== 'INPUT') {
                var box = row.querySelector('input[name="line_ids[]"]');
                box.checked = !box.checked;
            }
            recompute();
        });
    });

    document.getElementById('btn-clear').addEventListener('click', function () {
        Array.prototype.forEach.call(boxes(), function (b) { b.checked = false; });
        recompute();
    });

    document.getElementById('btn-letter').addEventListener('click', function () {
        var d = 0, dCount = 0, cCount = 0;
        Array.prototype.forEach.call(boxes(), function (b) {
            if (!b.checked) { return; }
            var dd = parseFloat(b.getAttribute('data-debit')) || 0;
            var cc = parseFloat(b.getAttribute('data-credit')) || 0;
            d += dd;
            if (dd > 0) { dCount++; }
            if (cc > 0) { cCount++; }
        });
        var isMultiple = dCount > 1 || cCount > 1;

        if (!isMultiple && localStorage.getItem('skipSimpleLettrage') === '1') {
            form.submit();
            return;
        }
        showModal(dCount, cCount, d, isMultiple);
    });

    function showModal(dCount, cCount, total, isMultiple) {
        var note = isMultiple
            ? '<div class="badge-multiple"><strong>Lettrage multiple</strong><br>' +
              'Vous regroupez plusieurs lignes sous une seule lettre. Vérifiez qu\'elles correspondent ' +
              'bien au <strong>même règlement</strong> — sinon, lettrez-les en groupes séparés.</div>'
            : '<div class="badge-multiple badge-simple">Lettrage simple — un débit rapproché d\'un crédit.</div>';

        var skip = isMultiple ? ''
            : '<label class="modal-skip"><input type="checkbox" id="m-skip"> Ne plus afficher pour les lettrages simples</label>';

        var overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML =
            '<div class="modal" role="dialog" aria-modal="true">' +
              '<div class="modal-head">Confirmer le lettrage</div>' +
              '<div class="modal-body">' +
                '<div class="code-line">Ces lignes vont être rapprochées et marquées soldées.</div>' +
                '<div class="recap-line"><span class="lbl">Lignes au débit</span><span class="val">' + dCount + '</span></div>' +
                '<div class="recap-line"><span class="lbl">Lignes au crédit</span><span class="val">' + cCount + '</span></div>' +
                '<div class="recap-line"><span class="lbl">Montant soldé</span><span class="val">' + money(total) + '</span></div>' +
                note +
              '</div>' +
              '<div class="modal-foot">' +
                skip +
                '<button type="button" class="btn btn-small" id="m-cancel">Annuler</button>' +
                '<button type="button" class="btn btn-success" id="m-confirm">Confirmer le lettrage</button>' +
              '</div>' +
            '</div>';
        document.body.appendChild(overlay);

        function close() { document.body.removeChild(overlay); }
        overlay.addEventListener('click', function (e) { if (e.target === overlay) { close(); } });
        document.getElementById('m-cancel').addEventListener('click', close);
        document.getElementById('m-confirm').addEventListener('click', function () {
            var s = document.getElementById('m-skip');
            if (s && s.checked) { localStorage.setItem('skipSimpleLettrage', '1'); }
            close();
            form.submit();
        });
    }

    recompute();
})();
</script>

<?php require_once __DIR__ . '/../../footer.php'; ?>
