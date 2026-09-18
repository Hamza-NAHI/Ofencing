<?php
require_once __DIR__ . '/_common.php';
require_method('GET');
$albums = query('SELECT a.*, (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) AS photo_count FROM gallery_albums a ORDER BY a.display_order, a.created_at DESC, a.id DESC')->fetchAll();
admin_header('Galerie');
?>
<div class="admin-toolbar"><a class="button button-dark" href="<?= h(app_url('admin/gallery/create.php')) ?>">Nouvel album</a><a class="admin-link" href="<?= h(app_url('gallery.html')) ?>">Voir la galerie publique</a></div>
<?php if (!$albums): ?><p>Aucun album pour le moment. Créez votre premier album, puis ajoutez vos photos.</p><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Couverture</th><th>Album</th><th>Date / lieu</th><th>Statut / ordre</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($albums as $album): $cover = gallery_cover($album); ?><tr>
<td><?php if ($cover): ?><img class="admin-gallery-cover" src="<?= h(gallery_admin_image($cover)) ?>" alt="<?= h($cover['alt_text'] ?: $album['title']) ?>" width="100" height="75" loading="lazy"><?php else: ?><span class="admin-muted">Sans photo</span><?php endif; ?></td>
<td><a class="admin-link" href="<?= h(app_url('admin/gallery/album.php?id=' . $album['id'])) ?>"><?= h($album['title']) ?></a><br><?= h($album['photo_count']) ?> photo(s)</td>
<td><?= h($album['event_date'] ?: '—') ?><br><?= h($album['location']) ?></td>
<td><span class="admin-badge"><?= $album['is_published'] ? 'Publié' : 'Brouillon' ?></span> / <?= h($album['display_order']) ?></td>
<td><div class="admin-actions"><a href="<?= h(app_url('admin/gallery/album.php?id=' . $album['id'])) ?>">Ouvrir</a><a href="<?= h(app_url('admin/gallery/edit.php?id=' . $album['id'])) ?>">Modifier</a>
<form method="post" action="<?= h(app_url('admin/gallery/publish.php')) ?>"><?= gallery_hidden((int) $album['id']) ?><input type="hidden" name="is_published" value="<?= $album['is_published'] ? '0' : '1' ?>"><button type="submit"><?= $album['is_published'] ? 'Dépublier' : 'Publier' ?></button></form>
<a href="<?= h(app_url('admin/gallery/delete-album.php?id=' . $album['id'])) ?>">Supprimer</a></div></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php admin_footer(); ?>
