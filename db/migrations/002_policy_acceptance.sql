-- Run on existing databases:
-- mysql -u root -p world_cup_poll_db < db/migrations/002_policy_acceptance.sql

USE world_cup_poll_db;

ALTER TABLE users
    ADD COLUMN policy_accepted_at DATETIME NULL AFTER must_change_password,
    ADD COLUMN policy_version VARCHAR(20) NULL AFTER policy_accepted_at;

-- Existing admins are not blocked (optional — remove if admins must accept too)
UPDATE users
SET policy_accepted_at = NOW(), policy_version = '1.0'
WHERE role = 'admin' AND policy_accepted_at IS NULL;
