<?php
// One-time setup, disabled by default and permanently closed when an admin exists.
define('ADMIN_PUBLIC_PAGE', true);
require_once __DIR__ . '/_common.php';
$secret = (string) settings()['SETUP_TOKEN'];
if (strlen($secret) < 32 || query('SELECT id FROM admin_users LIMIT 1')->fetch()) {
    fail_request('Configuration initiale désactivée.', 404);
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    rate_limit('setup', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 5, 900);
    $provided = $_POST['setup_token'] ?? '';
    if (!is_string($provided) || !hash_equals($secret, $provided)) {
        fail_request('Jeton de configuration incorrect.', 403);
    }
    try {
        $username = field($_POST, 'username', 64);
        if (!preg_match('/^[A-Za-z0-9_.-]{3,64}$/D', $username)) {
            throw new InvalidArgumentException('Identifiant : 3 à 64 lettres, chiffres, points, tirets ou underscores.');
        }
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new InvalidArgumentException('Choisissez un mot de passe de 12 à 72 octets.');
        }
        if ($password !== ($_POST['password_confirm'] ?? '')) {
            throw new InvalidArgumentException('Les mots de passe ne correspondent pas.');
        }
        // Serialize setup so two simultaneous requests cannot create two first users.
        $lock = 'ofencing-setup-' . substr(hash('sha256', (string) settings()['DB_NAME']), 0, 32);
        if ((int) query('SELECT GET_LOCK(?, 5)', [$lock])->fetchColumn() !== 1) {
            throw new RuntimeException('Setup lock unavailable.');
        }
        try {
            if (query('SELECT id FROM admin_users LIMIT 1')->fetch()) {
                throw new InvalidArgumentException('Un administrateur existe déjà. Connectez-vous.');
            }
            query('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)', [$username, password_hash($password, PASSWORD_DEFAULT)]);
        } finally {
            query('SELECT RELEASE_LOCK(?)', [$lock]);
        }
        admin_notice('Compte créé. Supprimez SETUP_TOKEN de config/local.php, puis connectez-vous.', 'admin/login.php');
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, POST');
    fail_request('Method not allowed.', 405);
}
admin_header('Premier administrateur', false);
?>
<p>Utilisez le jeton temporaire défini dans votre configuration. Cette page se désactive dès la création du compte.</p>
<?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" class="admin-form" action="<?= h(app_url('admin/setup.php')) ?>">
<?= csrf_field() ?>
<label>Jeton de configuration<input type="password" name="setup_token" required autocomplete="off"></label>
<label>Identifiant<input name="username" required minlength="3" maxlength="64" pattern="[A-Za-z0-9_.\-]{3,64}" autocomplete="username"></label>
<label>Mot de passe<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password"><small>Au moins 12 caractères ; 72 octets maximum.</small></label>
<label>Confirmer le mot de passe<input type="password" name="password_confirm" required minlength="12" maxlength="72" autocomplete="new-password"></label>
<button class="button button-dark" type="submit">Créer le compte</button>
</form>
<?php admin_footer(); ?>
