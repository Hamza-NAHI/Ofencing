<?php
require_once __DIR__ . '/_common.php';
gallery_admin_action(static function (): void {
    $albumId = valid_id($_POST['album_id'] ?? null);
    $photoId = valid_id($_POST['photo_id'] ?? null);
    gallery_write($albumId, static function () use ($albumId, $photoId): void {
        gallery_photo($photoId, $albumId);
        query('UPDATE gallery_albums SET cover_photo_id = ? WHERE id = ?', [$photoId, $albumId]);
    });
    admin_notice('Couverture enregistrée.', 'admin/gallery/album.php?id=' . $albumId);
});
