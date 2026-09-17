<?php
require_once __DIR__ . '/_common.php';
require_method('POST');
rate_limit('contact', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 5, 600);
$input = request_data();
try {
    $data = message_input($input);
} catch (InvalidArgumentException $error) {
    fail_request($error->getMessage(), 422);
}
query('INSERT INTO contact_messages (name, contact, level, message) VALUES (?, ?, ?, ?)', array_values($data));
json_response(['success' => true, 'message' => 'Message received.'], 201);
