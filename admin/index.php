<?php
require_once __DIR__ . '/_common.php';
require_method('GET');
$stats = [
    ['Tous les événements', query('SELECT COUNT(*) FROM events')->fetchColumn(), 'admin/events/'],
    ['Événements publiés à venir', query('SELECT COUNT(*) FROM events WHERE is_published = 1 AND event_date >= ?', [date('Y-m-d')])->fetchColumn(), 'admin/events/'],
    ['Créneaux actifs', query('SELECT COUNT(*) FROM training WHERE is_active = 1')->fetchColumn(), 'admin/training/'],
    ['Messages non lus', query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(), 'admin/messages/?filter=unread'],
];
admin_header('Tableau de bord');
?>
<p>Bonjour <?= h($_SESSION['username']) ?>. Gérez les événements, les entraînements et les demandes reçues.</p>
<div class="admin-cards"><?php foreach ($stats as [$label, $value, $path]): ?>
<a class="admin-card" href="<?= h(app_url($path)) ?>"><strong><?= h($value) ?></strong><?= h($label) ?></a>
<?php endforeach; ?></div>
<div class="admin-toolbar"><a class="button button-dark" href="<?= h(app_url('admin/events/create.php')) ?>">Nouvel événement</a><a class="button button-light" href="<?= h(app_url('admin/training/create.php')) ?>">Nouveau créneau</a></div>
<p class="admin-muted">Les événements publiés et les créneaux actifs apparaissent sur le site à son prochain chargement. Les dates des événements et les horaires sont ceux du Maroc ; les dates de réception des messages sont en UTC.</p>
<?php admin_footer(); ?>
