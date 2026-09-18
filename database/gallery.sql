-- Existing installations: import this migration ONCE into the existing database.
-- No existing tables or content are changed. Do not re-import schema.sql.
SET NAMES utf8mb4;

CREATE TABLE gallery_albums (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    cover_photo_id INT UNSIGNED NULL,
    event_date DATE NULL,
    location VARCHAR(200) NOT NULL DEFAULT '',
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX gallery_public_order (is_published, display_order, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gallery_photos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    album_id INT UNSIGNED NOT NULL,
    filename VARCHAR(64) NOT NULL UNIQUE,
    original_filename VARCHAR(255) NOT NULL,
    thumbnail_filename VARCHAR(64) NOT NULL UNIQUE,
    caption VARCHAR(1000) NOT NULL DEFAULT '',
    alt_text VARCHAR(300) NOT NULL DEFAULT '',
    file_size INT UNSIGNED NOT NULL,
    thumbnail_size INT UNSIGNED NOT NULL,
    width SMALLINT UNSIGNED NOT NULL,
    height SMALLINT UNSIGNED NOT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT gallery_photo_album FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE,
    INDEX gallery_photo_order (album_id, display_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- cover_photo_id is validated against the same album by PHP. Avoid a circular FK.
