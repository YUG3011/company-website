<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

// Accept id via GET or POST for backward compatibility, but validate strictly
$id = $_POST['id'] ?? $_GET['id'] ?? '';
$id = intval($id);
if (!$id) {
    header('Location: manage.php');
    exit;
}

$upload_dir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads/');
if (is_string($upload_dir) && substr($upload_dir, -1) !== DIRECTORY_SEPARATOR) {
    $upload_dir .= DIRECTORY_SEPARATOR;
}

// Try DB delete first
try {
    $stmt = $pdo->prepare('SELECT stored_name, original_name FROM certificates WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        $stored = $row['stored_name'] ?? '';
        $basename = basename($stored);
        $filePath = $upload_dir . $basename;
        // Ensure file path stays inside uploads directory
        $realUploadDir = is_dir($upload_dir) ? realpath($upload_dir) : false;
        $realFilePath = file_exists($filePath) ? realpath($filePath) : false;

        $pdo->beginTransaction();
        $del = $pdo->prepare('DELETE FROM certificates WHERE id = :id');
        $del->execute([':id' => $id]);

        if ($realFilePath && $realUploadDir && strpos($realFilePath, $realUploadDir) === 0) {
            @unlink($realFilePath);
        } elseif (file_exists($filePath)) {
            // fallback unlink if realpath not available
            @unlink($filePath);
        }

        $pdo->commit();
        header('Location: manage.php');
        exit;
    }
} catch (Exception $e) {
    // DB unavailable or error — fall back to JSON below
}

// Fallback: legacy JSON storage
$data_file = __DIR__ . '/data/certificates.json';
if (!file_exists($data_file)) {
    header('Location: manage.php');
    exit;
}
$records = json_decode(file_get_contents($data_file), true) ?: [];
$found = false;
foreach ($records as $k => $r) {
    // some legacy entries may have string ids — compare loosely
    if (!empty($r['id']) && ((string)$r['id'] === (string)$id)) {
        // delete file if exists (legacy used '../uploads/...')
        if (!empty($r['file'])) {
            $fileBase = basename($r['file']);
            $path = __DIR__ . '/../uploads/' . $fileBase;
            if (file_exists($path)) @unlink($path);
        }
        unset($records[$k]);
        $found = true;
        break;
    }
}
if ($found) {
    file_put_contents($data_file, json_encode(array_values($records), JSON_PRETTY_PRINT));
}
header('Location: manage.php');
exit;
