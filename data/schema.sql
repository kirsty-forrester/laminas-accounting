CREATE TABLE account (
     account_id INTEGER PRIMARY KEY AUTOINCREMENT,
     name       TEXT NOT NULL,
     type       TEXT NOT NULL CHECK (type IN ('asset', 'expense', 'liability', 'equity', 'income'))
);

CREATE TABLE journal_entry (
     journal_entry_id INTEGER PRIMARY KEY AUTOINCREMENT,
     date             TEXT    NOT NULL,  -- ISO 8601 (YYYY-MM-DD)
     description      TEXT    NOT NULL
);

CREATE TABLE journal_entry_line (
   journal_entry_line_id INTEGER PRIMARY KEY AUTOINCREMENT,
   journal_entry_id      INTEGER NOT NULL REFERENCES journal_entry(journal_entry_id),
   account_id            INTEGER NOT NULL REFERENCES account(account_id),
   direction             TEXT    NOT NULL CHECK (direction IN ('debit', 'credit')),
   amount                INTEGER NOT NULL  -- pennies
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
