<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('ofencing_' . substr(hash('sha256', dirname(__DIR__)), 0, 12));
    session_set_cookie_params([
        'lifetime' => 0, 'path' => app_url(),
        'secure' => request_is_https(),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    if (!session_start()) {
        throw new RuntimeException('Session storage unavailable.');
    }
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
}

function isAdminLoggedIn(): bool
{
    start_session();
    if (empty($_SESSION['admin_id'])) {
        return false;
    }
    if (time() - ($_SESSION['last_activity'] ?? 0) > 1800 || time() - ($_SESSION['login_time'] ?? 0) > 43200) {
        $_SESSION = [];
        session_regenerate_id(true);
        return false;
    }
    $user = query('SELECT id, password_hash FROM admin_users WHERE id = ?', [$_SESSION['admin_id']])->fetch();
    if (!$user || !isset($_SESSION['credential_stamp'])
        || !hash_equals(hash('sha256', $user['password_hash']), (string) $_SESSION['credential_stamp'])) {
        security_log('session_revoked');
        $_SESSION = [];
        session_regenerate_id(true);
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        if (defined('OFENCING_API')) {
            fail_request('Authentication required.', 401);
        }
        redirect_to('admin/login.php');
    }
}

function csrfToken(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf($token = null): void
{
    start_session();
    $token = $token ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        security_log('csrf_rejected');
        fail_request('Session expired or invalid security token. Reload the page and try again.', 403);
    }
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function login_admin(array $user): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION = ['admin_id' => (int) $user['id'], 'username' => $user['username'],
        'credential_stamp' => hash('sha256', $user['password_hash']),
        'login_time' => time(), 'last_activity' => time(), 'csrf' => bin2hex(random_bytes(32))];
}

function logout_admin(): void
{
    start_session();
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $cookie['path'],
        'secure' => $cookie['secure'], 'httponly' => true, 'samesite' => $cookie['samesite']]);
    session_destroy();
}
