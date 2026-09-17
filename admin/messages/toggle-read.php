<?php
require_once dirname(__DIR__) . '/_common.php';
require_method('POST');
verifyCsrf();
$id = valid_id($_POST['id'] ?? null);
find_record('contact_messages', $id);
try {
    $read = integer_field($_POST, 'is_read', 0, 1);
} catch (InvalidArgumentException $error) {
    fail_request($error->getMessage(), 422);
}
query('UPDATE contact_messages SET is_read = ? WHERE id = ?', [$read, $id]);
admin_notice($read ? 'Message marqué lu.' : 'Message marqué non lu.', 'admin/messages/');
