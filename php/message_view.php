<?php
// php/message_view.php - view a single message and mark as read
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) { header('Location: login.php'); exit; }
$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: messages.php'); exit; }
try {
    $stmt = $pdo->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $m = $stmt->fetch();
    if (!$m) { header('Location: messages.php'); exit; }
    // mark read
    if (!$m['is_read']) {
        $u = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id');
        $u->execute([':id' => $id]);
    }
} catch (Exception $e) { header('Location: messages.php'); exit; }

// fetch unread count for navbar
$unread_count = 0;
try {
    $stmt_un = $pdo->query('SELECT COUNT(*) AS c FROM contact_messages WHERE is_read = 0');
    $r_un = $stmt_un->fetch();
    $unread_count = isset($r_un['c']) ? (int)$r_un['c'] : 0;
} catch (Exception $e) { $unread_count = 0; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>View Message — Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>/* navbar styles copied to keep consistency */ :root{--grad1:#38bdf8;--grad2:#6366f1;--border:rgba(255,255,255,0.08);--nav-h:74px;--muted:#94a3b8} *{box-sizing:border-box;font-family:Inter,system-ui,Arial,sans-serif} body{margin:0;color:#fff;background:linear-gradient(180deg,#020617 0%,#0f172a 52%,#1e293b 100%);padding-top:var(--nav-h);} .navbar{display:flex;align-items:center;justify-content:space-between;padding:14px clamp(16px,4vw,42px);backdrop-filter:blur(18px) saturate(160%);-webkit-backdrop-filter:blur(18px) saturate(160%);background:linear-gradient(150deg,rgba(10,18,32,0.72),rgba(15,23,42,0.85));border-bottom:1px solid var(--border);position:fixed;inset:0 0 auto 0;height:var(--nav-h);z-index:120} .nav-left{display:flex;align-items:center;gap:14px} .nav-logo{height:48px;width:48px;object-fit:contain;border-radius:10px} .nav-title{font-weight:800;letter-spacing:.6px;background:linear-gradient(90deg,var(--grad1),var(--grad2));-webkit-background-clip:text;color:transparent;font-size:1.15rem} .nav-actions{display:flex;gap:12px;align-items:center} .btn{--btn-bg:linear-gradient(90deg,var(--grad1),var(--grad2));background:var(--btn-bg);color:#fff;border:1px solid rgba(255,255,255,0.08);padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700} .container{max-width:1180px;margin:0 auto;padding:34px clamp(18px,4vw,38px);height: 75vh;}
  .back {
  display: inline-block; /* allows height & padding */
  background: linear-gradient(90deg,#10b981,#059669);
  color: #fff;
  padding: 6px 14px; /* top-bottom, left-right */
  border-radius: 10px;
  text-decoration: none;
  line-height: 30px; /* centers text vertically */
}

  </style>
<link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">
<link rel="apple-touch-icon" href="image/title-logo/Sofzenix IT Solutions.png">
</head>
<body>
  <?php require_once __DIR__ . '/admin_nav.php'; ?>
  <main class="container">
    <a href="messages.php" class="back">← Back to messages</a>
    <h1><?php echo htmlspecialchars($m['subject'] ?: 'Message from ' . $m['name']); ?></h1>
    <p><strong>From:</strong> <?php echo htmlspecialchars($m['name']); ?> &lt;<?php echo htmlspecialchars($m['email']); ?>&gt;</p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($m['phone']); ?></p>
    <p><strong>Company:</strong> <?php echo htmlspecialchars($m['company']); ?></p>
    <p><strong>Received:</strong> <?php echo htmlspecialchars($m['created_at']); ?></p>
    <hr>
    <div style="white-space:pre-wrap;color:#e2e8f0"><?php echo htmlspecialchars($m['message']); ?></div>
    <hr>
    <form action="message_action.php" method="post" style="display:inline">
      <input type="hidden" name="action" value="mark_unread">
      <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id']); ?>">
      <button type="submit">Mark unread</button>
    </form>
    <form action="message_action.php" method="post" style="display:inline;margin-left:10px">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id']); ?>">
      <button type="submit" onclick="return confirm('Delete this message?')">Delete</button>
    </form>
  </main>
  <footer style="padding:24px;color:#94a3b8">© <?php echo date('Y'); ?> Sofzenix IT Solutions LLP.</footer>
</body>
</html>
