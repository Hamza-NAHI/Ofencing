<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/validation.php';

function gallery_album(int $id, bool $lock = false): array
{
    $row = query('SELECT * FROM gallery_albums WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id])->fetch();
    if (!$row) {
        throw new OutOfBoundsException('Album introuvable.');
    }
    return $row;
}

function gallery_photo(int $id, int $albumId): array
{
    $row = query('SELECT * FROM gallery_photos WHERE id = ? AND album_id = ?', [$id, $albumId])->fetch();
    if (!$row) {
        throw new OutOfBoundsException('Photo introuvable dans cet album.');
    }
    return $row;
}

function gallery_photos(int $albumId): array
{
    return query('SELECT * FROM gallery_photos WHERE album_id = ? ORDER BY display_order, id', [$albumId])->fetchAll();
}

function gallery_cover(array $album): ?array
{
    if ($album['cover_photo_id']) {
        $cover = query('SELECT * FROM gallery_photos WHERE album_id = ? AND id = ?', [$album['id'], $album['cover_photo_id']])->fetch();
        if ($cover) {
            return $cover;
        }
    }
    return query('SELECT * FROM gallery_photos WHERE album_id = ? ORDER BY display_order, id LIMIT 1', [$album['id']])->fetch() ?: null;
}

function gallery_album_input(array $input): array
{
    $date = field($input, 'event_date', 10, false);
    if ($date !== '') {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < '1000-01-01') {
            throw new InvalidArgumentException('Date invalide. Utilisez AAAA-MM-JJ.');
        }
    }
    return ['title' => field($input, 'title', 200), 'description' => field($input, 'description', 5000, false),
        'event_date' => $date ?: null, 'location' => field($input, 'location', 200, false),
        'is_published' => flag_field($input, 'is_published'),
        'display_order' => integer_field($input, 'display_order', 0, 9999, 0)];
}

function gallery_directory(int $albumId): string
{
    if ($albumId < 1) {
        throw new InvalidArgumentException('Album invalide.');
    }
    $root = dirname(__DIR__) . '/uploads/gallery';
    $directory = $root . '/album-' . $albumId;
    if (is_link(dirname($root)) || is_link($root) || is_link($directory)) {
        throw new RuntimeException('Invalid gallery storage.');
    }
    return $directory;
}

function gallery_file(int $albumId, string $filename): string
{
    if (!preg_match('/^[a-f0-9]{32}(?:-thumb)?\.(?:webp|jpg)$/D', $filename)) {
        throw new RuntimeException('Invalid stored filename.');
    }
    $path = gallery_directory($albumId) . '/' . $filename;
    if (is_link($path)) {
        throw new RuntimeException('Invalid gallery file.');
    }
    return $path;
}

function gallery_image_url(array $photo, bool $thumbnail = true): string
{
    $filename = $photo[$thumbnail ? 'thumbnail_filename' : 'filename'];
    gallery_file((int) $photo['album_id'], $filename); // Validate the stored name before exposing a URL.
    return app_url('uploads/gallery/album-' . (int) $photo['album_id'] . '/' . $filename);
}

function gallery_access(int $albumId, bool $published): void
{
    $directory = gallery_directory($albumId);
    if (!is_file(dirname(__DIR__) . '/uploads/.htaccess')) {
        throw new RuntimeException('Upload protection is missing.');
    }
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create album directory.');
    }
    if (is_link($directory . '/.htaccess')) {
        throw new RuntimeException('Invalid album access file.');
    }
    $temporary = $directory . '/.access-' . bin2hex(random_bytes(8));
    $content = "# Managed by ONescrime. Do not edit.\nRequire all " . ($published ? 'granted' : 'denied') . "\n";
    try {
        if (file_put_contents($temporary, $content, LOCK_EX) !== strlen($content)
            || !rename($temporary, $directory . '/.htaccess')) {
            throw new RuntimeException('Cannot update album access.');
        }
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
}

// Serialize upload/order/cover/edit/delete operations on the same album.
function gallery_write(int $albumId, callable $operation, ?callable $onFailure = null)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $album = gallery_album($albumId, true);
        $result = $operation($album);
        $pdo->commit();
        return $result;
    } catch (Throwable $error) {
        try {
            // Restore/remove staged files BEFORE releasing the album row lock.
            if ($onFailure) {
                $onFailure();
            }
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
        throw $error;
    }
}

function gallery_create_album(array $data): int
{
    // Slugs are descriptive metadata; numeric IDs make folder names stable after edits.
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($data['title'])), '-');
    $slug = substr($base ?: 'album', 0, 190) . '-' . bin2hex(random_bytes(8));
    $id = 0;
    $createdDirectory = false;
    db()->beginTransaction();
    try {
        query('INSERT INTO gallery_albums (title, description, event_date, location, is_published, display_order, slug) VALUES (?, ?, ?, ?, ?, ?, ?)', [...array_values($data), $slug]);
        $id = (int) db()->lastInsertId();
        if (is_dir(gallery_directory($id))) {
            throw new RuntimeException('Album directory already exists.');
        }
        $createdDirectory = true;
        gallery_access($id, (bool) $data['is_published']);
        db()->commit();
        return $id;
    } catch (Throwable $error) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        if ($createdDirectory && is_dir(gallery_directory($id))) {
            @unlink(gallery_directory($id) . '/.htaccess');
            @rmdir(gallery_directory($id));
        }
        throw $error;
    }
}

function gallery_update_album(int $id, array $data): void
{
    gallery_write($id, static function () use ($id, $data): void {
        if (!$data['is_published']) {
            gallery_access($id, false);
        }
        query('UPDATE gallery_albums SET title = ?, description = ?, event_date = ?, location = ?, is_published = ?, display_order = ? WHERE id = ?', [...array_values($data), $id]);
    });
    if ($data['is_published']) {
        gallery_sync_access($id);
    }
}

function gallery_publish_album(int $id, bool $published): void
{
    gallery_write($id, static function () use ($id, $published): void {
        if (!$published) {
            gallery_access($id, false);
        }
        query('UPDATE gallery_albums SET is_published = ? WHERE id = ?', [(int) $published, $id]);
    });
    if ($published) {
        gallery_sync_access($id);
    }
}

function gallery_sync_access(int $id): void
{
    // Open access only AFTER the DB commit. Re-lock/re-read to respect a concurrent unpublish.
    // Interruption here leaves a published album temporarily inaccessible, never a public draft.
    gallery_write($id, static function (array $album) use ($id): void {
        gallery_access($id, (bool) $album['is_published']);
    });
}

function gallery_remove_empty_directory(int $id): bool
{
    $dir = gallery_directory($id);
    if (!is_dir($dir)) {
        return true;
    }
    $otherFiles = array_diff(scandir($dir), ['.', '..', '.htaccess']);
    if ($otherFiles) {
        return false;
    }
    return (!is_file($dir . '/.htaccess') || @unlink($dir . '/.htaccess')) && @rmdir($dir);
}

function gallery_delete(int $albumId, ?int $photoId = null): bool
{
    // Stage files privately on the same filesystem so a DB failure can restore them.
    $moved = [];
    $trash = dirname(__DIR__) . '/uploads/gallery/.trash-' . bin2hex(random_bytes(16));
    $restored = true;
    $restore = static function () use (&$moved, &$restored): void {
        foreach ($moved as $source => $target) {
            if (!@rename($target, $source)) {
                $restored = false;
                security_log('gallery_recovery_required');
            }
        }
    };
    try {
        gallery_write($albumId, static function () use ($albumId, $photoId, $trash, &$moved): void {
            $photos = $photoId ? [gallery_photo($photoId, $albumId)] : gallery_photos($albumId);
            if (!mkdir($trash, 0700)) {
                throw new RuntimeException('Cannot stage deletion.');
            }
            // Hidden paths are denied by the root rules; this also guards direct aliases.
            if (file_put_contents($trash . '/.htaccess', "Require all denied\n") === false) {
                throw new RuntimeException('Cannot protect deletion files.');
            }
            if ($photoId) {
                query('UPDATE gallery_albums SET cover_photo_id = NULL WHERE id = ? AND cover_photo_id = ?', [$albumId, $photoId]);
                query('DELETE FROM gallery_photos WHERE id = ? AND album_id = ?', [$photoId, $albumId]);
            } else {
                query('DELETE FROM gallery_albums WHERE id = ?', [$albumId]);
            }
            foreach ($photos as $photo) {
                foreach (['filename', 'thumbnail_filename'] as $key) {
                    $source = gallery_file($albumId, $photo[$key]);
                    if (is_file($source)) {
                        $target = $trash . '/' . $photo[$key];
                        if (!rename($source, $target)) {
                            throw new RuntimeException('Cannot stage image removal.');
                        }
                        $moved[$source] = $target;
                    }
                }
            }
        }, $restore);
    } catch (Throwable $error) {
        if ($restored) {
            @unlink($trash . '/.htaccess');
            @rmdir($trash);
        }
        throw $error;
    }
    $clean = true;
    foreach ($moved as $target) {
        $clean = @unlink($target) && $clean;
    }
    if ($clean) {
        $clean = @unlink($trash . '/.htaccess') && @rmdir($trash);
    }
    if (!$photoId) {
        $clean = gallery_remove_empty_directory($albumId) && $clean;
    }
    if (!$clean) {
        error_log('ONescrime gallery: file cleanup incomplete; check upload permissions.');
    }
    return $clean;
}
