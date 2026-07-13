-- Sample lettering data
-- Adds a few extra customer entries so the lettering screen has real material:
-- some pairs are pre-reconciled (for the "Lettré" tab), others are left open
-- so they can be matched by hand. Lines are matched by piece number so this
-- stays stable regardless of auto-increment ids.

-- ============================================================================
-- ADDITIONAL ENTRIES (ids continue after 02_seed.sql, which stops at 8)
-- ============================================================================

-- Entry 9: Customer payment completing FA-2026-001 (BK - January 16)
-- This is the règlement that pairs with VE2026-000001 (1200) on 411000.
INSERT INTO entries (id, journal_id, entry_date, piece_number, label, status, total_debit, total_credit, created_by, created_at, posted_at)
VALUES (9, 3, '2026-01-16', 'BK2026-000004', 'Règlement client FA-2026-001', 'posted', 1200.00, 1200.00, 1, '2026-01-16 09:00:00', '2026-01-16 09:00:00');

INSERT INTO entry_lines (entry_id, line_no, account_id, label, debit, credit) VALUES
(9, 1, (SELECT id FROM accounts WHERE code = '512000'), 'Virement reçu client', 1200.00, 0),
(9, 2, (SELECT id FROM accounts WHERE code = '411000'), 'Règlement FA-2026-001', 0, 1200.00);

-- Entry 10: Sales invoice FA-2026-003 (VE - January 22) -- LEFT OPEN
INSERT INTO entries (id, journal_id, entry_date, piece_number, label, status, total_debit, total_credit, created_by, created_at, posted_at)
VALUES (10, 1, '2026-01-22', 'VE2026-000003', 'Facture client FA-2026-003', 'posted', 1500.00, 1500.00, 1, '2026-01-22 10:00:00', '2026-01-22 10:00:00');

INSERT INTO entry_lines (entry_id, line_no, account_id, label, debit, credit) VALUES
(10, 1, (SELECT id FROM accounts WHERE code = '411000'), 'Client - FA-2026-003', 1500.00, 0),
(10, 2, (SELECT id FROM accounts WHERE code = '707000'), 'Ventes de marchandises', 0, 1500.00);

-- Entry 11: Customer payment for FA-2026-003 (BK - January 25) -- LEFT OPEN
INSERT INTO entries (id, journal_id, entry_date, piece_number, label, status, total_debit, total_credit, created_by, created_at, posted_at)
VALUES (11, 3, '2026-01-25', 'BK2026-000005', 'Règlement client FA-2026-003', 'posted', 1500.00, 1500.00, 1, '2026-01-25 11:00:00', '2026-01-25 11:00:00');

INSERT INTO entry_lines (entry_id, line_no, account_id, label, debit, credit) VALUES
(11, 1, (SELECT id FROM accounts WHERE code = '512000'), 'Virement reçu client', 1500.00, 0),
(11, 2, (SELECT id FROM accounts WHERE code = '411000'), 'Règlement FA-2026-003', 0, 1500.00);

-- Entry 12: Sales invoice FA-2026-004 (VE - January 28) -- LEFT OPEN (multi-facture)
INSERT INTO entries (id, journal_id, entry_date, piece_number, label, status, total_debit, total_credit, created_by, created_at, posted_at)
VALUES (12, 1, '2026-01-28', 'VE2026-000004', 'Facture client FA-2026-004', 'posted', 600.00, 600.00, 1, '2026-01-28 14:00:00', '2026-01-28 14:00:00');

INSERT INTO entry_lines (entry_id, line_no, account_id, label, debit, credit) VALUES
(12, 1, (SELECT id FROM accounts WHERE code = '411000'), 'Client - FA-2026-004', 600.00, 0),
(12, 2, (SELECT id FROM accounts WHERE code = '707000'), 'Ventes de marchandises', 0, 600.00);

-- Entry 13: Sales invoice FA-2026-005 (VE - January 29) -- LEFT OPEN (multi-facture)
INSERT INTO entries (id, journal_id, entry_date, piece_number, label, status, total_debit, total_credit, created_by, created_at, posted_at)
VALUES (13, 1, '2026-01-29', 'VE2026-000005', 'Facture client FA-2026-005', 'posted', 900.00, 900.00, 1, '2026-01-29 14:00:00', '2026-01-29 14:00:00');

INSERT INTO entry_lines (entry_id, line_no, account_id, label, debit, credit) VALUES
(13, 1, (SELECT id FROM accounts WHERE code = '411000'), 'Client - FA-2026-005', 900.00, 0),
(13, 2, (SELECT id FROM accounts WHERE code = '707000'), 'Ventes de marchandises', 0, 900.00);

-- Entry 14: Single payment covering FA-2026-004 + FA-2026-005 (BK - February 2) -- LEFT OPEN
INSERT INTO entries (id, journal_id, entry_date, piece_number, label, status, total_debit, total_credit, created_by, created_at, posted_at)
VALUES (14, 3, '2026-02-02', 'BK2026-000006', 'Règlement client FA-004 + FA-005', 'posted', 1500.00, 1500.00, 1, '2026-02-02 09:30:00', '2026-02-02 09:30:00');

INSERT INTO entry_lines (entry_id, line_no, account_id, label, debit, credit) VALUES
(14, 1, (SELECT id FROM accounts WHERE code = '512000'), 'Virement reçu client', 1500.00, 0),
(14, 2, (SELECT id FROM accounts WHERE code = '411000'), 'Règlement FA-004 et FA-005', 0, 1500.00);

-- ============================================================================
-- PRE-RECONCILED PAIRS (populate the "Lettré" tab)
-- ============================================================================

-- 411000 Clients : facture FA-2026-001 lettrée avec son règlement BK2026-000004
UPDATE entry_lines
SET lettrage = 'A'
WHERE account_id = (SELECT id FROM accounts WHERE code = '411000')
  AND entry_id IN (
    SELECT id FROM entries WHERE piece_number IN ('VE2026-000001', 'BK2026-000004')
  );

-- 401000 Fournisseurs : facture ABC (AC2026-000001) lettrée avec son règlement
UPDATE entry_lines
SET lettrage = 'A'
WHERE account_id = (SELECT id FROM accounts WHERE code = '401000')
  AND entry_id IN (
    SELECT id FROM entries WHERE piece_number IN ('AC2026-000001', 'BK2026-000002')
  );

-- ============================================================================
-- Advance journal counters past the pieces created above (avoids collisions
-- with numbers generated when posting new entries through the app).
-- ============================================================================
UPDATE journals SET next_number = 6 WHERE code = 'VE';
UPDATE journals SET next_number = 7 WHERE code = 'BK';
