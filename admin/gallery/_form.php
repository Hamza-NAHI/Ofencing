<?php
require_once __DIR__ . '/_common.php';
if (!isset($editing)) {
    fail_request('Not found.', 404);
}
$id = $editing ? valid_id($_GET['id'] ?? null) : null;
$album = $id ? gallery_admin_album($id) : ['title' => '', 'description' => '', 'event_date' => '', 'location' => '', 'is_published' => 0, 'display_order' => 0];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $album = $_POST;
    try {
        $data = gallery_album_input($album);
        if ($id) {
            gallery_update_album($id, $data);
        } else {
            $id = gallery_create_album($data);
        }
        admin_notice('Album enregistré.', 'admin/gallery/album.php?id=' . $id);
    } catch (InvalidArgumentException $errorObject) {
        http_response_code(422);
        $error = $errorObject->getMessage();
    } catch (OutOfBoundsException $errorObject) {
        fail_request($errorObject->getMessage(), 404);
    }
} else {
    require_method('GET');
}
admin_header($editing ? 'Modifier l’album' : 'Nouvel album');
?>
<?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<form class="admin-form" method="post" action="<?= h(app_url('admin/gallery/' . ($editing ? 'edit.php?id=' . $id : 'create.php'))) ?>">
<?= csrf_field() ?>
<label>Titre<input name="title" dir="auto" maxlength="200" required value="<?= h($album['title'] ?? '') ?>"></label>
<label>Description<textarea name="description" dir="auto" rows="5" maxlength="5000"><?= h($album['description'] ?? '') ?></textarea></label>
<label>Date (facultative)<input type="date" name="event_date" value="<?= h($album['event_date'] ?? '') ?>"></label>
<label>Lieu (facultatif)<input name="location" dir="auto" maxlength="200" value="<?= h($album['location'] ?? '') ?>"></label>
<label>Ordre d’affichage<input type="number" name="display_order" min="0" max="9999" required value="<?= h($album['display_order'] ?? 0) ?>"><small>Le plus petit nombre apparaît en premier. À ordre égal, le dernier album créé apparaît en premier.</small></label>
<label class="admin-check"><input type="checkbox" name="is_published" value="1" <?= !empty($album['is_published']) ? 'checked' : '' ?>>Publié dans la galerie</label>
<div class="admin-toolbar"><button class="button button-dark" type="submit">Enregistrer l’album</button><a class="admin-link" href="<?= h(app_url('admin/gallery/')) ?>">Annuler</a></div>
</form>
<?php admin_footer(); ?>
