<?php
require_once __DIR__ . '/_common.php';
$albumId = valid_id($_GET['album'] ?? null);
$photoId = valid_id($_GET['id'] ?? null);
$album = gallery_admin_album($albumId);
try {
    $photo = gallery_photo($photoId, $albumId);
} catch (OutOfBoundsException $error) {
    fail_request($error->getMessage(), 404);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $clean = gallery_delete($albumId, $photoId);
    admin_notice($clean ? 'Photo et miniature supprimées.' : 'Photo retirée ; nettoyage des fichiers incomplet. Vérifiez les droits du dossier uploads.', 'admin/gallery/album.php?id=' . $albumId);
}
require_method('GET');
admin_header('Supprimer la photo');
?>
<img class="admin-gallery-cover" src="<?= h(gallery_admin_image($photo)) ?>" alt="<?= h($photo['alt_text'] ?: $album['title']) ?>" width="160" height="120">
<p>Supprimer définitivement cette photo et sa miniature de « <?= h($album['title']) ?> » ?</p>
<form class="admin-form" method="post" action="<?= h(app_url('admin/gallery/delete-photo.php?album=' . $albumId . '&id=' . $photoId)) ?>"><?= csrf_field() ?><div class="admin-toolbar"><button class="button admin-danger" type="submit">Confirmer la suppression</button><a class="admin-link" href="<?= h(app_url('admin/gallery/album.php?id=' . $albumId)) ?>">Annuler</a></div></form>
<?php admin_footer(); ?>
