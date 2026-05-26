-- Widen fifa_code for ISO 3166-1 alpha-2 (AR, BR, …)
-- mysql -u root -p world_cup_poll_db < db/migrations/003_fifa_code_length.sql

USE world_cup_poll_db;

ALTER TABLE teams
    MODIFY COLUMN fifa_code VARCHAR(10) NOT NULL;
