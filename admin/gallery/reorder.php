<?php
require_once __DIR__ . '/_common.php';
gallery_admin_action(static function (): void {
    $albumId = valid_id($_POST['album_id'] ?? null);
    $photoId = valid_id($_POST['photo_id'] ?? null);
    $direction = $_POST['direction'] ?? '';
    if (!in_array($direction, ['up', 'down'], true)) {
        throw new InvalidArgumentException('Direction invalide.');
    }
    gallery_write($albumId, static function () use ($albumId, $photoId, $direction): void {
        gallery_photo($photoId, $albumId);
        $photos = gallery_photos($albumId);
        $position = array_search($photoId, array_map('intval', array_column($photos, 'id')), true);
        $next = $position + ($direction === 'up' ? -1 : 1);
        if ($next >= 0 && $next < count($photos)) {
            [$photos[$position], $photos[$next]] = [$photos[$next], $photos[$position]];
        }
        // Normalize all ranks to make ordering deterministic even after deletions or tied imports.
        foreach ($photos as $order => $photo) {
            query('UPDATE gallery_photos SET display_order = ? WHERE id = ? AND album_id = ?', [$order, $photo['id'], $albumId]);
        }
    });
    admin_notice('Ordre enregistré.', 'admin/gallery/album.php?id=' . $albumId . '#photo-' . $photoId);
});
