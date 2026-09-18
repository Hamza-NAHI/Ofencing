<?php
declare(strict_types=1);
require_once __DIR__ . '/gallery.php';

const GALLERY_SOURCE_LIMIT = 15 * 1024 * 1024;
const GALLERY_PIXEL_LIMIT = 40000000;

function gallery_ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '-1' || $value === '0' || $value === '') {
        return 0; // Unlimited.
    }
    $power = ['k' => 1024, 'm' => 1048576, 'g' => 1073741824][strtolower(substr($value, -1))] ?? 1;
    return (int) ((float) $value * $power);
}

function gallery_gd_status(): array
{
    $gd = extension_loaded('gd');
    return ['gd' => $gd, 'webp' => $gd && function_exists('imagewebp') && (imagetypes() & IMG_WEBP),
        'jpeg' => $gd && function_exists('imagejpeg') && (imagetypes() & IMG_JPG),
        'exif' => function_exists('exif_read_data')];
}

function gallery_upload_name(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    if (!preg_match('//u', $name)) {
        return 'photo';
    }
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
    preg_match('/^.{0,250}/us', $name, $match);
    return $match[0] ?: 'photo';
}

function gallery_orient($image, string $path, string $mime)
{
    $orientation = 1;
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path, 'IFD0', true, false);
        $orientation = (int) ($exif['IFD0']['Orientation'] ?? 1);
    }
    if (in_array($orientation, [2, 5, 7], true)) {
        imageflip($image, IMG_FLIP_HORIZONTAL);
    } elseif ($orientation === 4) {
        imageflip($image, IMG_FLIP_VERTICAL);
    }
    $angles = [3 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90];
    if (isset($angles[$orientation])) {
        $rotated = imagerotate($image, $angles[$orientation], 0);
        if (!$rotated) {
            throw new RuntimeException('Image rotation failed.');
        }
        imagedestroy($image);
        return $rotated;
    }
    return $image;
}

function gallery_resize($source, int $longEdge, bool $transparent)
{
    $scale = min(1, $longEdge / max(imagesx($source), imagesy($source)));
    $width = max(1, (int) round(imagesx($source) * $scale));
    $height = max(1, (int) round(imagesy($source) * $scale));
    $image = imagecreatetruecolor($width, $height);
    if (!$image) {
        throw new RuntimeException('Image allocation failed.');
    }
    if ($transparent) {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    } else {
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    }
    if (!imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source))) {
        imagedestroy($image);
        throw new RuntimeException('Image resize failed.');
    }
    return $image;
}

function gallery_encode($image, string $path, bool $webp, int $quality): void
{
    $ok = $webp ? imagewebp($image, $path, $quality) : imagejpeg($image, $path, $quality);
    clearstatcache(true, $path);
    if (!$ok || !is_file($path) || filesize($path) === 0 || !@getimagesize($path)) {
        throw new RuntimeException('Image encoding failed.');
    }
}

function gallery_store_upload(int $albumId, array $file): array
{
    $status = gallery_gd_status();
    if (!$status['gd'] || (!$status['webp'] && !$status['jpeg'])) {
        throw new InvalidArgumentException('Le traitement des images est indisponible. Activez PHP GD avec WebP ou JPEG.');
    }
    if (!class_exists('finfo')) {
        throw new InvalidArgumentException('Activez l’extension PHP Fileinfo pour vérifier les images.');
    }
    $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($uploadError !== UPLOAD_ERR_OK) {
        $errors = [UPLOAD_ERR_INI_SIZE => 'Image trop volumineuse pour la limite PHP upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE => 'Image trop volumineuse.', UPLOAD_ERR_PARTIAL => 'Transfert incomplet. Réessayez cette photo.',
            UPLOAD_ERR_NO_FILE => 'Aucune image reçue.'];
        throw new InvalidArgumentException($errors[$uploadError] ?? 'Le serveur n’a pas pu recevoir cette image. Vérifiez son espace disque et ses droits.');
    }
    $path = $file['tmp_name'] ?? '';
    if (!is_string($path) || !is_uploaded_file($path)) {
        throw new InvalidArgumentException('Fichier de transfert invalide.');
    }
    $size = filesize($path);
    if (!$size || $size > GALLERY_SOURCE_LIMIT) {
        throw new InvalidArgumentException('Chaque image doit peser au maximum 15 Mio.');
    }
    $original = gallery_upload_name((string) ($file['name'] ?? ''));
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    $supported = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']];
    if (!isset($supported[$mime]) || !in_array($extension, $supported[$mime], true)) {
        throw new InvalidArgumentException('Formats acceptés : JPEG, PNG et WebP réels. HEIC, SVG et autres formats ne sont pas acceptés.');
    }
    $dimensions = @getimagesize($path);
    if (!$dimensions || ($dimensions['mime'] ?? '') !== $mime) {
        throw new InvalidArgumentException('Image invalide ou endommagée.');
    }
    [$width, $height] = $dimensions;
    if ($width < 1 || $height < 1 || max($width, $height) > 12000 || $width * $height > GALLERY_PIXEL_LIMIT) {
        throw new InvalidArgumentException('Image trop grande : maximum 40 mégapixels et 12 000 px par côté.');
    }
    // Conservative allowance for decoding, EXIF rotation, resized buffers and GD overhead.
    $required = $width * $height * 8 + 1920 * 1920 * 8 + 640 * 640 * 8 + 16 * 1024 * 1024;
    $memoryLimit = gallery_ini_bytes((string) ini_get('memory_limit'));
    if ($memoryLimit && memory_get_usage(true) + $required > $memoryLimit) {
        throw new InvalidArgumentException('Résolution trop élevée pour la mémoire PHP disponible. Réduisez l’image ou vérifiez memory_limit avec votre hébergeur.');
    }
    $decoder = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp'][$mime];
    if (!function_exists($decoder)) {
        throw new InvalidArgumentException('Cette installation GD ne peut pas lire ce format. Essayez un JPEG.');
    }
    $source = $large = $thumbnail = null;
    $files = [];
    try {
        $source = @$decoder($path);
        if (!$source) {
            throw new InvalidArgumentException('Image illisible ou endommagée.');
        }
        $source = gallery_orient($source, $path, $mime);
        $webp = (bool) $status['webp'];
        $large = gallery_resize($source, 1920, $webp);
        $thumbnail = gallery_resize($large, 640, $webp);
        $name = bin2hex(random_bytes(16));
        $suffix = $webp ? '.webp' : '.jpg';
        $filename = $name . $suffix;
        $thumbnailName = $name . '-thumb' . $suffix;
        $files = [gallery_file($albumId, $filename), gallery_file($albumId, $thumbnailName)];
        $result = gallery_write($albumId, static function (array $album) use ($albumId, $filename, $thumbnailName, $original, $large, $thumbnail, $webp, $files, $size): array {
            gallery_access($albumId, (bool) $album['is_published']);
            gallery_encode($large, $files[0], $webp, $webp ? 82 : 85);
            gallery_encode($thumbnail, $files[1], $webp, $webp ? 76 : 78);
            $order = (int) query('SELECT COALESCE(MAX(display_order), -1) + 1 FROM gallery_photos WHERE album_id = ?', [$albumId])->fetchColumn();
            query('INSERT INTO gallery_photos (album_id, filename, original_filename, thumbnail_filename, file_size, thumbnail_size, width, height, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$albumId, $filename, $original, $thumbnailName, filesize($files[0]), filesize($files[1]), imagesx($large), imagesy($large), $order]);
            return ['id' => (int) db()->lastInsertId(), 'name' => $original, 'success' => true,
                'width' => imagesx($large), 'height' => imagesy($large), 'source_bytes' => $size,
                'stored_bytes' => filesize($files[0]) + filesize($files[1]), 'format' => $webp ? 'webp' : 'jpeg'];
        });
        return $result;
    } catch (Throwable $error) {
        foreach ($files as $stored) {
            if (is_file($stored)) {
                @unlink($stored);
            }
        }
        throw $error;
    } finally {
        foreach ([$thumbnail, $large, $source] as $image) {
            if ($image instanceof GdImage) {
                imagedestroy($image);
            }
        }
        // The camera original is never moved into uploads; release PHP's temporary copy now.
        if (is_uploaded_file($path)) {
            @unlink($path);
        }
    }
}
