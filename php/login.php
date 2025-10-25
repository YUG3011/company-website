<?php
session_start();
require_once __DIR__ . '/config.php';

// If already logged in redirect to admin dashboard placeholder
if (!empty($_SESSION['is_admin'])) {
  header('Location: admin.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    $authenticated = false;

    // 1) Try database-backed admin authentication (admins table with password_hash)
    try {
        if (isset($pdo)) {
            $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $user]);
            $admin = $stmt->fetch();
            if ($admin && !empty($admin['password_hash']) && password_verify($pass, $admin['password_hash'])) {
                $authenticated = true;
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                // store numeric admin id for later actions (e.g. uploader_id)
                $_SESSION['admin_id'] = (int)$admin['id'];
                session_regenerate_id(true);
                header('Location: admin.php');
                exit;
            }
        }
    } catch (Exception $e) {
        // DB error — fall back to legacy constant-based auth below
    }

    // 2) Fallback to legacy constants (kept for compatibility) — DO NOT keep plain text in production
    if (!$authenticated && defined('ADMIN_USERNAME') && defined('ADMIN_PASSWORD') && $user === ADMIN_USERNAME && $pass === ADMIN_PASSWORD) {
        $authenticated = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_username'] = $user;
        session_regenerate_id(true);
        header('Location: admin.php');
        exit;
    }

    // failed
    usleep(250000);
    $error = 'Invalid credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Login - Sofzenix</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root { --bg:#0f172a; --panel:#1e293b; --grad:linear-gradient(120deg,#38bdf8,#6366f1,#f472b6); }
* { box-sizing:border-box !important; font-family:'Inter',system-ui,Arial,sans-serif !important; }
html,body { height:100% !important; margin:0 !important; background:radial-gradient(circle at 20% 20%, #1e293b 0%, #0f172a 60%) !important; color:#e2e8f0 !important; }
body { display:flex !important; align-items:center !important; justify-content:center !important; padding:32px 16px !important; }
.login-wrapper { width:100% !important; max-width:420px !important; }
.brand { text-align:center !important; margin:0 0 34px !important; }
.brand h1 { margin:12px 0 0 !important; font-size:1.4rem !important; letter-spacing:.5px !important; font-weight:800 !important; background:var(--grad) !important; -webkit-background-clip:text !important; color:transparent !important; }
.panel { background:linear-gradient(150deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02)) !important; border:1px solid rgba(255,255,255,0.08) !important; padding:38px 34px 40px !important; border-radius:28px !important; position:relative !important; backdrop-filter:blur(20px) saturate(1.4) !important; box-shadow:0 18px 46px -18px #000 !important; }
.panel::before { content:"" !important; position:absolute !important; inset:0 !important; background:radial-gradient(circle at 30% 20%,rgba(99,102,241,.4),transparent 70%) !important; mix-blend-mode:overlay !important; opacity:.55 !important; pointer-events: none !important; }
.panel h2 { margin:0 0 24px !important; font-size:1.35rem !important; font-weight:800 !important; letter-spacing:.5px !important; }
.form-group { display:flex !important; flex-direction:column !important; gap:8px !important; margin:0 0 18px !important; }
.form-group label { font-size:.7rem !important; font-weight:700 !important; letter-spacing:1px !important; text-transform:uppercase !important; color:#93c5fd !important; }
.form-group input { padding:14px 16px !important; border-radius:16px !important; border:1px solid rgba(255,255,255,0.12) !important; background:rgba(255,255,255,0.06) !important; color:#fff !important; font-size:.9rem !important; font-weight:500 !important; outline:none !important; transition:.3s !important; position:relative !important; z-index:2 !important; }
.form-group input:focus { border-color:#38bdf8 !important; box-shadow:0 0 0 3px rgba(56,189,248,.25) !important; }
.error { background:#dc2626cc !important; color:#fff !important; padding:10px 14px !important; border-radius:14px !important; font-size:.75rem !important; font-weight:600 !important; margin:0 0 18px !important; }
.actions { display:flex !important; align-items:center !important; justify-content:space-between !important; gap:16px !important; flex-wrap:wrap !important; }
button.login-btn { flex:1 1 auto !important; background:var(--grad) !important; background-size:200% 100% !important; border:none !important; color:#fff !important; font-weight:700 !important; letter-spacing:.6px !important; padding:14px 20px !important; font-size:.9rem !important; border-radius:16px !important; cursor:pointer !important; box-shadow:0 12px 36px -14px #6366f199 !important; transition:.5s !important; pointer-events:auto !important; opacity:1 !important; z-index:10 !important; }
.home-btn { background:#ffffff33 !important; color:#fff !important; border:1px solid rgba(255,255,255,0.12) !important; padding:12px 18px !important; border-radius:14px !important; font-weight:700 !important; cursor:pointer !important; text-decoration:none !important; margin-right:12px !important; position:relative !important; z-index:20 !important; pointer-events:auto !important; display:inline-block !important; }
.home-btn:hover { filter:brightness(1.05) !important; transform:translateY(-2px) !important; }
form.panel { pointer-events:auto !important; opacity:1 !important; z-index:10 !important; }
button.login-btn:hover { background-position:100% 50% !important; transform:translateY(-4px) !important; box-shadow:0 16px 40px -16px #6366f1aa !important; }
.footer-note { margin:28px 0 0 !important; text-align:center !important; font-size:.65rem !important; letter-spacing:.6px !important; color:#94a3b8 !important; }
.sec-badge { font-size:.55rem !important; padding:4px 8px !important; border:1px solid rgba(255,255,255,0.15) !important; border-radius:10px !important; background:rgba(255,255,255,0.06) !important; font-weight:600 !important; letter-spacing:.7px !important; }
@media (max-width:520px){ .panel{padding:32px 26px 34px !important;} }
/* Force enable pointer events and visibility for form fields */
.form-group input, .form-group label, .panel, .login-wrapper, form.panel {
  pointer-events: auto !important;
  opacity: 1 !important;
  visibility: visible !important;
  z-index: 10 !important;
}
</style>
<link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">
<link rel="apple-touch-icon" href="image/title-logo/Sofzenix IT Solutions.png">
</head>
<body>
  <div class="login-wrapper">
    <div class="brand">
      <img src="../Sofzenix IT Solution.png" alt="Logo" style="height:56px;">
      <h1>Sofzenix Admin</h1>
    </div>
    <form method="post" class="panel" autocomplete="off" novalidate>
      <!-- Hidden fields to prevent browser autofill (keeps visible fields empty) -->
      <input type="text" name="prevent_autofill_username" id="prevent_autofill_username" autocomplete="username" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" />
      <input type="password" name="prevent_autofill_password" id="prevent_autofill_password" autocomplete="new-password" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" />

      <h2>Admin Login</h2>
      <?php if($error): ?>
        <div class="error" role="alert"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required autocomplete="off" value="" />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="new-password" value="" />
      </div>
      <div class="actions">
        <a href="../index.html" class="home-btn">Home</a>
        <button type="submit" class="login-btn">Sign In →</button>
        <span class="sec-badge">ADMIN ONLY</span>
      </div>
     
      <p class="footer-note">This Page Is For Admin So Dont Touch Any Data If You Are Only User. Then Goto Your Home Page</p>
    </form>
  </div>
</body>
</html>
