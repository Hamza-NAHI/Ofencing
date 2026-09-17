<?php
require_once dirname(__DIR__) . '/_common.php';
require_method('GET');
$row = find_record('contact_messages', valid_id($_GET['id'] ?? null));
admin_header('Message de ' . $row['name']);
?>
<dl class="admin-detail"><dt>Contact</dt><dd dir="auto"><?= h($row['contact']) ?></dd><dt>Niveau</dt><dd><?= h($row['level']) ?></dd><dt>Reçu (UTC)</dt><dd><?= h($row['created_at']) ?></dd><dt>Statut</dt><dd><span class="admin-badge"><?= $row['is_read'] ? 'Lu' : 'Non lu' ?></span></dd></dl>
<div class="admin-message" dir="auto"><?= h($row['message'] ?: 'Aucun texte joint.') ?></div>
<div class="admin-pagination">
<form method="post" action="<?= h(app_url('admin/messages/toggle-read.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($row['id']) ?>"><input type="hidden" name="is_read" value="<?= $row['is_read'] ? '0' : '1' ?>"><button class="button button-dark" type="submit"><?= $row['is_read'] ? 'Marquer non lu' : 'Marquer lu' ?></button></form>
<a class="admin-link" href="<?= h(app_url('admin/messages/delete.php?id=' . $row['id'])) ?>">Supprimer</a><a class="admin-link" href="<?= h(app_url('admin/messages/')) ?>">Retour aux messages</a></div>
<?php admin_footer(); ?>
