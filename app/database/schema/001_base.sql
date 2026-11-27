-- Esquema inicial do MyGameList (SQLite): o mesmo desenho do banco antigo, em um passo só.
-- "COLLATE UCI" = comparar e ordenar sem ligar para maiúsculas e acentos (função registrada em App\Core\Database).
-- Datas e horas ficam em texto ISO, no horário do computador.

CREATE TABLE users (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    username         TEXT    NOT NULL COLLATE UCI UNIQUE,
    password_hash    TEXT    NOT NULL,
    total_xp         INTEGER NOT NULL DEFAULT 0,
    level            INTEGER NOT NULL DEFAULT 1,
    theme            TEXT    NULL,
    theme_fx         INTEGER NULL,
    profile_public   INTEGER NOT NULL DEFAULT 1,
    session_version  INTEGER NOT NULL DEFAULT 1,
    created_at       TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    calm_mode        INTEGER NOT NULL DEFAULT 0,
    catalog_layout   TEXT    NULL,
    dashboard_layout TEXT    NULL
);

CREATE TABLE login_attempts (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    ip         TEXT NOT NULL,
    identifier TEXT NOT NULL COLLATE UCI,
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_login_attempts ON login_attempts (ip, identifier, created_at);

CREATE TABLE login_events (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    ip         TEXT NOT NULL,
    user_agent TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_login_events_user ON login_events (user_id, created_at);

CREATE TABLE platforms (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    name     TEXT NOT NULL COLLATE UCI,
    name_key TEXT NOT NULL COLLATE UCI UNIQUE
);
CREATE TABLE genres (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    name     TEXT NOT NULL COLLATE UCI,
    name_key TEXT NOT NULL COLLATE UCI UNIQUE
);
CREATE TABLE companies (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    name     TEXT NOT NULL COLLATE UCI,
    name_key TEXT NOT NULL COLLATE UCI UNIQUE
);

CREATE TABLE games (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    title            TEXT    NOT NULL COLLATE UCI,
    title_key        TEXT    NOT NULL COLLATE UCI,
    platform_id      INTEGER NOT NULL REFERENCES platforms (id),
    developer_id     INTEGER NULL REFERENCES companies (id),
    publisher_id     INTEGER NULL REFERENCES companies (id),
    genre_id         INTEGER NULL REFERENCES genres (id),
    finished_at      TEXT    NOT NULL,
    started_at       TEXT    NULL,
    queued_at        TEXT    NULL,
    release_year     INTEGER NULL,
    playtime_minutes INTEGER NOT NULL DEFAULT 0,
    rating           INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 11),
    difficulty       TEXT    NOT NULL CHECK (difficulty IN ('C','B','A','AA','AAA')),
    used_save_states INTEGER NOT NULL DEFAULT 0,
    intro_by         TEXT    NULL COLLATE UCI,
    intro_where      TEXT    NULL COLLATE UCI,
    intro_age        INTEGER NULL,
    played_where     TEXT    NULL COLLATE UCI,
    music_title      TEXT    NULL,
    music_url        TEXT    NULL,
    cover_path       TEXT    NULL,
    is_sample        INTEGER NOT NULL DEFAULT 0,
    created_at       TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at       TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    UNIQUE (user_id, title_key, platform_id, release_year)
);
CREATE INDEX idx_games_user_finished ON games (user_id, finished_at);
CREATE INDEX idx_games_platform ON games (platform_id);
CREATE INDEX idx_games_developer ON games (developer_id);
CREATE INDEX idx_games_publisher ON games (publisher_id);
CREATE INDEX idx_games_genre ON games (genre_id);

CREATE TABLE game_tasks (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id     INTEGER NOT NULL REFERENCES games (id) ON DELETE CASCADE,
    description TEXT    NOT NULL,
    tier        INTEGER NOT NULL CHECK (tier BETWEEN 1 AND 3)
);
CREATE INDEX idx_tasks_game ON game_tasks (game_id);

CREATE TABLE xp_events (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    game_id     INTEGER NOT NULL REFERENCES games (id) ON DELETE CASCADE,
    source      TEXT    NOT NULL CHECK (source IN ('base','difficulty','task')),
    amount      INTEGER NOT NULL,
    description TEXT    NOT NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_xp_user ON xp_events (user_id);
CREATE INDEX idx_xp_game ON xp_events (game_id);

CREATE TABLE game_people (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL REFERENCES games (id) ON DELETE CASCADE,
    name    TEXT    NOT NULL COLLATE UCI,
    UNIQUE (game_id, name)
);
CREATE INDEX idx_people_name ON game_people (name);

CREATE TABLE settings (
    name  TEXT NOT NULL PRIMARY KEY,
    value TEXT NOT NULL
);
INSERT INTO settings (name, value) VALUES
    ('xp_base','100'), ('xp_diff_C','0'), ('xp_diff_B','15'), ('xp_diff_A','40'), ('xp_diff_AA','80'), ('xp_diff_AAA','150'),
    ('xp_tier_1','25'), ('xp_tier_2','60'), ('xp_tier_3','120'), ('level_max','100'), ('level_total_xp','300000'), ('level_curve_exp','1.8');

CREATE TABLE franchises (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    name        TEXT    NOT NULL COLLATE UCI,
    name_key    TEXT    NOT NULL COLLATE UCI,
    description TEXT    NULL,
    cover_path  TEXT    NULL,
    hero_path   TEXT    NULL,
    cover_focus TEXT    NULL,
    is_sample   INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    UNIQUE (user_id, name_key)
);
CREATE TABLE franchise_sections (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    franchise_id INTEGER NOT NULL REFERENCES franchises (id) ON DELETE CASCADE,
    name         TEXT    NOT NULL COLLATE UCI,
    position     INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX idx_sections_franchise ON franchise_sections (franchise_id, position);
CREATE TABLE franchise_entries (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    section_id   INTEGER NOT NULL REFERENCES franchise_sections (id) ON DELETE CASCADE,
    title        TEXT    NOT NULL COLLATE UCI,
    title_key    TEXT    NOT NULL COLLATE UCI,
    release_year INTEGER NULL,
    game_id      INTEGER NULL REFERENCES games (id) ON DELETE SET NULL,
    position     INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX idx_entries_section ON franchise_entries (section_id, position);
CREATE INDEX idx_entries_title ON franchise_entries (title_key);
CREATE INDEX idx_entries_game ON franchise_entries (game_id);

CREATE TABLE goty_picks (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    year       INTEGER NOT NULL,
    game_id    INTEGER NOT NULL REFERENCES games (id) ON DELETE CASCADE,
    note       TEXT    NULL,
    created_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    UNIQUE (user_id, year)
);
CREATE INDEX idx_goty_game ON goty_picks (game_id);

CREATE TABLE retro_top (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id  INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    year     INTEGER NOT NULL,
    position INTEGER NOT NULL CHECK (position BETWEEN 1 AND 3),
    game_id  INTEGER NOT NULL REFERENCES games (id) ON DELETE CASCADE,
    UNIQUE (user_id, year, position),
    UNIQUE (user_id, year, game_id)
);
CREATE INDEX idx_retro_game ON retro_top (game_id);

CREATE TABLE best_picks (
    user_id   INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    kind      TEXT    NOT NULL COLLATE UCI,
    ref_key   TEXT    NOT NULL COLLATE UCI,
    game_id   INTEGER NOT NULL REFERENCES games (id) ON DELETE CASCADE,
    chosen_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    PRIMARY KEY (user_id, kind, ref_key)
);
CREATE INDEX idx_best_game ON best_picks (game_id);

CREATE TABLE dropped_games (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    title            TEXT    NOT NULL COLLATE UCI,
    title_key        TEXT    NOT NULL COLLATE UCI,
    platform_id      INTEGER NOT NULL REFERENCES platforms (id),
    genre_id         INTEGER NULL REFERENCES genres (id),
    release_year     INTEGER NULL,
    playtime_minutes INTEGER NOT NULL DEFAULT 0,
    dropped_at       TEXT    NOT NULL,
    queued_at        TEXT    NULL,
    started_at       TEXT    NULL,
    return_chance    TEXT    NOT NULL CHECK (return_chance IN ('low','medium','high')),
    reason           TEXT    NULL,
    cover_path       TEXT    NULL,
    is_sample        INTEGER NOT NULL DEFAULT 0,
    created_at       TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at       TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    UNIQUE (user_id, title_key, platform_id)
);
CREATE INDEX idx_dropped_user_date ON dropped_games (user_id, dropped_at);

CREATE TABLE backlog_games (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    title        TEXT    NOT NULL COLLATE UCI,
    title_key    TEXT    NOT NULL COLLATE UCI,
    platform_id  INTEGER NULL REFERENCES platforms (id),
    platform_key INTEGER GENERATED ALWAYS AS (ifnull(platform_id, 0)) STORED,
    genre_id     INTEGER NULL REFERENCES genres (id),
    release_year INTEGER NULL,
    status       TEXT    NOT NULL DEFAULT 'want' CHECK (status IN ('want','playing')),
    priority     TEXT    NOT NULL DEFAULT 'medium' CHECK (priority IN ('low','medium','high')),
    started_at   TEXT    NULL,
    queued_at    TEXT    NULL,
    note         TEXT    NULL,
    cover_path   TEXT    NULL,
    is_sample    INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at   TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    UNIQUE (user_id, title_key, platform_key)
);
CREATE INDEX idx_backlog_user_status ON backlog_games (user_id, status);

CREATE TABLE game_notes (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    game_id    INTEGER NULL REFERENCES games (id) ON DELETE CASCADE,
    backlog_id INTEGER NULL REFERENCES backlog_games (id) ON DELETE CASCADE,
    dropped_id INTEGER NULL REFERENCES dropped_games (id) ON DELETE CASCADE,
    note_date  TEXT    NOT NULL,
    body       TEXT    NOT NULL,
    created_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    CHECK ((game_id IS NOT NULL) + (backlog_id IS NOT NULL) + (dropped_id IS NOT NULL) = 1)
);
CREATE INDEX idx_notes_game ON game_notes (game_id, note_date);
CREATE INDEX idx_notes_backlog ON game_notes (backlog_id, note_date);
CREATE INDEX idx_notes_dropped ON game_notes (dropped_id, note_date);
CREATE INDEX idx_notes_user ON game_notes (user_id);

CREATE TABLE game_shots (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    game_id    INTEGER NULL REFERENCES games (id) ON DELETE CASCADE,
    backlog_id INTEGER NULL REFERENCES backlog_games (id) ON DELETE CASCADE,
    dropped_id INTEGER NULL REFERENCES dropped_games (id) ON DELETE CASCADE,
    note_id    INTEGER NULL REFERENCES game_notes (id) ON DELETE SET NULL,
    path       TEXT    NOT NULL,
    width      INTEGER NOT NULL,
    height     INTEGER NOT NULL,
    caption    TEXT    NULL,
    created_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    CHECK ((game_id IS NOT NULL) + (backlog_id IS NOT NULL) + (dropped_id IS NOT NULL) = 1)
);
CREATE INDEX idx_shots_game ON game_shots (game_id, id);
CREATE INDEX idx_shots_backlog ON game_shots (backlog_id, id);
CREATE INDEX idx_shots_dropped ON game_shots (dropped_id, id);
CREATE INDEX idx_shots_note ON game_shots (note_id);
CREATE INDEX idx_shots_user ON game_shots (user_id);

CREATE TABLE goals (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    year       INTEGER NOT NULL,
    metric     TEXT    NOT NULL CHECK (metric IN ('games','hours')),
    target     INTEGER NOT NULL CHECK (target BETWEEN 1 AND 100000),
    created_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
    UNIQUE (user_id, year, metric)
);

CREATE TABLE journal_entries (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    entry_date TEXT    NOT NULL,
    body       TEXT    NOT NULL,
    created_at TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_journal_user_date ON journal_entries (user_id, entry_date);

CREATE TABLE menu_usage (
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    item    TEXT    NOT NULL,
    hits    INTEGER NOT NULL DEFAULT 0,
    last_at TEXT    NOT NULL,
    PRIMARY KEY (user_id, item)
);

-- updated_at se atualiza sozinho
CREATE TRIGGER trg_games_updated      AFTER UPDATE ON games         WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE games         SET updated_at = datetime('now','localtime') WHERE id = NEW.id; END;
CREATE TRIGGER trg_franchises_updated AFTER UPDATE ON franchises    WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE franchises    SET updated_at = datetime('now','localtime') WHERE id = NEW.id; END;
CREATE TRIGGER trg_goty_updated       AFTER UPDATE ON goty_picks    WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE goty_picks    SET updated_at = datetime('now','localtime') WHERE id = NEW.id; END;
CREATE TRIGGER trg_dropped_updated    AFTER UPDATE ON dropped_games WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE dropped_games SET updated_at = datetime('now','localtime') WHERE id = NEW.id; END;
CREATE TRIGGER trg_backlog_updated    AFTER UPDATE ON backlog_games WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE backlog_games SET updated_at = datetime('now','localtime') WHERE id = NEW.id; END;
CREATE TRIGGER trg_goals_updated      AFTER UPDATE ON goals         WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE goals         SET updated_at = datetime('now','localtime') WHERE id = NEW.id; END;
