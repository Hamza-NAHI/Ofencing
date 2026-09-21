<?php
declare(strict_types=1);
define('OFENCING_API', true);
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/validation.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function request_data(): array
{
    enforce_request_limit();
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($type === 'application/json') {
        $raw = file_get_contents('php://input', false, null, 0, 32769);
        if (strlen($raw) > 32768) {
            fail_request('Request too large.', 413);
        }
        try {
            $object = json_decode($raw, false, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            fail_request('Invalid JSON.', 400);
        }
        if (!is_object($object)) {
            fail_request('Expected a JSON object.', 400);
        }
        return (array) $object;
    }
    if (!in_array($type, ['multipart/form-data', 'application/x-www-form-urlencoded'], true)) {
        fail_request('Use JSON or form data.', 415);
    }
    return $_POST;
}

// Authenticated POST actions work on shared hosting without PUT/DELETE routing.
// Events/training have a tiny shared handler; all SQL identifiers are allowlisted.
function content_api(string $kind): void
{
    if (!in_array($kind, ['events', 'training'], true)) {
        fail_request('Not found.', 404);
    }
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        $sql = $kind === 'events'
            ? 'SELECT event_date, title, description, location, type FROM events WHERE is_published = 1 AND event_date >= ? ORDER BY event_date, id'
            : 'SELECT day_of_week, start_time, end_time, type, location FROM training WHERE is_active = 1 ORDER BY display_order, day_of_week, start_time, id';
        json_response(['success' => true, 'data' => query($sql, $kind === 'events' ? [date('Y-m-d')] : [])->fetchAll()]);
    }
    require_method('POST');
    requireAdmin();
    $input = request_data();
    verifyCsrf($input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $action = $input['action'] ?? 'create';
    if (!in_array($action, ['create', 'update', 'delete'], true)) {
        fail_request('Invalid action.', 422);
    }
    $id = null;
    if ($action !== 'create') {
        $id = valid_id($input['id'] ?? $_GET['id'] ?? null);
        if (!query("SELECT id FROM $kind WHERE id = ?", [$id])->fetch()) {
            fail_request('Not found.', 404);
        }
    }
    if ($action === 'delete') {
        query("DELETE FROM $kind WHERE id = ?", [$id]);
        json_response(['success' => true, 'data' => ['id' => $id]]);
    }
    try {
        $data = $kind === 'events' ? event_input($input) : training_input($input);
    } catch (InvalidArgumentException $error) {
        fail_request($error->getMessage(), 422);
    }
    if ($action === 'create') {
        $columns = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        query("INSERT INTO $kind ($columns) VALUES ($marks)", array_values($data));
        $id = (int) db()->lastInsertId();
    } else {
        $assignments = implode(', ', array_map(static fn($key) => "$key = ?", array_keys($data)));
        query("UPDATE $kind SET $assignments WHERE id = ?", [...array_values($data), $id]);
    }
    json_response(['success' => true, 'data' => ['id' => $id]], $action === 'create' ? 201 : 200);
}
