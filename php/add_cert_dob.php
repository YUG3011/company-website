<?php
// add_cert_dob.php — run once to add `dob` column to certificates table if it doesn't exist
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
if (!$pdo) {
    echo "Database not available. Start MySQL and ensure php/config.php credentials are correct.\n";
    exit;
}
try {
    // Check if column exists (works on MySQL)
    $stmt = $pdo->query("SHOW COLUMNS FROM certificates LIKE 'dob'");
    $col = $stmt->fetch();
    if ($col) {
        echo "Column `dob` already exists on `certificates`. Nothing to do.\n";
        exit;
    }
    // Add the column
    $pdo->exec("ALTER TABLE certificates ADD COLUMN dob DATE NULL AFTER issue_date;");
    echo "Column `dob` added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>