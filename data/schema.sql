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
