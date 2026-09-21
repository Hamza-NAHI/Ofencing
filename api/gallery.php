<?php
require_once __DIR__ . '/_common.php';
require_once dirname(__DIR__) . '/config/gallery.php';
require_method('GET');

function public_gallery_photo(array $photo): array
{
    return ['caption' => $photo['caption'], 'alt_text' => $photo['alt_text'],
        'thumbnail_url' => gallery_image_url($photo), 'image_url' => gallery_image_url($photo, false)];
}

function public_gallery_album(array $album, int $count, ?array $cover, bool $detail = false): array
{
    $data = ['id' => (int) $album['id'], 'title' => $album['title'], 'location' => $album['location'],
        'photo_count' => $count, 'cover' => $cover ? ['thumbnail_url' => gallery_image_url($cover), 'alt_text' => $cover['alt_text']] : null];
    if ($detail) {
        $data['description'] = $album['description'];
        $data['event_date'] = $album['event_date'];
    }
    return $data;
}

// Consistent read: an album cannot become a draft between the publication check and photo query.
db()->beginTransaction();
if (isset($_GET['album'])) {
    $id = valid_id($_GET['album']);
    $album = query('SELECT * FROM gallery_albums WHERE id = ? AND is_published = 1', [$id])->fetch();
    if (!$album) {
        db()->rollBack();
        fail_request('Album not found.', 404);
    }
    $photos = gallery_photos($id);
    $data = ['album' => public_gallery_album($album, count($photos), gallery_cover($album), true),
        'photos' => array_map('public_gallery_photo', $photos)];
} else {
    // Counts and cover selection in one query; no N+1 full-album reads.
    $albums = query('SELECT a.*, (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) AS photo_count,
        COALESCE((SELECT p.id FROM gallery_photos p WHERE p.id = a.cover_photo_id AND p.album_id = a.id),
        (SELECT p.id FROM gallery_photos p WHERE p.album_id = a.id ORDER BY p.display_order, p.id LIMIT 1)) AS chosen_cover
        FROM gallery_albums a WHERE a.is_published = 1 ORDER BY a.display_order, a.created_at DESC, a.id DESC')->fetchAll();
    $covers = [];
    $ids = array_values(array_filter(array_column($albums, 'chosen_cover')));
    if ($ids) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        foreach (query("SELECT * FROM gallery_photos WHERE id IN ($marks)", $ids)->fetchAll() as $photo) {
            $covers[$photo['id']] = $photo;
        }
    }
    $data = array_map(static fn($album) => public_gallery_album($album, (int) $album['photo_count'], $covers[$album['chosen_cover']] ?? null), $albums);
}
db()->commit();
json_response(['success' => true, 'data' => $data]);
