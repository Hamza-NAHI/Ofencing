<?php
require_once dirname(__DIR__) . '/_common.php';
$id = valid_id($_GET['id'] ?? null);
$row = find_record('contact_messages', $id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    query('DELETE FROM contact_messages WHERE id = ?', [$id]);
    admin_notice('Message supprimé.', 'admin/messages/');
} else {
    require_method('GET');
}
admin_header('Confirmer la suppression');
?>
<p>Supprimer définitivement le message de <?= h($row['name']) ?> reçu le <?= h($row['created_at']) ?> (UTC) ?</p>
<form class="admin-form" method="post" action="<?= h(app_url('admin/messages/delete.php?id=' . $id)) ?>"><?= csrf_field() ?>
<div class="admin-toolbar"><button class="button admin-danger" type="submit">Confirmer la suppression</button><a class="admin-link" href="<?= h(app_url('admin/messages/')) ?>">Annuler</a></div></form>
<?php admin_footer(); ?>
