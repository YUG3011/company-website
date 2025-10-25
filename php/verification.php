<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php'); exit;
}
$found = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num = trim($_POST['cert_number'] ?? '');
    $type = trim($_POST['type'] ?? '');
    if ($num !== '' && $type !== '') {
        try {
            $stmt = $pdo->prepare('SELECT * FROM certificates WHERE LOWER(cert_number)=LOWER(:num) AND LOWER(type)=LOWER(:type) LIMIT 1');
            $stmt->execute([':num'=>$num, ':type'=>$type]);
            $found = $stmt->fetch();
        } catch (Exception $e) {
            // DB error - fallback to JSON
            $data_file = __DIR__ . '/data/certificates.json';
            $records = file_exists($data_file) ? (json_decode(file_get_contents($data_file), true) ?: []) : [];
            foreach ($records as $r) {
                if (strcasecmp($r['cert_number'], $num) === 0 && strcasecmp($r['type'], $type) === 0) { $found = $r; break; }
            }
        }
    }
}
function is_pdf_file($file){ return $file && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf'; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verify Certificate</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--grad1:#38bdf8;--grad2:#6366f1;--grad3:#f472b6;--text:#f1f5f9;--muted:#94a3b8;--border:rgba(255,255,255,0.08);--nav-h:74px}
    *{box-sizing:border-box;font-family:Inter,system-ui,Arial,sans-serif}
    body{margin:0;min-height:100vh;color:var(--text);background:
      radial-gradient(circle at 12% 18%,rgba(99,102,241,0.18),transparent 55%),
      radial-gradient(circle at 88% 75%,rgba(56,189,248,0.22),transparent 60%),
      linear-gradient(180deg,#020617 0%,#0f172a 52%,#1e293b 100%);background-attachment:fixed;padding-top:var(--nav-h);}
    .navbar{display:flex;align-items:center;justify-content:space-between;padding:14px clamp(16px,4vw,42px);backdrop-filter:blur(18px) saturate(160%);-webkit-backdrop-filter:blur(18px) saturate(160%);background:linear-gradient(150deg,rgba(10,18,32,0.72),rgba(15,23,42,0.85));border-bottom:1px solid var(--border);box-shadow:0 10px 40px -18px #0008;position:fixed;inset:0 0 auto 0;height:var(--nav-h);z-index:120}
    .nav-left{display:flex;align-items:center;gap:14px}
    .nav-logo{height:48px;width:48px;object-fit:contain;border-radius:10px;filter:drop-shadow(0 4px 14px rgba(0,0,0,.35))}
    .nav-title{font-weight:800;letter-spacing:.6px;background:linear-gradient(90deg,var(--grad1),var(--grad2));-webkit-background-clip:text;color:transparent;font-size:1.15rem;margin-bottom:2px}
  .nav-actions{display:flex;gap:12px;align-items:center}
    .btn{--btn-bg:linear-gradient(90deg,var(--grad1),var(--grad2));background:var(--btn-bg);color:#fff;border:1px solid rgba(255,255,255,0.08);padding:10px 18px;border-radius:12px;font-weight:700;cursor:pointer;text-decoration:none;font-size:.8rem;letter-spacing:.3px;display:inline-flex;align-items:center;gap:6px;position:relative;overflow:hidden;transition:.28s;box-shadow:0 10px 34px -14px rgba(99,102,241,0.55),0 4px 14px -6px rgba(56,189,248,0.45)}
    .btn:hover{transform:translateY(-3px);box-shadow:0 16px 42px -14px rgba(99,102,241,0.65)}
    .btn.ghost{--btn-bg:linear-gradient(90deg,#ffffff,#eef2ff);background:var(--btn-bg);color:#0f172a;border:1px solid rgba(255,255,255,0.25);box-shadow:0 6px 22px -10px rgba(99,102,241,.45)}
    .btn.nav-active{outline:2px solid #38bdf8;outline-offset:0}
    /* Layout */
    .container{max-width:980px;margin:0 auto;padding:50px clamp(18px,4vw,42px);}
    .card{background:linear-gradient(145deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02));border:1px solid var(--border);backdrop-filter:blur(26px) saturate(170%);-webkit-backdrop-filter:blur(26px) saturate(170%);padding:34px clamp(20px,3.2vw,46px);border-radius:28px;box-shadow:0 18px 60px -18px rgba(0,0,0,0.55),0 6px 28px -10px rgba(0,0,0,0.55)}
    .card h3{margin:0 0 20px;font-size:1.5rem;font-weight:800;letter-spacing:.5px;background:linear-gradient(90deg,var(--grad1),var(--grad2),var(--grad3));-webkit-background-clip:text;color:transparent}
    form{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end}
  input,select{padding:14px 16px;border-radius:14px;border:1px solid rgba(0,0,0,0.2);background:linear-gradient(135deg,#ffffff,#f1f5f9);color:#0f172a;font:600 .8rem/1.2 Inter,system-ui,sans-serif;letter-spacing:.3px;outline:none;transition:.3s;min-width:220px}
  select option{color:#0f172a;background:#ffffff}
    input::placeholder{color:#94a3b8}
  input:focus,select:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.45);background:#ffffff}
    .result{margin-top:24px;padding:18px 20px;border-radius:20px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12)}
    .pdf{height:480px;border:1px solid rgba(255,255,255,0.2);margin-top:12px;border-radius:14px;width:100%;}
    footer{max-width:980px;margin:40px auto 22px;padding:0 clamp(18px,4vw,42px);color:#64748b;font-size:0.65rem;letter-spacing:.4px}
    @media (max-width:680px){form{flex-direction:column;align-items:stretch}input,select{width:100%;min-width:0}}
  </style>
<link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">
<link rel="apple-touch-icon" href="image/title-logo/Sofzenix IT Solutions.png">
</head>
<body>
  <?php require_once __DIR__ . '/admin_nav.php'; ?>
  <main class="container">
    <div class="card">
      <h3>Verify Certificate</h3>
      <form method="post">
        <input name="cert_number" placeholder="Certificate Number e.g. INT2025003" required>
        <select name="type" required>
          <option value="">-- Type --</option>
          <option value="Internship">Internship</option>
          <option value="Employee">Employee</option>
        </select>
        <button class="btn" type="submit">Verify</button>
      </form>

      <?php if($_SERVER['REQUEST_METHOD']==='POST'): ?>
        <?php if($found): ?>
          <div class="result">
            <h4>Certificate Found</h4>
            <p><strong>Certificate No:</strong> <?php echo htmlspecialchars($found['cert_number']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($found['name']); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($found['type']); ?></p>
            <p><strong>Issue Date:</strong> <?php echo htmlspecialchars($found['issue_date']); ?></p>
            <?php
              // determine file path: prefer stored_name from DB, else legacy 'file' field
              $filePath = '';
              if (!empty($found['stored_name'])) { $filePath = '../uploads/' . basename($found['stored_name']); }
              elseif (!empty($found['file'])) { $filePath = $found['file']; }
            ?>
            <?php if($filePath && is_pdf_file($filePath)): ?>
              <div>
                <iframe src="<?php echo htmlspecialchars($filePath); ?>" class="pdf" width="100%"></iframe>
              </div>
            <?php elseif($filePath): ?>
              <div><a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank">Download file</a></div>
            <?php else: ?>
              <p>No file available for this certificate.</p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="result">No matching certificate found.</div>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </main>
  <footer>© <?php echo date('Y'); ?> Sofzenix IT Solutions LLP.</footer>
</body>
</html>
