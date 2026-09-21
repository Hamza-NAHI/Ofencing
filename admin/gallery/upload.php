<?php
define('OFENCING_IMAGE_UPLOAD', true);
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
if ($wantsJson) {
    define('OFENCING_API', true);
}
require_once __DIR__ . '/_common.php';
require_method('POST');
$postLimit = gallery_ini_bytes((string) ini_get('post_max_size'));
if ($postLimit && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $postLimit) {
    fail_request('Transfert trop volumineux pour la limite PHP post_max_size.', 413);
}
verifyCsrf();
$id = valid_id($_GET['id'] ?? null);
gallery_admin_album($id);
// Shared by sessions for this administrator; fresh cookies cannot reset the budget.
rate_limit('upload_requests', (string) $_SESSION['admin_id'], 120, 600);
$uploaded = $_FILES['photos'] ?? null;
if (!$uploaded || !isset($uploaded['name']) || !is_array($uploaded['name']) || count($uploaded['name']) < 1 || count($uploaded['name']) > 50) {
    fail_request('Sélectionnez entre 1 et 50 photos.', 422);
}
// Charge every submitted file before GD decoding, including rejected images.
rate_limit('upload_images', (string) $_SESSION['admin_id'], 100, 600, count($uploaded['name']));
$results = [];
foreach ($uploaded['name'] as $index => $name) {
    $file = [];
    foreach (['name', 'error', 'tmp_name', 'size'] as $key) {
        $file[$key] = $uploaded[$key][$index] ?? null;
    }
    if (!is_string($name) || !is_int($file['error']) || !is_string($file['tmp_name'])) {
        $results[] = ['name' => 'photo', 'success' => false, 'error' => 'Transfert invalide.'];
        continue;
    }
    try {
        $results[] = gallery_store_upload($id, $file);
    } catch (InvalidArgumentException | OutOfBoundsException $error) {
        security_log('upload_rejected', ['admin_id' => (int) $_SESSION['admin_id']]);
        $results[] = ['name' => gallery_upload_name($name), 'success' => false, 'error' => $error->getMessage()];
    } catch (Throwable $error) {
        security_log('upload_failed', ['exception' => get_class($error), 'admin_id' => (int) $_SESSION['admin_id']]);
        $results[] = ['name' => gallery_upload_name($name), 'success' => false, 'error' => 'Cette photo n’a pas pu être enregistrée. Vérifiez la configuration et l’espace disponible.'];
    }
}
$successes = count(array_filter($results, static fn($result) => $result['success']));
$failures = count($results) - $successes;
if ($wantsJson) {
    $body = ['success' => $successes > 0, 'data' => ['uploaded' => $successes, 'failed' => $failures, 'results' => $results]];
    if (!$successes) {
        $body['error'] = $results[0]['error'] ?? 'Aucune photo reçue.';
    }
    json_response($body, $successes ? ($failures ? 200 : 201) : 422);
}
$_SESSION['notice'] = $successes . ' photo(s) ajoutée(s), ' . $failures . ' échec(s). '
    . implode(' ', array_map(static fn($result) => $result['success'] ? '' : $result['name'] . ' : ' . $result['error'], $results));
redirect_to('admin/gallery/album.php?id=' . $id);
