<?php
// php/message_action.php - handle mark/read/delete actions
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) { header('Location: login.php'); exit; }
$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);
if (!$id) { header('Location: messages.php'); exit; }
try {
    if ($action === 'delete') {
        $d = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
        $d->execute([':id'=>$id]);
    } elseif ($action === 'mark_unread') {
        $u = $pdo->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = :id');
        $u->execute([':id'=>$id]);
    } else {
        // mark read by default
        $u = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id');
        $u->execute([':id'=>$id]);
    }
} catch (Exception $e) {
    // ignore
}
header('Location: messages.php'); exit;
