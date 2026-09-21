<?php
declare(strict_types=1);

// No proxy headers or Host-based localhost exceptions: these are client-controlled.
function request_is_https(): bool
{
    return isset($_SERVER['HTTPS']) && in_array(strtolower((string) $_SERVER['HTTPS']), ['on', '1'], true);
}

function security_headers(): void
{
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; script-src-attr 'none'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    header('Cache-Control: no-store');
}

function security_log(string $event, array $context = []): void
{
    // Only call-site enums/diagnostic locations, never passwords, tokens, SQL or user text.
    $record = ['event' => preg_replace('/[^a-z0-9_]/', '', $event)];
    foreach (['scope', 'exception', 'file', 'line', 'admin_id'] as $key) {
        if (isset($context[$key]) && is_scalar($context[$key])) {
            $record[$key] = substr(preg_replace('/[\x00-\x1F\x7F]/', '', (string) $context[$key]), 0, 100);
        }
    }
    error_log('ONescrime security ' . json_encode($record, JSON_UNESCAPED_SLASHES));
}

function enforce_request_limit(int $maximum = 32768): void
{
    $length = $_SERVER['CONTENT_LENGTH'] ?? null;
    if ($length !== null && (!ctype_digit((string) $length) || (float) $length > $maximum)) {
        fail_request('Request too large.', 413);
    }
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($type === 'multipart/form-data') {
        // PHP parses multipart before user code. Apache/PHP must also cap ingress.
        if ($length === null) {
            fail_request('Content-Length required for multipart uploads.', 411);
        }
    } elseif (in_array($_SERVER['REQUEST_METHOD'] ?? '', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $raw = file_get_contents('php://input', false, null, 0, $maximum + 1);
        if ($raw === false || strlen($raw) > $maximum) {
            fail_request('Request too large.', 413);
        }
    }
}
