CREATE TABLE account (
     account_id INTEGER PRIMARY KEY AUTOINCREMENT,
     name       TEXT NOT NULL,
     type       TEXT NOT NULL CHECK (type IN ('asset', 'expense', 'liability', 'equity', 'income'))
);

CREATE TABLE journal_entry (
     journal_entry_id INTEGER PRIMARY KEY AUTOINCREMENT,
     date             TEXT    NOT NULL,  -- ISO 8601 (YYYY-MM-DD)
     description      TEXT    NOT NULL,
     status           TEXT    NOT NULL DEFAULT 'draft'
         CHECK (status IN ('draft', 'submitted', 'approved', 'posted', 'voided'))
);

CREATE TABLE journal_entry_line (
   journal_entry_line_id INTEGER PRIMARY KEY AUTOINCREMENT,
   journal_entry_id      INTEGER NOT NULL REFERENCES journal_entry(journal_entry_id),
   account_id            INTEGER NOT NULL REFERENCES account(account_id),
   direction             TEXT    NOT NULL CHECK (direction IN ('debit', 'credit')),
   amount                INTEGER NOT NULL  -- pennies
);

-- Append-only record of each lifecycle transition, written by the audit
-- listener when a journal entry changes status. The status values are not
-- re-constrained here: they only ever record statuses that already passed the
-- CHECK on journal_entry.status, and JournalEntryStatus is the source of truth.
CREATE TABLE audit_log (
    audit_log_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    journal_entry_id INTEGER NOT NULL REFERENCES journal_entry(journal_entry_id),
    from_status      TEXT    NOT NULL,
    to_status        TEXT    NOT NULL,
    actor            TEXT,                                    -- acting user; NULL until auth exists
    created_at       TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP  -- ISO 8601 (UTC)
);

-- Seed data: a realistic small-business chart of accounts.
-- Clear any existing rows first so re-running this seed doesn't duplicate.
DELETE FROM account;
INSERT INTO account (name, type) VALUES
    -- Assets ("has it" buckets)
    ('Cash at Bank',            'asset'),
    ('Petty Cash',              'asset'),
    ('Accounts Receivable',     'asset'),
    ('Inventory',               'asset'),
    ('Office Equipment',        'asset'),
    -- Liabilities (the lender's door)
    ('Accounts Payable',        'liability'),
    ('VAT Payable',             'liability'),
    ('Bank Loan',               'liability'),
    -- Equity (the owner's door)
    ('Owner''s Capital',        'equity'),
    ('Retained Earnings',       'equity'),
    -- Income (the earnings door)
    ('Sales Revenue',           'income'),
    ('Interest Income',         'income'),
    -- Expenses ("used it up" buckets)
    ('Rent Expense',            'expense'),
    ('Wages & Salaries',        'expense'),
    ('Utilities Expense',       'expense'),
    ('Office Supplies',         'expense');
