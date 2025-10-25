<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}
// Simple logout handler
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: ../index.html');
  exit;
}

// Fetch unread contact messages count for navbar badge (non-fatal)
$unread_count = 0;
try {
    $stmt_un = $pdo->query('SELECT COUNT(*) AS c FROM contact_messages WHERE is_read = 0');
    $r_un = $stmt_un->fetch();
    $unread_count = isset($r_un['c']) ? (int)$r_un['c'] : 0;
} catch (Exception $e) {
    $unread_count = 0;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard — Sofzenix</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* --- Admin Panel Dark Theme aligned with user site design (colors only, no content changes) --- */
    :root {
      --grad1:#38bdf8; --grad2:#6366f1; --grad3:#f472b6;
      --bg-deep:#020617; --bg-mid:#0f172a; --bg-soft:#1e293b;
      --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,0.08);
      --glass:rgba(255,255,255,0.04); --glass-hi:rgba(255,255,255,0.07);
      --nav-h:74px;
    }
    *{box-sizing:border-box}
  body{font-family:Inter,system-ui,Arial,sans-serif;margin:0;min-height:100vh;display:flex;flex-direction:column;color:var(--text);padding-top:var(--nav-h);background:
      radial-gradient(circle at 12% 18%,rgba(99,102,241,0.18),transparent 55%),
      radial-gradient(circle at 88% 75%,rgba(56,189,248,0.22),transparent 60%),
      linear-gradient(180deg,var(--bg-deep) 0%,var(--bg-mid) 52%,var(--bg-soft) 100%);
      background-attachment:fixed;}
    /* Glassy navbar similar to user side */
  .navbar{display:flex;align-items:center;justify-content:space-between;padding:14px clamp(16px,4vw,42px);backdrop-filter:blur(18px) saturate(160%);-webkit-backdrop-filter:blur(18px) saturate(160%);background:linear-gradient(150deg,rgba(10,18,32,0.72),rgba(15,23,42,0.85));border-bottom:1px solid var(--border);box-shadow:0 10px 40px -18px #0008;position:fixed;inset:0 0 auto 0;height:var(--nav-h);z-index:120}
    .nav-left{display:flex;align-items:center;gap:14px}
    .nav-logo{height:48px;width:48px;object-fit:contain;border-radius:10px;filter:drop-shadow(0 4px 14px rgba(0,0,0,.35))}
    .nav-title{font-weight:800;letter-spacing:.6px;background:linear-gradient(90deg,var(--grad1),var(--grad2));-webkit-background-clip:text;color:transparent;font-size:1.15rem;margin-bottom:2px}
    .nav-actions{display:flex;gap:12px;align-items:center}
    /* Unified button styles */
    .btn{--btn-bg:linear-gradient(90deg,var(--grad1),var(--grad2));background:var(--btn-bg);color:#fff;border:1px solid rgba(255,255,255,0.08);padding:10px 18px;border-radius:12px;font-weight:700;cursor:pointer;text-decoration:none;font-size:.9rem;letter-spacing:.3px;display:inline-flex;align-items:center;gap:6px;position:relative;overflow:hidden;transition:.28s cubic-bezier(.4,.4,.2,1);box-shadow:0 10px 34px -14px rgba(99,102,241,0.55),0 4px 14px -6px rgba(56,189,248,0.45)}
    .btn:hover{transform:translateY(-3px);box-shadow:0 16px 42px -14px rgba(99,102,241,0.65)}
    .btn:active{transform:translateY(-1px)}
    .btn.ghost{--btn-bg:linear-gradient(90deg,#ffffff,#eef2ff);background:var(--btn-bg);color:#0f172a;font-weight:700;border:1px solid rgba(255,255,255,0.25);box-shadow:0 6px 22px -10px rgba(99,102,241,.45)}
    .btn.ghost:hover{filter:brightness(1.05)}
    /* Layout */
  .container{max-width:1180px;width:100%;margin:0 auto;padding:40px clamp(18px,4vw,38px);flex:1;display:flex;align-items:center;justify-content:center;min-height:calc(100vh - var(--nav-h) - 120px);}
    /* Card adopts glass style */
  .card{background:linear-gradient(145deg,var(--glass),var(--glass-hi));border:1px solid var(--border);backdrop-filter:blur(26px) saturate(170%);-webkit-backdrop-filter:blur(26px) saturate(170%);padding:34px clamp(20px,3.2vw,46px);border-radius:28px;box-shadow:0 18px 60px -18px rgba(0,0,0,0.55),0 6px 28px -10px rgba(0,0,0,0.55);max-width:760px;width:100%;}
    .card h3{margin:0 0 24px;font-size:1.45rem;letter-spacing:.5px;font-weight:800;background:linear-gradient(90deg,var(--grad1),var(--grad2),var(--grad3));-webkit-background-clip:text;color:transparent}
    .form-row{display:flex;flex-direction:column;gap:18px}
    label{font-size:.75rem;letter-spacing:.7px;font-weight:700;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:6px}
  input[type=text],input[type=date],select,input[type=file]{width:100%;padding:14px 14px 13px;border-radius:14px;border:1px solid rgba(0,0,0,0.15);background:linear-gradient(135deg,#ffffff,#f1f5f9);color:#0f172a;font:600 0.9rem/1.2 Inter,system-ui,sans-serif;letter-spacing:.3px;outline:none;transition:border-color .3s,background .35s,box-shadow .35s}
  input[type=file]{padding:12px;border-style:dashed;background:#ffffff;cursor:pointer}
  input::placeholder{color:#64748b;font-weight:500}
  select option{color:#0f172a;background:#ffffff}
    select{cursor:pointer}
  input:focus,select:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.45);background:#ffffff}
    input[type=date]:focus{color:var(--text)}
    .card form > div > button.btn{margin-top:4px;min-width:180px}
    .info .kv{display:grid;grid-template-columns:140px 1fr;gap:8px 18px;margin-top:10px}
    .kv .key{color:var(--muted);font-weight:700}
    .kv .val{word-break:break-word}
    footer{max-width:1180px;margin:46px auto 22px;padding:18px clamp(18px,4vw,38px);color:#64748b;font-size:0.78rem;letter-spacing:.4px;text-align:left}
    /* Subtle entrance animation */
    @keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}
    .card{animation:fadeUp .9s cubic-bezier(.4,.2,.15,1)}
  @media (max-width:980px){.nav-title{display:none}.card{padding:30px 24px;border-radius:24px;margin:0 auto}.container{padding:34px 26px;}}
    @media (max-width:640px){.btn,.btn.ghost{padding:9px 14px;font-size:.78rem;border-radius:10px}.nav-logo{height:40px;width:40px}.card h3{font-size:1.25rem}}
  </style>
<link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">
<link rel="apple-touch-icon" href="image/title-logo/Sofzenix IT Solutions.png">
</head>
<body>
<?php require_once __DIR__ . '/admin_nav.php'; ?>

  <main class="container">
    <section class="card">
      <h3>Add New Certificate</h3>
      <form action="insert.php" method="POST" enctype="multipart/form-data" class="form-row">
        <div>
          <label for="cert_number">Certificate Number</label>
          <input id="cert_number" name="cert_number" type="text" placeholder="e.g., INT2025003" required>
        </div>
        <div>
          <label for="name">Full Name</label>
          <input id="name" name="name" type="text" placeholder="Full name" required>
        </div>
        <div>
          <label for="type">Type</label>
          <select id="type" name="type" required>
            <option value="">-- Select Type --</option>
            <option value="Internship">Internship</option>
            <option value="Employee">Employee</option>
          </select>
        </div>
        <div>
          <label for="dob">Date of Birth</label>
          <input id="dob" name="dob" type="date" placeholder="YYYY-MM-DD" required>
        </div>
        <div>
          <label for="issue_date">Issue Date</label>
          <input id="issue_date" name="issue_date" type="date" required>
        </div>
        <div>
          <label for="certificate_file">Upload Certificate (PDF / Image)</label>
          <input id="certificate_file" name="certificate_file" type="file" accept="application/pdf,image/*" required>
        </div>
        <div>
          <button class="btn" type="submit">Add Certificate</button>
        </div>
      </form>
    </section>

    
  </main>

  <footer>© <?php echo date('Y'); ?> Sofzenix IT Solutions LLP. All rights reserved.</footer>
</body>
</html>
