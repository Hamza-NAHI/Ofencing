<?php
// Shared helpers. No framework or third-party runtime dependencies.
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

function settings(): array
{
    static $settings;
    if ($settings === null) {
        $defaults = [
            'DB_HOST' => 'localhost', 'DB_PORT' => '3306', 'DB_NAME' => 'DB_NAME',
            'DB_USER' => 'DB_USER', 'DB_PASS' => 'DB_PASS',
            'APP_TIMEZONE' => 'Africa/Casablanca', 'APP_BASE_PATH' => null,
            'SETUP_TOKEN' => '', // Disabled until explicitly configured. See README.
        ];
        $local = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
        $settings = array_replace($defaults, is_array($local) ? $local : []);
        foreach ($defaults as $key => $value) {
            $env = getenv($key);
            if ($env !== false) {
                $settings[$key] = $env;
            }
        }
    }
    return $settings;
}

function app_url(string $path = ''): string
{
    $base = settings()['APP_BASE_PATH'];
    if ($base === null) {
        $root = str_replace('\\', '/', dirname(__DIR__));
        $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
        $relative = substr($script, strlen($root));
        $url = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = $relative !== '' && str_ends_with($url, $relative)
            ? substr($url, 0, -strlen($relative)) : '';
    }
    return rtrim((string) $base, '/') . '/' . ltrim($path, '/');
}

function h($value): string
{
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect_to(string $path): void
{
    header('Location: ' . app_url($path), true, 303);
    exit;
}

function json_response(array $body, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function fail_request(string $message, int $status): void
{
    if (defined('OFENCING_API')) {
        json_response(['success' => false, 'error' => $message], $status);
    }
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ONescrime</title><body><h1>ONescrime</h1><p>' . h($message) . '</p><p><a href="' . h(app_url('admin/')) . '">Retour à l’administration</a></p></body></html>';
    exit;
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        fail_request('Method not allowed.', 405);
    }
}

function valid_id($value): int
{
    if (!is_scalar($value) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $value)
        || (float) $value > 2147483647) {
        fail_request('Invalid ID.', 422);
    }
    return (int) $value;
}

// IPs come from the web server, never from client-supplied forwarding headers.
// One private, locked file per application; old entries are pruned on every use.
function rate_limit(string $scope, string $key, int $limit, int $window): void
{
    $directory = sys_get_temp_dir() . '/ofencing-' . substr(hash('sha256', dirname(__DIR__)), 0, 20);
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create rate-limit directory.');
    }
    $file = fopen($directory . '/limits.json', 'c+');
    if (!$file || !flock($file, LOCK_EX)) {
        throw new RuntimeException('Cannot lock rate limiter.');
    }
    try {
        $state = json_decode(stream_get_contents($file), true) ?: [];
        $now = time();
        $state = array_filter($state, static fn($entry) => $entry['until'] > $now);
        $bucket = hash('sha256', $scope . ':' . $key);
        $entry = $state[$bucket] ?? ['count' => 0, 'until' => $now + $window];
        $blocked = $entry['count'] >= $limit;
        if (!$blocked) {
            $entry['count']++;
            $state[$bucket] = $entry;
        }
        rewind($file);
        if (!ftruncate($file, 0) || fwrite($file, json_encode($state, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Cannot persist rate limit.');
        }
        fflush($file);
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
    if ($blocked) {
        header('Retry-After: ' . max(1, $entry['until'] - $now));
        fail_request('Too many attempts. Please try again later.', 429);
    }
}

set_exception_handler(static function (Throwable $error): void {
    // Deliberately omit exception text/SQL/credentials from public output and logs.
    error_log('ONescrime: ' . get_class($error) . ' at ' . basename($error->getFile()) . ':' . $error->getLine());
    fail_request(defined('OFENCING_API') ? 'Service temporarily unavailable. Please try again later.' : 'Service temporairement indisponible. Réessayez plus tard ; si le problème persiste, vérifiez la configuration PHP / MySQL.', 503);
});
date_default_timezone_set(settings()['APP_TIMEZONE']);
