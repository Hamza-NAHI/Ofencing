<?php
require_once __DIR__ . '/_common.php';
require_method('GET');
$id = valid_id($_GET['id'] ?? null);
$album = gallery_admin_album($id);
$photos = gallery_photos($id);
$cover = gallery_cover($album);
$gd = gallery_gd_status();
$uploadLimit = min(GALLERY_SOURCE_LIMIT, gallery_ini_bytes((string) ini_get('upload_max_filesize')) ?: GALLERY_SOURCE_LIMIT,
    max(0, (gallery_ini_bytes((string) ini_get('post_max_size')) ?: PHP_INT_MAX) - 65536));
admin_header($album['title']);
?>
<div class="admin-toolbar"><span class="admin-badge"><?= $album['is_published'] ? 'Publié' : 'Brouillon' ?></span><span><?= count($photos) ?> photo(s)</span>
<a class="admin-link" href="<?= h(app_url('admin/gallery/edit.php?id=' . $id)) ?>">Modifier l’album</a><a class="admin-link" href="<?= h(app_url('admin/gallery/')) ?>">Tous les albums</a>
<?php if ($album['is_published']): ?><a class="admin-link" href="<?= h(app_url('gallery.html?album=' . $id)) ?>">Voir sur le site</a><?php endif; ?></div>
<section class="admin-upload"><h2>Ajouter des photos</h2>
<p>Sélectionnez jusqu’à 50 photos JPEG, PNG ou WebP. Taille maximale par photo sur ce serveur : <?= h(round($uploadLimit / 1048576, 1)) ?> Mio. Les photos sont optimisées automatiquement.</p>
<?php if (!$gd['gd'] || !class_exists('finfo')): ?><p class="admin-error">Les extensions PHP GD et Fileinfo doivent être activées pour ajouter des photos.</p><?php endif; ?>
<form id="gallery-upload" class="admin-form" method="post" enctype="multipart/form-data" action="<?= h(app_url('admin/gallery/upload.php?id=' . $id)) ?>" data-max-bytes="<?= h($uploadLimit) ?>">
<?= csrf_field() ?><label>Photos<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" multiple required></label>
<button class="button button-dark" type="submit" <?= !$gd['gd'] || !class_exists('finfo') ? 'disabled' : '' ?>>Ajouter les photos</button>
</form><p id="upload-status" role="status" aria-live="polite"></p><ul id="upload-results" class="admin-upload-results"></ul>
<a id="upload-refresh" class="admin-link" href="<?= h(app_url('admin/gallery/album.php?id=' . $id)) ?>" hidden>Actualiser les photos de l’album</a>
<noscript><p>Sans JavaScript, limitez chaque sélection à <?= h(ini_get('max_file_uploads')) ?> photos et respectez la taille totale PHP indiquée ci-dessous.</p></noscript>
<details><summary>Limites de ce serveur</summary><p>GD : <?= $gd['gd'] ? 'actif' : 'absent' ?> · Sortie : <?= $gd['webp'] ? 'WebP' : ($gd['jpeg'] ? 'JPEG' : 'indisponible') ?> · Orientation EXIF : <?= $gd['exif'] ? 'active' : 'extension absente' ?></p><p>upload_max_filesize : <?= h(ini_get('upload_max_filesize')) ?> · post_max_size : <?= h(ini_get('post_max_size')) ?> · memory_limit : <?= h(ini_get('memory_limit')) ?> · max_file_uploads : <?= h(ini_get('max_file_uploads')) ?></p></details>
</section>
<?php if (!$photos): ?><p>Aucune photo dans cet album.</p><?php else: ?>
<div class="admin-gallery-grid">
<?php foreach ($photos as $position => $photo): ?><article class="admin-photo" id="photo-<?= h($photo['id']) ?>">
<img src="<?= h(gallery_admin_image($photo)) ?>" alt="<?= h($photo['alt_text'] ?: $album['title'] . ' — photo ' . ($position + 1)) ?>" width="<?= h($photo['width']) ?>" height="<?= h($photo['height']) ?>" loading="lazy">
<p class="admin-muted">Photo <?= $position + 1 ?> · <?= h($photo['width']) ?> × <?= h($photo['height']) ?> · <?= h(round(($photo['file_size'] + $photo['thumbnail_size']) / 1024)) ?> Kio</p>
<?php if ($cover && (int) $cover['id'] === (int) $photo['id']): ?><p class="admin-badge">Couverture<?= $album['cover_photo_id'] ? '' : ' automatique' ?></p><?php endif; ?>
<form class="admin-form" method="post" action="<?= h(app_url('admin/gallery/save-photo.php')) ?>"><?= gallery_hidden($id, (int) $photo['id']) ?>
<label>Légende<textarea name="caption" rows="2" maxlength="1000" dir="auto"><?= h($photo['caption']) ?></textarea></label>
<label>Description de l’image<input name="alt_text" maxlength="300" dir="auto" value="<?= h($photo['alt_text']) ?>"><small>Décrivez la scène pour les personnes qui ne peuvent pas voir l’image. Facultatif.</small></label>
<button class="button button-light" type="submit">Enregistrer les textes</button></form>
<div class="admin-actions">
<form method="post" action="<?= h(app_url('admin/gallery/reorder.php')) ?>"><?= gallery_hidden($id, (int) $photo['id']) ?><input type="hidden" name="direction" value="up"><button type="submit" <?= $position === 0 ? 'disabled' : '' ?> aria-label="Monter la photo <?= $position + 1 ?>">Monter</button></form>
<form method="post" action="<?= h(app_url('admin/gallery/reorder.php')) ?>"><?= gallery_hidden($id, (int) $photo['id']) ?><input type="hidden" name="direction" value="down"><button type="submit" <?= $position === count($photos) - 1 ? 'disabled' : '' ?> aria-label="Descendre la photo <?= $position + 1 ?>">Descendre</button></form>
<form method="post" action="<?= h(app_url('admin/gallery/set-cover.php')) ?>"><?= gallery_hidden($id, (int) $photo['id']) ?><button type="submit">Choisir comme couverture</button></form>
<a href="<?= h(app_url('admin/gallery/delete-photo.php?album=' . $id . '&id=' . $photo['id'])) ?>">Supprimer</a>
</div></article><?php endforeach; ?></div><?php endif; ?>
<script src="<?= h(app_url('admin/gallery/upload.js')) ?>"></script>
<?php admin_footer(); ?>
