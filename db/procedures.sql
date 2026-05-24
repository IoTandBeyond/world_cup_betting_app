-- Tournament & team setup procedures
-- Run after db.sql:
--   mysql -u root -p world_cup_poll_db < db/procedures.sql

USE world_cup_poll_db;

DELIMITER $$

-- ---------------------------------------------------------------------------
-- sp_create_tournament
-- Creates a tournament. Only one should be "active" for the app at a time.
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_create_tournament$$
CREATE PROCEDURE sp_create_tournament(
    IN p_name VARCHAR(150),
    IN p_slug VARCHAR(150),
    IN p_year SMALLINT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_status ENUM('upcoming', 'active', 'finished')
)
BEGIN
    IF p_end_date < p_start_date THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'end_date must be on or after start_date';
    END IF;

    INSERT INTO tournaments (name, slug, year, start_date, end_date, status)
    VALUES (p_name, p_slug, p_year, p_start_date, p_end_date, p_status);

    SELECT LAST_INSERT_ID() AS tournament_id;
END$$

-- ---------------------------------------------------------------------------
-- sp_activate_tournament
-- Sets one tournament as active; all others become "upcoming".
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_activate_tournament$$
CREATE PROCEDURE sp_activate_tournament(
    IN p_tournament_id BIGINT UNSIGNED
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tournaments WHERE id = p_tournament_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Tournament not found';
    END IF;

    UPDATE tournaments
    SET status = 'upcoming'
    WHERE id <> p_tournament_id AND status = 'active';

    UPDATE tournaments
    SET status = 'active'
    WHERE id = p_tournament_id;
END$$

-- ---------------------------------------------------------------------------
-- sp_add_team
-- Adds one national team to a tournament.
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_team$$
CREATE PROCEDURE sp_add_team(
    IN p_tournament_id BIGINT UNSIGNED,
    IN p_name VARCHAR(120),
    IN p_short_name VARCHAR(10),
    IN p_fifa_code VARCHAR(3),
    IN p_flag_url VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tournaments WHERE id = p_tournament_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Tournament not found';
    END IF;

    IF EXISTS (
        SELECT 1 FROM teams
        WHERE tournament_id = p_tournament_id AND fifa_code = UPPER(p_fifa_code)
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Team FIFA code already exists for this tournament';
    END IF;

    INSERT INTO teams (tournament_id, name, short_name, fifa_code, flag_url)
    VALUES (
        p_tournament_id,
        p_name,
        UPPER(p_short_name),
        UPPER(p_fifa_code),
        p_flag_url
    );

    SELECT LAST_INSERT_ID() AS team_id;
END$$

-- ---------------------------------------------------------------------------
-- sp_import_teams
-- Bulk import from a comma-separated list (one team per line):
--   Full Name,SHORT,FIF
-- Example:
--   Brazil,BRA,BRA
--   Germany,GER,GER
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_import_teams$$
CREATE PROCEDURE sp_import_teams(
    IN p_tournament_id BIGINT UNSIGNED,
    IN p_csv_lines TEXT
)
BEGIN
    DECLARE v_line TEXT;
    DECLARE v_pos INT DEFAULT 1;
    DECLARE v_next INT;
    DECLARE v_name VARCHAR(120);
    DECLARE v_short VARCHAR(10);
    DECLARE v_fifa VARCHAR(3);
    DECLARE v_count INT DEFAULT 0;

    IF NOT EXISTS (SELECT 1 FROM tournaments WHERE id = p_tournament_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Tournament not found';
    END IF;

    SET p_csv_lines = REPLACE(p_csv_lines, '\r\n', '\n');
    SET p_csv_lines = REPLACE(p_csv_lines, '\r', '\n');

    WHILE v_pos <= CHAR_LENGTH(p_csv_lines) DO
        SET v_next = LOCATE('\n', p_csv_lines, v_pos);
        IF v_next = 0 THEN
            SET v_line = TRIM(SUBSTRING(p_csv_lines, v_pos));
            SET v_pos = CHAR_LENGTH(p_csv_lines) + 1;
        ELSE
            SET v_line = TRIM(SUBSTRING(p_csv_lines, v_pos, v_next - v_pos));
            SET v_pos = v_next + 1;
        END IF;

        IF v_line <> '' AND v_line NOT LIKE 'name,%' AND v_line NOT LIKE 'Name,%' THEN
            SET v_name = TRIM(SUBSTRING_INDEX(v_line, ',', 1));
            SET v_short = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(v_line, ',', 2), ',', -1));
            SET v_fifa = TRIM(SUBSTRING_INDEX(v_line, ',', -1));

            IF v_name <> '' AND v_short <> '' AND v_fifa <> '' THEN
                INSERT IGNORE INTO teams (tournament_id, name, short_name, fifa_code)
                VALUES (
                    p_tournament_id,
                    v_name,
                    UPPER(v_short),
                    UPPER(v_fifa)
                );
                IF ROW_COUNT() > 0 THEN
                    SET v_count = v_count + 1;
                END IF;
            END IF;
        END IF;
    END WHILE;

    SELECT v_count AS teams_imported;
END$$

DELIMITER ;

-- For databases created before uq_teams_tournament_fifa existed in db.sql, run once:
-- ALTER TABLE teams ADD UNIQUE KEY uq_teams_tournament_fifa (tournament_id, fifa_code);
