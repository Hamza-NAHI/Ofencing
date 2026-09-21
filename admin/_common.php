<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/validation.php';
start_session();
if (!defined('ADMIN_PUBLIC_PAGE')) {
    requireAdmin();
}
enforce_request_limit(defined('OFENCING_IMAGE_UPLOAD') ? 20 * 1024 * 1024 : 32768);

function admin_header(string $title, bool $navigation = true): void
{
    $notice = $_SESSION['notice'] ?? '';
    unset($_SESSION['notice']);
    ?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow"><title><?= h($title) ?> · ONescrime Admin</title>
<link rel="icon" href="<?= h(app_url('assets/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= h(app_url('styles2.css')) ?>"><link rel="stylesheet" href="<?= h(app_url('admin/admin.css')) ?>">
</head><body class="admin-body"><header class="admin-header shell">
<a class="brand" href="<?= h(app_url('admin/')) ?>"><img class="brand-logo" src="<?= h(app_url('assets/on-fencing-logo.svg')) ?>" alt="" width="120" height="36"><span>ONescrime · Admin</span></a>
<?php if ($navigation): ?><nav aria-label="Administration">
<a href="<?= h(app_url('admin/')) ?>">Tableau de bord</a><a href="<?= h(app_url('admin/events/')) ?>">Événements</a><a href="<?= h(app_url('admin/training/')) ?>">Entraînements</a><a href="<?= h(app_url('admin/messages/')) ?>">Messages</a><a href="<?= h(app_url('admin/gallery/')) ?>">Galerie</a>
<a href="<?= h(app_url()) ?>">Voir le site</a>
<form action="<?= h(app_url('admin/logout.php')) ?>" method="post"><?= csrf_field() ?><button type="submit" class="text-link">Déconnexion</button></form>
</nav><?php endif; ?></header><main class="shell admin-main"><h1><?= h($title) ?></h1>
<?php if ($notice): ?><p class="admin-notice" role="status"><?= h($notice) ?></p><?php endif;
}

function admin_footer(): void
{
    echo '</main><footer class="shell admin-footer">ONescrime · Administration privée</footer></body></html>';
}

function admin_notice(string $message, string $path): void
{
    $_SESSION['notice'] = $message;
    redirect_to($path);
}

function find_record(string $table, int $id): array
{
    if (!in_array($table, ['events', 'training', 'contact_messages'], true)) {
        throw new InvalidArgumentException('Invalid resource.');
    }
    $record = query("SELECT * FROM $table WHERE id = ?", [$id])->fetch();
    if (!$record) {
        fail_request('Élément introuvable.', 404);
    }
    return $record;
}
