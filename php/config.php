<?php
// php/config.php — update DB credentials for local XAMPP

// define('DB_HOST', 'localhost');
// define('DB_NAME', 'sofzenix1_sofzenix_it_solution');
// define('DB_USER', 'sofzenix1_phpma');
// define('DB_PASS', 'Sofzenix@2023'); // set if you changed root passwor
// define('ADMIN_EMAIL', 'contact@sofzenix.in'); // used for contact form notifications (optional)

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'sofzenix_it_solution');
define('DB_USER', 'root');
define('DB_PASS', ''); // set if you changed root password
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'Admin@123';

// Admin credentials (temporary — we'll switch to hashed passwords next)
// Maintenance toggle: presence of this file disables DB connection and enables maintenance mode.
$maintenance_flag = __DIR__ . '/maintenance.flag';
if (file_exists($maintenance_flag)) {
    define('MAINTENANCE_MODE', true);
    $pdo = null;
} else {
    define('MAINTENANCE_MODE', false);
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        // Log the error for admins and set $pdo to null so pages can handle offline DB gracefully.
        error_log('Database connection failed: ' . $e->getMessage());
        $pdo = null;
        define('DB_CONNECTION_ERROR', true);
    }
}