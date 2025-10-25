<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}
$data_file = __DIR__ . '/data/certificates.json';
$records = [];
// Try to load records from DB first
try {
    $stmt = $pdo->query('SELECT * FROM certificates ORDER BY uploaded_at DESC');
    $records = $stmt->fetchAll();
} catch (Exception $e) {
    // fall back to JSON file if DB unavailable
    if (file_exists($data_file)) {
        $json = file_get_contents($data_file);
        $records = json_decode($json, true) ?: [];
    }
}
$added = $_GET['added'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Certificates — Sofzenix</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--grad1:#38bdf8;--grad2:#6366f1;--grad3:#f472b6;--text:#f1f5f9;--muted:#94a3b8;--border:rgba(255,255,255,0.08);--nav-h:74px;}
    *{box-sizing:border-box;font-family:Inter,system-ui,Arial,sans-serif}
    body{margin:0;min-height:100vh;color:var(--text);background:
      radial-gradient(circle at 12% 18%,rgba(99,102,241,0.18),transparent 55%),
      radial-gradient(circle at 88% 75%,rgba(56,189,248,0.22),transparent 60%),
      linear-gradient(180deg,#020617 0%,#0f172a 52%,#1e293b 100%);background-attachment:fixed;padding-top:var(--nav-h);}
    /* Unified Navbar */
    .navbar{display:flex;align-items:center;justify-content:space-between;padding:14px clamp(16px,4vw,42px);backdrop-filter:blur(18px) saturate(160%);-webkit-backdrop-filter:blur(18px) saturate(160%);background:linear-gradient(150deg,rgba(10,18,32,0.72),rgba(15,23,42,0.85));border-bottom:1px solid var(--border);box-shadow:0 10px 40px -18px #0008;position:fixed;inset:0 0 auto 0;height:var(--nav-h);z-index:120}
    .nav-left{display:flex;align-items:center;gap:14px}
    .nav-logo{height:48px;width:48px;object-fit:contain;border-radius:10px;filter:drop-shadow(0 4px 14px rgba(0,0,0,.35))}
    .nav-title{font-weight:800;letter-spacing:.6px;background:linear-gradient(90deg,var(--grad1),var(--grad2));-webkit-background-clip:text;color:transparent;font-size:1.15rem;margin-bottom:2px}
    .nav-actions{display:flex;gap:12px;align-items:center}
    .btn{--btn-bg:linear-gradient(90deg,var(--grad1),var(--grad2));background:var(--btn-bg);color:#fff;border:1px solid rgba(255,255,255,0.08);padding:10px 18px;border-radius:12px;font-weight:700;cursor:pointer;text-decoration:none;font-size:.8rem;letter-spacing:.3px;display:inline-flex;align-items:center;gap:6px;position:relative;overflow:hidden;transition:.28s cubic-bezier(.4,.4,.2,1);box-shadow:0 10px 34px -14px rgba(99,102,241,0.55),0 4px 14px -6px rgba(56,189,248,0.45)}
    .btn:hover{transform:translateY(-3px);box-shadow:0 16px 42px -14px rgba(99,102,241,0.65)}
    .btn.ghost{--btn-bg:linear-gradient(90deg,#ffffff,#eef2ff);background:var(--btn-bg);color:#0f172a;border:1px solid rgba(255,255,255,0.25);box-shadow:0 6px 22px -10px rgba(99,102,241,.45)}
    .btn.nav-active{outline:2px solid #38bdf8;outline-offset:0}
    /* Page layout */
    .container{max-width:1180px;margin:0 auto;padding:40px clamp(18px,4vw,38px);}    
    table{width:100%;border-collapse:collapse;background:linear-gradient(135deg,rgba(255,255,255,0.08),rgba(255,255,255,0.03));border:1px solid var(--border);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-radius:22px;overflow:hidden;box-shadow:0 18px 50px -20px #000}
    th,td{padding:14px 18px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.08);font-size:.75rem;letter-spacing:.5px}
    th{background:rgba(255,255,255,0.04);font-weight:800;color:#e2e8f0;text-transform:uppercase;font-size:.6rem;letter-spacing:1px}
    tr:last-child td{border-bottom:0}
    tbody tr:hover{background:rgba(255,255,255,0.05)}
    .actions a{margin-right:6px;padding:6px 10px;border-radius:10px;text-decoration:none;font-weight:700;font-size:.6rem;letter-spacing:.5px;display:inline-block}
    .edit{background:linear-gradient(90deg,#fbbf24,#f59e0b);color:#111}
    .delete{background:#ef4444;color:#fff}
    .view{background:#10b981;color:#fff}
    .notice{margin:18px 0 24px;padding:14px 16px;background:rgba(16,185,129,0.12);color:#6ee7b7;border:1px solid rgba(16,185,129,0.4);border-radius:18px;font-size:.7rem;letter-spacing:.5px;font-weight:600}
    .error{margin:18px 0 24px;padding:14px 16px;background:#dc2626cc;color:#fff;border-radius:18px;font-size:.7rem;letter-spacing:.5px;font-weight:600}
    .empty{padding:40px 30px;text-align:center;color:#94a3b8;font-weight:600;font-size:.8rem;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:22px}
    footer{max-width:1180px;margin:40px auto 22px;padding:0 clamp(18px,4vw,38px);color:#64748b;font-size:0.65rem;letter-spacing:.4px}
    @media (max-width:820px){th,td{padding:12px 12px;font-size:.65rem}.actions a{margin-right:4px}}    
    @media (max-width:640px){.btn,.btn.ghost{padding:9px 14px;font-size:.7rem;border-radius:10px}.nav-logo{height:40px;width:40px}table{font-size:.65rem}}
  </style>
<link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">
<link rel="apple-touch-icon" href="image/title-logo/Sofzenix IT Solutions.png">
</head>
<body>
  <?php require_once __DIR__ . '/admin_nav.php'; ?>
  <main class="container">
    <?php if(!empty($_SESSION['upload_error'])): ?>
      <div class="error"><?php echo htmlspecialchars($_SESSION['upload_error']); unset($_SESSION['upload_error']); ?></div>
    <?php endif; ?>
    <?php if(!empty($_SESSION['db_error'])): ?>
      <div class="error"><?php echo htmlspecialchars($_SESSION['db_error']); unset($_SESSION['db_error']); ?></div>
    <?php endif; ?>
    <?php if($added): ?>
      <div class="notice">Certificate added successfully. ID: <?php echo htmlspecialchars($added); ?></div>
    <?php endif; ?>

    <?php if(empty($records)): ?>
      <div class="empty card">No certificates found. Use Add Certificate to create one.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Certificate No</th>
            <th>Name</th>
            <th>DOB</th>
            <th>Type</th>
            <th>Issue Date</th>
            <th>File</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($records as $i => $r): ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><?php echo htmlspecialchars($r['cert_number'] ?? $r['certno'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['dob'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['type'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['issue_date'] ?? ''); ?></td>
              <td><?php if(!empty($r['stored_name']) || !empty($r['file'])): ?>
                    <?php $file = !empty($r['stored_name']) ? ('../uploads/'.basename($r['stored_name'])) : (!empty($r['file']) ? $r['file'] : ''); ?>
                    <?php if($file): ?><a class="view" href="<?php echo htmlspecialchars($file); ?>" target="_blank">View</a><?php endif; ?>
                  <?php else: ?>-
                  <?php endif; ?></td>
              <td class="actions">
                <a class="edit" href="edit.php?id=<?php echo urlencode($r['id'] ?? $r['id']); ?>">Edit</a>
                <a class="delete" href="delete.php?id=<?php echo urlencode($r['id'] ?? $r['id']); ?>" onclick="return confirm('Delete this certificate?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>
  <footer>© <?php echo date('Y'); ?> Sofzenix IT Solutions LLP.</footer>
</body>
</html>
