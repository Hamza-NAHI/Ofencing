<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function db(): PDO
{
    static $pdo;
    if ($pdo === null) {
        // Set credentials in config/local.php (ignored by Git), or environment variables.
        // On shared hosting, copy config/local.example.php to config/local.php.
        $config = settings();
        $dsn = 'mysql:host=' . $config['DB_HOST'] . ';port=' . $config['DB_PORT']
            . ';dbname=' . $config['DB_NAME'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        // Store all creation/update timestamps in UTC; event dates and slot times are local.
        $pdo->prepare("SET time_zone = '+00:00'")->execute();
    }
    return $pdo;
}

function query(string $sql, array $parameters = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute($parameters);
    return $statement;
}
