-- Import once into an empty database selected in phpMyAdmin.
-- Compatible with MySQL 8+ / MariaDB 10.4+ (XAMPP). UTF-8 includes Arabic.
-- No database name, production credentials, default administrator or plaintext passwords.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE admin_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(200) NOT NULL,
    type VARCHAR(100) NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX events_public_date (is_published, event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE training (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT 'ISO: 1 Monday to 7 Sunday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    type VARCHAR(100) NOT NULL,
    location VARCHAR(200) NOT NULL DEFAULT '',
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX training_public_order (is_active, display_order, day_of_week, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    contact VARCHAR(254) NOT NULL,
    level VARCHAR(50) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX messages_read_date (is_read, created_at),
    INDEX messages_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DEVELOPMENT EXAMPLES from the former js/data.js, not confirmed coach availability.
-- Unpublish/remove these through Admin before going live. Import only once.
INSERT INTO events (event_date, title, description, location, type, is_published) VALUES
('2026-09-12', 'Open Piste — Casablanca', 'An open sparring afternoon for intermediate and competitive fencers.', 'Casablanca', 'Open sparring', 1),
('2026-09-26', 'Beginner Discovery Session', 'A welcoming introduction to fencing: movement, distance and your first bout.', 'Rabat', 'Beginner workshop', 1),
('2026-10-10', 'Competition Prep Camp', 'Focused tactical work before the autumn competition block.', 'Casablanca', 'Training camp', 1),
('2026-10-24', 'Youth Fencing Morning', 'A playful technical session designed for young fencers and first-timers.', 'Rabat', 'Youth session', 1);

INSERT INTO training (day_of_week, start_time, end_time, type, display_order, is_active) VALUES
(1, '18:00', '20:00', 'Private lessons', 10, 1),
(2, '19:00', '21:00', 'Small group', 20, 1),
(3, '17:00', '20:00', 'Youth + private', 30, 1),
(5, '18:00', '21:00', 'Private + competition', 40, 1),
(6, '09:00', '13:00', 'Workshops / sparring', 50, 1);
