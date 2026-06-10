-- Goal-difference scoring tier
-- mysql -u root -p world_cup_poll_db < db/migrations/005_scoring_goal_difference.sql

USE world_cup_poll_db;

INSERT INTO settings (`key`, `value`)
VALUES ('points_correct_diff', '3')
ON DUPLICATE KEY UPDATE `value` = '3';

UPDATE settings SET `value` = '2' WHERE `key` = 'points_correct_winner';
UPDATE settings SET `value` = '2' WHERE `key` = 'points_correct_draw';
