-- Optional SQLite schema for persisting exercise results; the application runs without it.
-- Apply with: sqlite3 php-labs.db < db/schema.sql
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS players (
    id INTEGER PRIMARY KEY,
    display_name TEXT NOT NULL UNIQUE CHECK (length(display_name) BETWEEN 1 AND 64),
    created_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ', 'now'))
);

CREATE TABLE IF NOT EXISTS rps_rounds (
    id INTEGER PRIMARY KEY,
    player_id INTEGER NOT NULL REFERENCES players (id) ON DELETE CASCADE,
    human_move TEXT NOT NULL CHECK (human_move IN ('rock', 'paper', 'scissors')),
    computer_move TEXT NOT NULL CHECK (computer_move IN ('rock', 'paper', 'scissors')),
    outcome TEXT NOT NULL CHECK (outcome IN ('tie', 'win', 'lose')),
    played_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ', 'now'))
);

CREATE INDEX IF NOT EXISTS idx_rps_rounds_player_played ON rps_rounds (player_id, played_at);

CREATE TABLE IF NOT EXISTS guess_attempts (
    id INTEGER PRIMARY KEY,
    guess_value REAL NOT NULL,
    outcome TEXT NOT NULL CHECK (outcome IN ('too_low', 'too_high', 'correct')),
    attempted_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ', 'now'))
);

CREATE TABLE IF NOT EXISTS hash_recovery_runs (
    id INTEGER PRIMARY KEY,
    was_recovered INTEGER NOT NULL CHECK (was_recovered IN (0, 1)),
    attempts INTEGER NOT NULL CHECK (attempts BETWEEN 1 AND 10000),
    elapsed_ms REAL NOT NULL CHECK (elapsed_ms >= 0),
    ran_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ', 'now'))
);
