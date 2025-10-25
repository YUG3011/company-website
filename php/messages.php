<?php
// php/messages.php - admin list of contact messages
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) { header('Location: login.php'); exit; }

// Fetch unread contact messages count for navbar badge (non-fatal)
$unread_count = 0;
try {
    $stmt_un = $pdo->query('SELECT COUNT(*) AS c FROM contact_messages WHERE is_read = 0');
    $r_un = $stmt_un->fetch();
    $unread_count = isset($r_un['c']) ? (int)$r_un['c'] : 0;
} catch (Exception $e) {
    $unread_count = 0;
}

// Fetch messages
try {
    $stmt = $pdo->query('SELECT id, name, email, subject, message, created_at, is_read FROM contact_messages ORDER BY created_at DESC');
    $msgs = $stmt->fetchAll();
} catch (Exception $e) {
    $msgs = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Messages — Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--grad1:#38bdf8;--grad2:#6366f1;--border:rgba(255,255,255,0.08);--nav-h:74px;--muted:#94a3b8}
    *{box-sizing:border-box;font-family:Inter,system-ui,Arial,sans-serif}
    body{margin:0;color:#fff;background:linear-gradient(180deg,#020617 0%,#0f172a 52%,#1e293b 100%);padding-top:var(--nav-h);}
    .navbar{display:flex;align-items:center;justify-content:space-between;padding:14px clamp(16px,4vw,42px);backdrop-filter:blur(18px) saturate(160%);-webkit-backdrop-filter:blur(18px) saturate(160%);background:linear-gradient(150deg,rgba(10,18,32,0.72),rgba(15,23,42,0.85));border-bottom:1px solid var(--border);position:fixed;inset:0 0 auto 0;height:var(--nav-h);z-index:120}
    .nav-left{display:flex;align-items:center;gap:14px}
    .nav-logo{height:48px;width:48px;object-fit:contain;border-radius:10px}
    .nav-title{font-weight:800;letter-spacing:.6px;background:linear-gradient(90deg,var(--grad1),var(--grad2));-webkit-background-clip:text;color:transparent;font-size:1.15rem}
    .nav-actions{display:flex;gap:12px;align-items:center}
    .btn{--btn-bg:linear-gradient(90deg,var(--grad1),var(--grad2));background:var(--btn-bg);color:#fff;border:1px solid rgba(255,255,255,0.08);padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700}
    .container{max-width:1180px;margin:0 auto;padding:34px clamp(18px,4vw,38px);height: 75vh;}

    /* NEW: card grid for messages */
    .messages-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;margin-top:18px}
    .message-card{background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.04);padding:18px;border-radius:14px;box-shadow:0 8px 30px rgba(2,6,23,0.6);min-height:140px;display:flex;flex-direction:column;justify-content:space-between;width: max-content;}
    .message-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:8px}
    .msg-from{font-weight:800;font-size:1rem}
    .msg-meta{font-size:.82rem;color:var(--muted)}
    .msg-subject{font-weight:700;margin:8px 0;color:#e2e8f0}
    .msg-body{color:#cbd5e1;font-size:.95rem;line-height:1.4;max-height:88px;overflow:hidden;text-overflow:ellipsis}
    .card-actions{display:flex;gap:8px;margin-top:12px;align-items:center}
    .action-btn{padding:8px 12px;border-radius:10px;border:0;cursor:pointer;font-weight:700}
    .view-btn{background:linear-gradient(90deg,#10b981,#059669);color:#fff}
    .delete-btn{background:#ef4444;color:#fff}
    .badge-unread{background:#ef4444;color:#fff;padding:6px 10px;border-radius:999px;font-weight:800;font-size:.8rem}
    @media (max-width:640px){.container{padding:20px 18px}}
  </style>
<link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">
<link rel="apple-touch-icon" href="image/title-logo/Sofzenix IT Solutions.png">
</head>
<body>
  <?php require_once __DIR__ . '/admin_nav.php'; ?>
  <main class="container">
    <h1>Contact Messages</h1>
    <?php if(empty($msgs)): ?>
      <p>No messages yet.</p>
    <?php else: ?>
      <div class="messages-grid">
        <?php foreach($msgs as $m): ?>
          <div class="message-card">
            <div>
              <div class="message-head">
                <div>
                  <div class="msg-from"><?php echo htmlspecialchars($m['name']); ?></div>
                  <div class="msg-meta"><?php echo htmlspecialchars($m['email']); ?> • <?php echo htmlspecialchars($m['created_at']); ?></div>
                </div>
                <?php if(empty($m['is_read'])): ?><div class="badge-unread">NEW</div><?php endif; ?>
              </div>
              <div class="msg-subject"><?php echo htmlspecialchars($m['subject'] ?: '— No subject —'); ?></div>
              <div class="msg-body"><?php echo nl2br(htmlspecialchars(substr($m['message'],0,400))); ?></div>
            </div>

            <div class="card-actions">
              <a class="action-btn view-btn" href="message_view.php?id=<?php echo urlencode($m['id']); ?>">View</a>
              <form action="message_action.php" method="post" style="display:inline;margin:0">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id']); ?>">
                <button class="action-btn delete-btn" type="submit" onclick="return confirm('Delete this message?')">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
  <footer style="padding:24px;color:#94a3b8">© <?php echo date('Y'); ?> Sofzenix IT Solutions LLP.</footer>
</body>
</html>
