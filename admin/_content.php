<?php
// Small shared view for the two similar CRUD resources. Wrapper pages set both values.
require_once __DIR__ . '/_common.php';
if (!isset($resource, $mode) || !in_array($resource, ['events', 'training'], true)
    || !in_array($mode, ['index', 'create', 'edit', 'delete'], true)) {
    fail_request('Élément introuvable.', 404);
}
$isEvent = $resource === 'events';
$base = 'admin/' . $resource . '/';
$days = [1 => 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$title = $isEvent ? 'Événements' : 'Entraînements';
$error = '';
$id = in_array($mode, ['edit', 'delete'], true) ? valid_id($_GET['id'] ?? null) : null;
$data = $id ? find_record($resource, $id) : ($isEvent
    ? ['event_date' => '', 'title' => '', 'description' => '', 'location' => '', 'type' => '', 'is_published' => 0]
    : ['day_of_week' => 1, 'start_time' => '', 'end_time' => '', 'type' => '', 'location' => '', 'display_order' => 0, 'is_active' => 0]);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode !== 'index') {
    verifyCsrf();
    if ($mode === 'delete') {
        query("DELETE FROM $resource WHERE id = ?", [$id]);
        admin_notice('Suppression effectuée.', $base);
    }
    $data = $_POST;
    try {
        $clean = $isEvent ? event_input($data) : training_input($data);
        if ($mode === 'create') {
            $columns = implode(', ', array_keys($clean));
            $marks = implode(', ', array_fill(0, count($clean), '?'));
            query("INSERT INTO $resource ($columns) VALUES ($marks)", array_values($clean));
        } else {
            $assignments = implode(', ', array_map(static fn($key) => "$key = ?", array_keys($clean)));
            query("UPDATE $resource SET $assignments WHERE id = ?", [...array_values($clean), $id]);
        }
        admin_notice('Enregistrement effectué.', $base);
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: ' . ($mode === 'index' ? 'GET' : 'GET, POST'));
    fail_request('Method not allowed.', 405);
}
if ($mode === 'index') {
    $order = $isEvent ? 'event_date, id' : 'display_order, day_of_week, start_time, id';
    $rows = query("SELECT * FROM $resource ORDER BY $order")->fetchAll();
    admin_header($title);
    ?><div class="admin-toolbar"><a class="button button-dark" href="<?= h(app_url($base . 'create.php')) ?>"><?= $isEvent ? 'Nouvel événement' : 'Nouveau créneau' ?></a></div>
<?php if (!$rows): ?><p>Aucun élément pour le moment.</p><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th><?= $isEvent ? 'Date' : 'Jour / heure' ?></th><th><?= $isEvent ? 'Événement' : 'Entraînement' ?></th><th>Lieu</th><th>Statut<?= $isEvent ? '' : ' / ordre' ?></th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr>
<td><?= h($isEvent ? $row['event_date'] : $days[(int) $row['day_of_week']] . ' ' . substr($row['start_time'], 0, 5) . ' – ' . substr($row['end_time'], 0, 5)) ?></td>
<td><?= h($isEvent ? $row['title'] : $row['type']) ?></td><td><?= h($row['location']) ?></td>
<td><span class="admin-badge"><?= $isEvent ? ($row['is_published'] ? 'Publié' : 'Brouillon') : ($row['is_active'] ? 'Actif' : 'Inactif') ?></span><?= $isEvent ? '' : ' / ' . h($row['display_order']) ?></td>
<td><div class="admin-actions"><a href="<?= h(app_url($base . 'edit.php?id=' . $row['id'])) ?>">Modifier</a><a href="<?= h(app_url($base . 'delete.php?id=' . $row['id'])) ?>">Supprimer</a></div></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php
    admin_footer();
    return;
}
if ($mode === 'delete') {
    admin_header('Confirmer la suppression');
    ?><p>Supprimer « <?= h($isEvent ? $data['title'] : $data['type']) ?> » ? Cette action est définitive.</p>
<form method="post" class="admin-form" action="<?= h(app_url($base . 'delete.php?id=' . $id)) ?>"><?= csrf_field() ?>
<div class="admin-toolbar"><button type="submit" class="button admin-danger">Confirmer la suppression</button><a class="admin-link" href="<?= h(app_url($base)) ?>">Annuler</a></div></form>
<?php
    admin_footer();
    return;
}
admin_header(($mode === 'create' ? 'Créer · ' : 'Modifier · ') . $title);
?>
<?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" class="admin-form" action="<?= h(app_url($base . $mode . '.php' . ($id ? '?id=' . $id : ''))) ?>">
<?= csrf_field() ?>
<?php if ($isEvent): ?>
<label>Date<input type="date" name="event_date" required value="<?= h($data['event_date'] ?? '') ?>"></label>
<label>Titre<input name="title" maxlength="200" required dir="auto" value="<?= h($data['title'] ?? '') ?>"></label>
<label>Description<textarea name="description" rows="6" maxlength="5000" dir="auto"><?= h($data['description'] ?? '') ?></textarea></label>
<?php else: ?>
<label>Jour<select name="day_of_week"><?php foreach ($days as $number => $day): ?><option value="<?= $number ?>" <?= (string) ($data['day_of_week'] ?? '') === (string) $number ? 'selected' : '' ?>><?= h($day) ?></option><?php endforeach; ?></select></label>
<label>Début<input type="time" name="start_time" required value="<?= h($data['start_time'] ?? '') ?>"></label>
<label>Fin<input type="time" name="end_time" required value="<?= h($data['end_time'] ?? '') ?>"></label>
<?php endif; ?>
<label>Type / public<input name="type" maxlength="100" required dir="auto" value="<?= h($data['type'] ?? '') ?>"></label>
<label>Lieu<?= $isEvent ? '' : ' (facultatif)' ?><input name="location" maxlength="200" <?= $isEvent ? 'required' : '' ?> dir="auto" value="<?= h($data['location'] ?? '') ?>"></label>
<?php if (!$isEvent): ?><label>Ordre d’affichage<input type="number" name="display_order" min="0" max="9999" required value="<?= h($data['display_order'] ?? 0) ?>"><small>Les valeurs les plus petites apparaissent en premier.</small></label><?php endif; ?>
<?php $flag = $isEvent ? 'is_published' : 'is_active'; ?>
<label class="admin-check"><input type="checkbox" name="<?= h($flag) ?>" value="1" <?= !empty($data[$flag]) ? 'checked' : '' ?>><?= $isEvent ? 'Publié sur le site' : 'Créneau actif sur le site' ?></label>
<div class="admin-toolbar"><button class="button button-dark" type="submit">Enregistrer</button><a class="admin-link" href="<?= h(app_url($base)) ?>">Annuler</a></div>
</form>
<?php admin_footer(); ?>
