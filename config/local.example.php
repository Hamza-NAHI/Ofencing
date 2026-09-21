<?php
// Copy to local.php and replace placeholders. Never commit local.php.
return [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'DB_NAME',
    'DB_USER' => 'DB_USER',
    'DB_PASS' => 'DB_PASS',
    'APP_TIMEZONE' => 'Africa/Casablanca',
    // Usually detected automatically. Set '/ofencing' only if detection needs overriding.
    'APP_BASE_PATH' => null,
    // Production only: enable AFTER configuring HTTPS at the host. Keep false on XAMPP HTTP.
    'APP_REQUIRE_HTTPS' => false,
    // Temporary random secret (at least 32 characters) to enable admin/setup.php.
    // Remove it immediately after creating the first administrator.
    'SETUP_TOKEN' => '',
];
