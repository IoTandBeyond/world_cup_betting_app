-- Run on existing databases:
-- mysql -u root -p world_cup_poll_db < db/migrations/001_must_change_password.sql

USE world_cup_poll_db;

ALTER TABLE users
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0
    AFTER is_active;
