<?php
// create_admin.php
// One-time script: creates `admins` table and inserts an admin user with a hashed password.
// USAGE (local dev): open in browser once: http://localhost/COMPANY-WEBSITE/php/create_admin.php
// After running, DELETE this file from the server for security.

require_once __DIR__ . '/config.php';
// Basic guard: if config failed to create a PDO instance, stop with actionable instructions
if (!isset($pdo) || $pdo === null) {
    echo "Database connection unavailable. The script cannot continue.\n";
    if (defined('DB_CONNECTION_ERROR')) {
        echo "The application failed to connect to the database (DB_CONNECTION_ERROR).\n";
    } else {
        echo "Possible reasons: MySQL is not running, 'php/maintenance.flag' exists, or config.php disabled DB.\n";
    }
    echo "Quick steps to resolve:\n";
    // Use constants from config.php where available to help fixing
    if (defined('DB_HOST') && defined('DB_NAME')) {
        echo " 1) Start MySQL in XAMPP Control Panel and ensure it is running.\n";
        echo " 2) Confirm the database '" . DB_NAME . "' exists (open http://localhost/phpmyadmin).\n";
        echo " 3) Verify DB credentials in php/config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS).\n";
        echo " 4) Remove php/maintenance.flag if present.\n";
    } else {
        echo "Please check php/config.php for database configuration.\n";
    }
    echo "After fixing, re-run this script in your browser.\n";
    // Helpful debug info (only show when running on localhost)
    $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
    if ($isLocal && defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        $maskedPass = defined('DB_PASS') ? (DB_PASS === '' ? '(empty)' : str_repeat('*', 8)) : '(unknown)';
        echo "\nDebug info (local only):\n";
        echo " DB_HOST=" . DB_HOST . "\n";
        echo " DB_NAME=" . DB_NAME . "\n";
        echo " DB_USER=" . DB_USER . "\n";
        echo " DB_PASS=" . $maskedPass . "\n";
    }
    exit;
}

// Change these values before running if you want a different username/password
$adminUsername = 'Admin';
$adminPlainPassword = 'AdMiN-SofzenixIt@2025Solution';

try {
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Check if user exists
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $adminUsername]);
    if ($stmt->fetch()) {
        echo "Admin user '{$adminUsername}' already exists.\n";
        echo "If you want to reset the password, delete the user from the admins table and run this script again.\n";
        exit;
    }

    // Insert user with password_hash
    $hash = password_hash($adminPlainPassword, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:u, :h)');
    $ins->execute([':u' => $adminUsername, ':h' => $hash]);

    echo "Admin user created successfully.\n";
    echo "Username: {$adminUsername}\n";
    echo "Password: {$adminPlainPassword}\n";
    echo "IMPORTANT: Delete this file (php/create_admin.php) after verifying login to avoid leaving a setup script on the server.\n";
    echo "You can now sign in at: /php/login.php\n";
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
