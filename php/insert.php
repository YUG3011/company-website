<?php
// insert.php - handles certificate form submission with file upload and stores metadata into MySQL via PDO
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cert_number = trim($_POST['cert_number'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $issue_date = trim($_POST['issue_date'] ?? '');
    $dob = trim($_POST['dob'] ?? '');

    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_url = '';
    $stored_name = null;
    $original_name = null;
    $mime = null;
    $size = null;

    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['certificate_file']['tmp_name'];
        $original_name = basename($_FILES['certificate_file']['name']);
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed, true)) {
            // detect mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            // basic mime whitelist
            $mime_allowed = ['application/pdf','image/jpeg','image/png','image/gif'];
            if ($detected === 'application/pdf' || strpos($detected, 'image/') === 0) {
                $new_name = uniqid('cert_', true) . '.' . $file_ext;
                $dest = $upload_dir . $new_name;
                if (move_uploaded_file($file_tmp, $dest)) {
                    $stored_name = $new_name;
                    $file_url = '../uploads/' . $new_name;
                    $mime = $detected;
                    $size = filesize($dest);
                } else {
                    // upload failed
                    $_SESSION['upload_error'] = 'File upload failed.';
                }
            } else {
                $_SESSION['upload_error'] = 'Invalid file contents.';
            }
        } else {
            $_SESSION['upload_error'] = 'Invalid file type. Only PDF, JPG, PNG, GIF allowed.';
        }
    } else {
        $_SESSION['upload_error'] = 'No file uploaded or upload error.';
    }

    // Insert metadata into DB
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO certificates (cert_number,name,type,issue_date,dob,original_name,stored_name,mime,size,uploader_id)
             VALUES (:cert_number,:name,:type,:issue_date,:dob,:original_name,:stored_name,:mime,:size,:uploader_id)"
        );

        // determine uploader id from session (set on login). falls back to null if not available
        $uploader_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

        // bind values with explicit types
        $stmt->bindValue(':cert_number', $cert_number, PDO::PARAM_STR);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':issue_date', $issue_date ?: null, $issue_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':dob', $dob ?: null, $dob ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':original_name', $original_name, PDO::PARAM_STR);
        $stmt->bindValue(':stored_name', $stored_name, PDO::PARAM_STR);
        $stmt->bindValue(':mime', $mime, PDO::PARAM_STR);
        if ($size === null) {
            $stmt->bindValue(':size', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':size', (int)$size, PDO::PARAM_INT);
        }
        if ($uploader_id === null) {
            $stmt->bindValue(':uploader_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':uploader_id', $uploader_id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $id = $pdo->lastInsertId();
    } catch (Exception $e) {
        // log error in production; for now store message in session and redirect
        $_SESSION['db_error'] = 'Database error: ' . $e->getMessage();
        header('Location: manage.php');
        exit;
    }

    // Redirect to manage page with success
    header('Location: manage.php?added=' . rawurlencode($id));
    exit;
} else {
    header('Location: admin.php');
    exit;
}
