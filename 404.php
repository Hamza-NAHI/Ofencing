<?php
// Reuse the existing branded 404 HTML, adapting only its URLs for subdirectories.
require_once __DIR__ . '/config/bootstrap.php';
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, follow');
$html = file_get_contents(__DIR__ . '/404.html');
echo preg_replace_callback('/(href|src)="\/(?!\/)([^"]*)"/', static function ($match) {
    return $match[1] . '="' . h(app_url($match[2])) . '"';
}, $html);
