<?php
define('ADMIN_PUBLIC_PAGE', true);
require_once __DIR__ . '/_common.php';
if (isAdminLoggedIn()) {
    redirect_to('admin/');
}
$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    rate_limit('login', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);
    try {
        $username = field($_POST, 'username', 64);
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || strlen($password) > 72 || $password === '' || str_contains($password, "\0")) {
            throw new InvalidArgumentException('Invalid password.');
        }
        $user = query('SELECT id, username, password_hash FROM admin_users WHERE username = ?', [$username])->fetch();
        // Verify a dummy hash for unknown usernames to keep the response comparable.
        $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($password, $hash) || !$user) {
            throw new InvalidArgumentException('Invalid credentials.');
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            query('UPDATE admin_users SET password_hash = ? WHERE id = ?', [$user['password_hash'], $user['id']]);
        }
        rate_limit('login', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900, 0);
        login_admin($user);
        redirect_to('admin/');
    } catch (InvalidArgumentException $exception) {
        security_log('login_failed');
        http_response_code(401);
        $error = 'Identifiant ou mot de passe incorrect.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, POST');
    fail_request('Method not allowed.', 405);
}
admin_header('Connexion', false);
?>
<?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" class="admin-form" action="<?= h(app_url('admin/login.php')) ?>">
<?= csrf_field() ?>
<label>Identifiant<input name="username" value="<?= h($username) ?>" maxlength="64" required autocomplete="username" autofocus></label>
<label>Mot de passe<input type="password" name="password" maxlength="72" required autocomplete="current-password"></label>
<button class="button button-dark" type="submit">Se connecter</button>
</form>
<?php admin_footer(); ?>
