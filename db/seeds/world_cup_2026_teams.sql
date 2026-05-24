-- Example: load all teams for tournament id 1 (FIFA World Cup 2026)
-- Adjust tournament_id if yours differs:
--   SELECT id, name FROM tournaments;

USE world_cup_poll_db;

SET @tid = (SELECT id FROM tournaments WHERE slug = 'world-cup-2026' LIMIT 1);

-- If no tournament exists, create one first via Admin UI or:
-- CALL sp_create_tournament('FIFA World Cup 2026','world-cup-2026',2026,'2026-06-11','2026-07-19','active');

INSERT IGNORE INTO teams (tournament_id, name, short_name, fifa_code) VALUES
(@tid, 'United States', 'USA', 'USA'),
(@tid, 'Canada', 'CAN', 'CAN'),
(@tid, 'Mexico', 'MEX', 'MEX'),
(@tid, 'Brazil', 'BRA', 'BRA'),
(@tid, 'Argentina', 'ARG', 'ARG'),
(@tid, 'Uruguay', 'URU', 'URU'),
(@tid, 'Colombia', 'COL', 'COL'),
(@tid, 'Ecuador', 'ECU', 'ECU'),
(@tid, 'France', 'FRA', 'FRA'),
(@tid, 'Germany', 'GER', 'GER'),
(@tid, 'Spain', 'ESP', 'ESP'),
(@tid, 'England', 'ENG', 'ENG'),
(@tid, 'Portugal', 'POR', 'POR'),
(@tid, 'Netherlands', 'NED', 'NED'),
(@tid, 'Belgium', 'BEL', 'BEL'),
(@tid, 'Italy', 'ITA', 'ITA'),
(@tid, 'Croatia', 'CRO', 'CRO'),
(@tid, 'Morocco', 'MAR', 'MAR'),
(@tid, 'Senegal', 'SEN', 'SEN'),
(@tid, 'Nigeria', 'NGA', 'NGA'),
(@tid, 'Japan', 'JPN', 'JPN'),
(@tid, 'South Korea', 'KOR', 'KOR'),
(@tid, 'Australia', 'AUS', 'AUS'),
(@tid, 'Saudi Arabia', 'KSA', 'KSA');

SELECT COUNT(*) AS team_count FROM teams WHERE tournament_id = @tid;
