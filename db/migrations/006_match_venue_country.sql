-- Venue host country for matches
-- mysql -u root -p world_cup_poll_db < db/migrations/006_match_venue_country.sql

USE world_cup_poll_db;

ALTER TABLE matches
    ADD COLUMN venue_country VARCHAR(100) NULL AFTER venue;
