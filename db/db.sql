CREATE DATABASE world_cup_poll_db;

USE world_cup_poll_db;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    policy_accepted_at DATETIME NULL,
    policy_version VARCHAR(20) NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    invited_by BIGINT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invitations_user
        FOREIGN KEY (invited_by)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tournaments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    year SMALLINT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM(
        'upcoming',
        'active',
        'finished'
    ) DEFAULT 'upcoming',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    short_name VARCHAR(10) NOT NULL,
    fifa_code VARCHAR(3) NOT NULL,
    flag_url VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_teams_tournament
        FOREIGN KEY (tournament_id)
        REFERENCES tournaments(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_teams_tournament_fifa (tournament_id, fifa_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE players (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    position ENUM(
        'goalkeeper',
        'defender',
        'midfielder',
        'forward'
    ) NOT NULL,
    shirt_number TINYINT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_players_team
        FOREIGN KEY (team_id)
        REFERENCES teams(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    stage ENUM(
        'group',
        'round_of_16',
        'quarter_final',
        'semi_final',
        'third_place',
        'final'
    ) NOT NULL,
    group_name VARCHAR(5) NULL,
    home_team_id BIGINT UNSIGNED NOT NULL,
    away_team_id BIGINT UNSIGNED NOT NULL,
    kickoff_at DATETIME NOT NULL,
    venue VARCHAR(150) NULL,
    home_score TINYINT NULL,
    away_score TINYINT NULL,
    status ENUM(
        'scheduled',
        'live',
        'finished',
        'cancelled'
    ) DEFAULT 'scheduled',
    allow_predictions TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_matches_tournament
        FOREIGN KEY (tournament_id)
        REFERENCES tournaments(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_matches_home_team
        FOREIGN KEY (home_team_id)
        REFERENCES teams(id),
    CONSTRAINT fk_matches_away_team
        FOREIGN KEY (away_team_id)
        REFERENCES teams(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE predictions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    match_id BIGINT UNSIGNED NOT NULL,
    predicted_home_score TINYINT NOT NULL,
    predicted_away_score TINYINT NOT NULL,
    points_awarded TINYINT DEFAULT 0,
    scored_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_match (
        user_id,
        match_id
    ),
    CONSTRAINT fk_predictions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_predictions_match
        FOREIGN KEY (match_id)
        REFERENCES matches(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE bonus_predictions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tournament_id BIGINT UNSIGNED NOT NULL,
    world_cup_winner_team_id BIGINT UNSIGNED NULL,
    top_scorer_player_id BIGINT UNSIGNED NULL,
    best_goalkeeper_player_id BIGINT UNSIGNED NULL,
    mvp_player_id BIGINT UNSIGNED NULL,
    points_awarded SMALLINT DEFAULT 0,
    scored_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bonus_user_tournament (
        user_id,
        tournament_id
    ),
    CONSTRAINT fk_bonus_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bonus_tournament
        FOREIGN KEY (tournament_id)
        REFERENCES tournaments(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bonus_winner_team
        FOREIGN KEY (world_cup_winner_team_id)
        REFERENCES teams(id),
    CONSTRAINT fk_bonus_top_scorer
        FOREIGN KEY (top_scorer_player_id)
        REFERENCES players(id),
    CONSTRAINT fk_bonus_goalkeeper
        FOREIGN KEY (best_goalkeeper_player_id)
        REFERENCES players(id),
    CONSTRAINT fk_bonus_mvp
        FOREIGN KEY (mvp_player_id)
        REFERENCES players(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE points_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    prediction_id BIGINT UNSIGNED NULL,
    bonus_prediction_id BIGINT UNSIGNED NULL,
    points SMALLINT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_points_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE leaderboard_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    total_points INT DEFAULT 0,
    rank_position INT DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_leaderboard (
        tournament_id,
        user_id
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO settings (`key`, `value`) VALUES
('points_exact_score', '5'),
('points_correct_winner', '3'),
('points_correct_draw', '3'),
('points_world_cup_winner', '10'),
('points_top_scorer', '10'),
('points_best_goalkeeper', '7'),
('points_mvp', '7');

CREATE INDEX idx_matches_kickoff
ON matches(kickoff_at);
CREATE INDEX idx_matches_status
ON matches(status);
CREATE INDEX idx_predictions_user
ON predictions(user_id);
CREATE INDEX idx_predictions_match
ON predictions(match_id);
CREATE INDEX idx_points_user
ON points_log(user_id);

-- Default admin (password: AdminPassword123!)
INSERT INTO users (uuid, name, email, password_hash, role) VALUES (
    '00000000-0000-4000-8000-000000000001',
    'Admin',
    'admin@worldcup.local',
    '$2y$12$6KhH0y/QsYYXg8qoYpHYcuqEn6mydGrr8OFYPtpWTomckMncep9WC',
    'admin'
);

INSERT INTO tournaments (name, slug, year, start_date, end_date, status) VALUES (
    'FIFA World Cup 2026',
    'world-cup-2026',
    2026,
    '2026-06-11',
    '2026-07-19',
    'active'
);

INSERT INTO teams (tournament_id, name, short_name, fifa_code) VALUES
(1, 'Brazil', 'BRA', 'BRA'),
(1, 'Germany', 'GER', 'GER'),
(1, 'Argentina', 'ARG', 'ARG'),
(1, 'France', 'FRA', 'FRA');