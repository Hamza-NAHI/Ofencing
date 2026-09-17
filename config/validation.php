<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

const FENCING_LEVELS = ['Complete beginner', 'Beginner', 'Intermediate', 'Competitor', 'Coach / club inquiry'];

function field(array $input, string $key, int $max, bool $required = true): string
{
    $value = $input[$key] ?? '';
    if (!is_string($value) || !preg_match('//u', $value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
        throw new InvalidArgumentException('Invalid field: ' . $key . '.');
    }
    $value = preg_replace('/^\s+|\s+$/u', '', $value);
    if (($required && $value === '') || preg_match_all('/./us', $value) > $max) {
        throw new InvalidArgumentException('Invalid field: ' . $key . ' (maximum ' . $max . ' characters).');
    }
    return $value;
}

function integer_field(array $input, string $key, int $min, int $max, $default = null): int
{
    $value = $input[$key] ?? $default;
    if (!is_int($value) && !(is_string($value) && preg_match('/^[0-9]+$/D', $value))) {
        throw new InvalidArgumentException('Invalid field: ' . $key . '.');
    }
    if ($value < $min || $value > $max) {
        throw new InvalidArgumentException('Invalid field: ' . $key . '.');
    }
    return (int) $value;
}

function flag_field(array $input, string $key): int
{
    if (isset($input[$key]) && is_bool($input[$key])) {
        return (int) $input[$key];
    }
    return integer_field($input, $key, 0, 1, 0);
}

function event_input(array $input): array
{
    $date = field($input, 'event_date', 10);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < '1000-01-01') {
        throw new InvalidArgumentException('Invalid event_date. Use YYYY-MM-DD.');
    }
    return [
        'event_date' => $date, 'title' => field($input, 'title', 200),
        'description' => field($input, 'description', 5000, false),
        'location' => field($input, 'location', 200), 'type' => field($input, 'type', 100),
        'is_published' => flag_field($input, 'is_published'),
    ];
}

function training_input(array $input): array
{
    $start = field($input, 'start_time', 8);
    $end = field($input, 'end_time', 8);
    foreach ([$start, $end] as $time) {
        if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::00)?$/D', $time)) {
            throw new InvalidArgumentException('Invalid time. Use HH:MM.');
        }
    }
    $start = substr($start, 0, 5);
    $end = substr($end, 0, 5);
    if ($end <= $start) {
        throw new InvalidArgumentException('End time must be after start time on the same day.');
    }
    return [
        'day_of_week' => integer_field($input, 'day_of_week', 1, 7),
        'start_time' => $start, 'end_time' => $end,
        'type' => field($input, 'type', 100), 'location' => field($input, 'location', 200, false),
        'display_order' => integer_field($input, 'display_order', 0, 9999, 0),
        'is_active' => flag_field($input, 'is_active'),
    ];
}

function message_input(array $input): array
{
    $contact = field($input, 'contact', 254);
    $phone = preg_match('/^\+?[0-9][0-9\s().-]*$/D', $contact)
        && preg_match('/^[0-9]{7,15}$/D', preg_replace('/\D/', '', $contact));
    if (!filter_var($contact, FILTER_VALIDATE_EMAIL) && !$phone) {
        throw new InvalidArgumentException('Enter a valid email address or phone number.');
    }
    $level = field($input, 'level', 50, false);
    if ($level !== '' && !in_array($level, FENCING_LEVELS, true)) {
        throw new InvalidArgumentException('Invalid fencing level.');
    }
    return ['name' => field($input, 'name', 120), 'contact' => $contact,
        'level' => $level, 'message' => field($input, 'message', 5000, false)];
}
