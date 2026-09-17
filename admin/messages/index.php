<?php
require_once dirname(__DIR__) . '/_common.php';
require_method('GET');
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'unread', 'read'], true)) {
    fail_request('Filtre invalide.', 422);
}
$page = valid_id($_GET['page'] ?? '1');
$where = $filter === 'all' ? '' : ' WHERE is_read = ?';
$parameters = $filter === 'all' ? [] : [$filter === 'read' ? 1 : 0];
$total = (int) query('SELECT COUNT(*) FROM contact_messages' . $where, $parameters)->fetchColumn();
$pages = max(1, (int) ceil($total / 50));
$page = min($page, $pages);
$offset = ($page - 1) * 50;
// The offset is calculated from validated integers, never interpolated from input.
$rows = query('SELECT id, name, contact, level, is_read, created_at FROM contact_messages' . $where . " ORDER BY created_at DESC, id DESC LIMIT 50 OFFSET $offset", $parameters)->fetchAll();
admin_header('Messages');
?>
<nav class="admin-toolbar" aria-label="Filtrer les messages">
<?php foreach (['all' => 'Tous', 'unread' => 'Non lus', 'read' => 'Lus'] as $key => $label): ?><a class="admin-link" href="<?= h(app_url('admin/messages/?filter=' . $key)) ?>" <?= $filter === $key ? 'aria-current="page"' : '' ?>><?= h($label) ?></a><?php endforeach; ?>
<span><?= h($total) ?> message(s) · plus récents en premier</span></nav>
<?php if (!$rows): ?><p>Aucun message dans cette sélection.</p><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Reçu (UTC)</th><th>Nom / contact</th><th>Niveau</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr class="<?= $row['is_read'] ? '' : 'admin-unread' ?>">
<td><?= h($row['created_at']) ?></td><td><?= h($row['name']) ?><br><span dir="auto"><?= h($row['contact']) ?></span></td><td><?= h($row['level']) ?></td>
<td><span class="admin-badge"><?= $row['is_read'] ? 'Lu' : 'Non lu' ?></span></td>
<td><div class="admin-actions"><a href="<?= h(app_url('admin/messages/view.php?id=' . $row['id'])) ?>">Ouvrir</a>
<form method="post" action="<?= h(app_url('admin/messages/toggle-read.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($row['id']) ?>"><input type="hidden" name="is_read" value="<?= $row['is_read'] ? '0' : '1' ?>"><button type="submit"><?= $row['is_read'] ? 'Marquer non lu' : 'Marquer lu' ?></button></form>
<a href="<?= h(app_url('admin/messages/delete.php?id=' . $row['id'])) ?>">Supprimer</a></div></td></tr><?php endforeach; ?>
</tbody></table></div>
<nav class="admin-pagination" aria-label="Pagination">
<?php if ($page > 1): ?><a class="admin-link" href="<?= h(app_url('admin/messages/?filter=' . $filter . '&page=' . ($page - 1))) ?>">Précédent</a><?php endif; ?>
<span>Page <?= h($page) ?> / <?= h($pages) ?></span>
<?php if ($page < $pages): ?><a class="admin-link" href="<?= h(app_url('admin/messages/?filter=' . $filter . '&page=' . ($page + 1))) ?>">Suivant</a><?php endif; ?></nav>
<?php endif; admin_footer(); ?>
