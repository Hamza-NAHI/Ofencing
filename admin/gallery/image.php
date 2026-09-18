<?php
// Authenticated previews also work for drafts. Public photos are served statically by Apache.
require_once __DIR__ . '/_common.php';
require_method('GET');
$id = valid_id($_GET['id'] ?? null);
$photo = query('SELECT * FROM gallery_photos WHERE id = ?', [$id])->fetch();
if (!$photo) {
    fail_request('Photo introuvable.', 404);
}
$path = gallery_file((int) $photo['album_id'], $photo['thumbnail_filename']);
if (!is_file($path)) {
    fail_request('Photo indisponible.', 404);
}
header('Content-Type: ' . (str_ends_with($path, '.webp') ? 'image/webp' : 'image/jpeg'));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store');
session_write_close();
readfile($path);
