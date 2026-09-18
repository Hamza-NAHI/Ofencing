<?php
require_once __DIR__ . '/_common.php';
$id = valid_id($_GET['id'] ?? null);
$album = gallery_admin_album($id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $clean = gallery_delete($id);
    admin_notice($clean ? 'Album, photos et miniatures supprimés.' : 'Album retiré ; nettoyage des fichiers incomplet. Vérifiez les droits du dossier uploads.', 'admin/gallery/');
}
require_method('GET');
$count = (int) query('SELECT COUNT(*) FROM gallery_photos WHERE album_id = ?', [$id])->fetchColumn();
admin_header('Supprimer l’album');
?>
<p>Supprimer définitivement « <?= h($album['title']) ?> », ses <?= h($count) ?> photo(s) et toutes leurs miniatures ?</p>
<form class="admin-form" method="post" action="<?= h(app_url('admin/gallery/delete-album.php?id=' . $id)) ?>"><?= csrf_field() ?><div class="admin-toolbar"><button class="button admin-danger" type="submit">Confirmer la suppression</button><a class="admin-link" href="<?= h(app_url('admin/gallery/album.php?id=' . $id)) ?>">Annuler</a></div></form>
<?php admin_footer(); ?>
