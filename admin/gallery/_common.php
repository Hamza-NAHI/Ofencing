<?php
require_once dirname(__DIR__) . '/_common.php';
require_once dirname(__DIR__, 2) . '/config/gallery-images.php';

function gallery_admin_album(int $id): array
{
    try {
        return gallery_album($id);
    } catch (OutOfBoundsException $error) {
        fail_request($error->getMessage(), 404);
    }
}

function gallery_admin_action(callable $action): void
{
    require_method('POST');
    verifyCsrf();
    try {
        $action();
    } catch (OutOfBoundsException $error) {
        fail_request($error->getMessage(), 404);
    } catch (InvalidArgumentException $error) {
        fail_request($error->getMessage(), 422);
    }
}

function gallery_hidden(int $albumId, ?int $photoId = null): string
{
    return csrf_field() . '<input type="hidden" name="album_id" value="' . h($albumId) . '">'
        . ($photoId ? '<input type="hidden" name="photo_id" value="' . h($photoId) . '">' : '');
}

function gallery_admin_image(array $photo): string
{
    return app_url('admin/gallery/image.php?id=' . (int) $photo['id']);
}
