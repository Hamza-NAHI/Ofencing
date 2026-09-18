<?php
require_once __DIR__ . '/_common.php';
gallery_admin_action(static function (): void {
    $id = valid_id($_POST['album_id'] ?? null);
    $published = integer_field($_POST, 'is_published', 0, 1);
    gallery_publish_album($id, (bool) $published);
    admin_notice($published ? 'Album publié.' : 'Album dépublié.', 'admin/gallery/');
});
