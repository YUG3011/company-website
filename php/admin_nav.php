<?php
// reusable responsive admin navbar
// Assumes session started and config.php already required where included.
// Safe compute unread count if possible.
$unread_count = 0;
if (isset($pdo)) {
    try {
        $stmt_un = $pdo->query('SELECT COUNT(*) AS c FROM contact_messages WHERE is_read = 0');
        $r_un = $stmt_un->fetch();
        $unread_count = isset($r_un['c']) ? (int)$r_un['c'] : 0;
    } catch (Exception $e) { $unread_count = 0; }
}
?>
<style>
/* Responsive admin navbar (inline to keep pages self-contained) */
.admin-navbar{position:fixed;inset:0 0 auto 0;height:74px;z-index:120;background:linear-gradient(150deg,rgba(10,18,32,0.82),rgba(15,23,42,0.9));backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:space-between;padding:10px 20px;border-bottom:1px solid rgba(255,255,255,0.04)}
.admin-nav-left{display:flex;align-items:center;gap:12px}
.admin-logo{height:44px;width:44px;object-fit:contain;border-radius:8px}
.admin-title{font-weight:800;background:linear-gradient(90deg,#38bdf8,#6366f1);-webkit-background-clip:text;color:transparent}
/* nav links */
.admin-nav-toggle{position:absolute;left:-9999px;opacity:0;width:1px;height:1px;}
.admin-links{display:flex;gap:8px;align-items:center}
.admin-links a{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:10px;text-decoration:none;font-weight:700;color:#fff;background:linear-gradient(90deg,#1e293b,#0f172a);border:1px solid rgba(255,255,255,0.03)}
.badge{background:#ef4444;color:#fff;padding:4px 8px;border-radius:999px;font-weight:800;margin-left:8px}
/* mobile */
.admin-burger{display:none}
@media (max-width:800px){
  .admin-links{display:none;position:absolute;top:74px;left:0;right:0;background:linear-gradient(180deg,#081225,#050a14);flex-direction:column;padding:12px;gap:10px;border-bottom:1px solid rgba(255,255,255,0.04)}
  .admin-burger{display:inline-flex;width:44px;height:44px;align-items:center;justify-content:center;border-radius:10px;background:linear-gradient(90deg,#38bdf8,#6366f1);color:#fff;font-weight:800}
  /* when checkbox checked show links */
  .admin-nav-toggle:checked + .admin-burger + .admin-links{display:flex}
}
</style>
<header class="admin-navbar">
  <div class="admin-nav-left">
    <img src="../Sofzenix IT Solution.png" alt="Sofzenix" class="admin-logo">
    <div>
      <div class="admin-title">Sofzenix Admin</div>
      <div style="font-size:12px;color:#94a3b8">Secure administration</div>
    </div>
  </div>
  <input id="admin-nav-toggle" class="admin-nav-toggle" type="checkbox" aria-label="Open menu">
  <label for="admin-nav-toggle" class="admin-burger">☰</label>
  <nav class="admin-links">
    <a href="admin.php">Home</a>
    <a href="verification.php">Verify</a>
    <a href="manage.php">Certificates</a>
    <a href="messages.php">Messages<?php if(!empty($unread_count)): ?> <span class="badge"><?php echo $unread_count; ?></span><?php endif; ?></a>
    <form method="get" action="admin.php" style="display:inline;margin:0">
      <button class="admin-logout" name="logout" value="1" type="submit" style="padding:8px 12px;border-radius:10px;border:0;background:linear-gradient(90deg,#ef4444,#dc2626);color:#fff;font-weight:700">Logout</button>
    </form>
  </nav>
</header>
