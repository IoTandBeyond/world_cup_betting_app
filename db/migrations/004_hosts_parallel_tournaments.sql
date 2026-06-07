-- Host role, tournament hosts, parallel tournaments, tournament-scoped invitations
-- mysql -u root -p world_cup_poll_db < db/migrations/004_hosts_parallel_tournaments.sql

USE world_cup_poll_db;

ALTER TABLE users
    MODIFY COLUMN role ENUM('admin', 'host', 'user') NOT NULL DEFAULT 'user';

ALTER TABLE tournaments
    ADD COLUMN host_user_id BIGINT UNSIGNED NULL AFTER status,
    ADD CONSTRAINT fk_tournaments_host
        FOREIGN KEY (host_user_id) REFERENCES users(id)
        ON DELETE SET NULL;

ALTER TABLE invitations
    ADD COLUMN tournament_id BIGINT UNSIGNED NULL AFTER invited_by,
    ADD CONSTRAINT fk_invitations_tournament
        FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
        ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS tournament_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tournament_member (tournament_id, user_id),
    CONSTRAINT fk_tm_tournament
        FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_tm_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Existing players → first active tournament (if any)
INSERT IGNORE INTO tournament_members (tournament_id, user_id)
SELECT t.id, u.id
FROM users u
CROSS JOIN (
    SELECT id FROM tournaments
    WHERE status IN ('active', 'upcoming')
    ORDER BY FIELD(status, 'active', 'upcoming'), start_date ASC
    LIMIT 1
) t
WHERE u.role = 'user';
