<?php
require_once __DIR__ . '/_common.php';
gallery_admin_action(static function (): void {
    $albumId = valid_id($_POST['album_id'] ?? null);
    $photoId = valid_id($_POST['photo_id'] ?? null);
    $caption = field($_POST, 'caption', 1000, false);
    $alt = field($_POST, 'alt_text', 300, false);
    gallery_write($albumId, static function () use ($albumId, $photoId, $caption, $alt): void {
        gallery_photo($photoId, $albumId);
        query('UPDATE gallery_photos SET caption = ?, alt_text = ? WHERE id = ? AND album_id = ?', [$caption, $alt, $photoId, $albumId]);
    });
    admin_notice('Textes enregistrés.', 'admin/gallery/album.php?id=' . $albumId . '#photo-' . $photoId);
});
