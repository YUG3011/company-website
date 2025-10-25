<?php
// test_db.php — simple DB connectivity diagnostic (open in browser)
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

if (!isset($pdo) || $pdo === null) {
    echo "PDO is null. Cannot connect to database.\n";
    if (defined('DB_CONNECTION_ERROR')) {
        echo "DB_CONNECTION_ERROR is defined — check error log for details.\n";
    }
    echo "Config values (masked where appropriate):\n";
    echo " DB_HOST=" . (defined('DB_HOST') ? DB_HOST : '(not defined)') . "\n";
    echo " DB_NAME=" . (defined('DB_NAME') ? DB_NAME : '(not defined)') . "\n";
    echo " DB_USER=" . (defined('DB_USER') ? DB_USER : '(not defined)') . "\n";
    echo " DB_PASS=" . (defined('DB_PASS') ? (DB_PASS === '' ? '(empty)' : str_repeat('*', 8)) : '(not defined)') . "\n";
    echo "maintenance.flag present? " . (file_exists(__DIR__ . '/maintenance.flag') ? 'YES' : 'NO') . "\n";
    exit;
}

try {
    $stmt = $pdo->query('SELECT NOW() AS now');
    $row = $stmt->fetch();
    echo "Connected to DB successfully. Server time: " . ($row['now'] ?? '(unknown)') . "\n";
    echo "Database name: " . (defined('DB_NAME') ? DB_NAME : '(unknown)') . "\n";
} catch (Exception $e) {
    echo "Query failed: " . $e->getMessage() . "\n";
}
