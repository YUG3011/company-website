<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}
$id = $_GET['id'] ?? '';
$id = intval($id);
if (!$id) { header('Location: manage.php'); exit; }

$record = null; $index = null;
// Try DB lookup
try {
    $stmt = $pdo->prepare('SELECT * FROM certificates WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch();
} catch (Exception $e) {
    // fallback to JSON
}

// If DB not available, fallback to JSON file
if (!$record) {
    $data_file = __DIR__ . '/data/certificates.json';
    $records = [];
    if (file_exists($data_file)) $records = json_decode(file_get_contents($data_file), true) ?: [];
    foreach ($records as $k => $r) { if (!empty($r['id']) && ((int)$r['id'] === $id)) { $record = $r; $index = $k; break; } }
    if (!$record) { header('Location: manage.php'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate
    $cert_number = trim($_POST['cert_number'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $issue_date = trim($_POST['issue_date'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    if ($cert_number === '' || $name === '' || $type === '' || $issue_date === '' || $dob === '') {
        $_SESSION['upload_error'] = 'All fields except file are required.';
        header('Location: edit.php?id=' . urlencode($id)); exit;
    }

    $upload_dir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads/');
    if (is_string($upload_dir) && substr($upload_dir, -1) !== DIRECTORY_SEPARATOR) $upload_dir .= DIRECTORY_SEPARATOR;

    // If DB available, update via transaction
    if (isset($pdo) && $record && isset($record['id'])) {
        try {
            $pdo->beginTransaction();
            // Handle optional file upload
            $newStored = null; $newMime = null; $newSize = null;
            if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['certificate_file']['tmp_name'];
                $nameRaw = basename($_FILES['certificate_file']['name']);
                $ext = strtolower(pathinfo($nameRaw, PATHINFO_EXTENSION));
                $allowed = ['pdf','jpg','jpeg','png','gif'];
                if (!in_array($ext, $allowed)) throw new Exception('File type not allowed');
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $newName = uniqid('cert_', true) . '.' . $ext;
                $dest = $upload_dir . $newName;
                if (!move_uploaded_file($tmp, $dest)) throw new Exception('Failed to move uploaded file.');
                $newStored = 'uploads/' . $newName;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $newMime = finfo_file($finfo, $dest);
                $newSize = filesize($dest);
                if ($finfo) finfo_close($finfo);
                // delete old file safely
                if (!empty($record['stored_name'])) {
                    $old = realpath(__DIR__ . '/../' . ltrim($record['stored_name'],'/'));
                    $realUploadDir = realpath(__DIR__ . '/../uploads');
                    if ($old && $realUploadDir && strpos($old, $realUploadDir) === 0) @unlink($old);
                }
            }

            // Include dob in update
            $update = $pdo->prepare('UPDATE certificates SET cert_number = :cn, name = :n, type = :t, issue_date = :idate, dob = :dob' .
                ($newStored ? ', stored_name = :sn, mime = :mime, size = :size' : '') . ' WHERE id = :id');
            $params = [':cn'=>$cert_number, ':n'=>$name, ':t'=>$type, ':idate'=>$issue_date, ':dob'=>($dob ?: null), ':id'=>$id];
            if ($newStored) { $params[':sn']=$newStored; $params[':mime']=$newMime; $params[':size']=$newSize; }
            $update->execute($params);
            $pdo->commit();
            header('Location: manage.php'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['upload_error'] = 'Update failed: ' . $e->getMessage();
            header('Location: edit.php?id=' . urlencode($id)); exit;
        }
    }

    // Legacy JSON update
    $records[$index]['cert_number'] = htmlspecialchars($cert_number);
    $records[$index]['name'] = htmlspecialchars($name);
    $records[$index]['type'] = htmlspecialchars($type);
    $records[$index]['issue_date'] = htmlspecialchars($issue_date);
    $records[$index]['dob'] = htmlspecialchars($dob);
    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir2 = __DIR__ . '/../uploads/'; if (!is_dir($upload_dir2)) mkdir($upload_dir2,0755,true);
        $tmp = $_FILES['certificate_file']['tmp_name']; $nameRaw = basename($_FILES['certificate_file']['name']); $ext = strtolower(pathinfo($nameRaw, PATHINFO_EXTENSION));
        $allowed = ['pdf','jpg','jpeg','png','gif'];
        if (in_array($ext,$allowed)) {
            $new = uniqid('cert_',true) . '.' . $ext; $dest = $upload_dir2 . $new;
            if (move_uploaded_file($tmp,$dest)) {
                if (!empty($records[$index]['file'])) { $old = __DIR__ . '/../' . ltrim($records[$index]['file'],'/'); if (file_exists($old)) @unlink($old); }
                $records[$index]['file'] = '../uploads/' . $new;
            }
        }
    }
    file_put_contents(__DIR__ . '/data/certificates.json', json_encode($records, JSON_PRETTY_PRINT));
    header('Location: manage.php'); exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Certificate</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--grad1:#38bdf8;--grad2:#6366f1;--grad3:#f472b6;--text:#f1f5f9;--muted:#94a3b8;--border:rgba(255,255,255,0.08);--nav-h:74px}
        *{box-sizing:border-box;font-family:Inter,system-ui,Arial,sans-serif}
        body{margin:0;min-height:100vh;color:var(--text);background:
            radial-gradient(circle at 12% 18%,rgba(99,102,241,0.18),transparent 55%),
            radial-gradient(circle at 88% 75%,rgba(56,189,248,0.22),transparent 60%),
            linear-gradient(180deg,#020617 0%,#0f172a 52%,#1e293b 100%);background-attachment:fixed;padding: calc(var(--nav-h) + 40px) clamp(18px,4vw,42px) 60px}
        .navbar{display:flex;align-items:center;justify-content:space-between;padding:14px clamp(16px,4vw,42px);backdrop-filter:blur(18px) saturate(160%);-webkit-backdrop-filter:blur(18px) saturate(160%);background:linear-gradient(150deg,rgba(10,18,32,0.72),rgba(15,23,42,0.85));border-bottom:1px solid var(--border);box-shadow:0 10px 40px -18px #0008;position:fixed;inset:0 0 auto 0;height:var(--nav-h);z-index:120}
        .nav-left{display:flex;align-items:center;gap:14px}
        .nav-logo{height:48px;width:48px;object-fit:contain;border-radius:10px;filter:drop-shadow(0 4px 14px rgba(0,0,0,.35))}
        .nav-title{font-weight:800;letter-spacing:.6px;background:linear-gradient(90deg,var(--grad1),var(--grad2));-webkit-background-clip:text;color:transparent;font-size:1.15rem;margin-bottom:2px}
        .nav-actions{display:flex;gap:12px;align-items:center}
        .btn{--btn-bg:linear-gradient(90deg,var(--grad1),var(--grad2));background:var(--btn-bg);color:#fff;border:1px solid rgba(255,255,255,0.08);padding:10px 18px;border-radius:12px;font-weight:700;cursor:pointer;text-decoration:none;font-size:.75rem;letter-spacing:.3px;display:inline-flex;align-items:center;gap:6px;position:relative;overflow:hidden;transition:.28s;box-shadow:0 10px 34px -14px rgba(99,102,241,0.55)}
        .btn:hover{transform:translateY(-3px);box-shadow:0 16px 42px -14px rgba(99,102,241,0.65)}
        .btn.ghost{--btn-bg:linear-gradient(90deg,#ffffff,#eef2ff);background:var(--btn-bg);color:#0f172a;border:1px solid rgba(255,255,255,0.25);box-shadow:0 6px 22px -10px rgba(99,102,241,.45)}
        .btn.nav-active{outline:2px solid #38bdf8;outline-offset:0}
        .card{max-width:780px;margin:auto;background:linear-gradient(145deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02));border:1px solid var(--border);backdrop-filter:blur(26px) saturate(170%);-webkit-backdrop-filter:blur(26px) saturate(170%);padding:38px clamp(26px,3vw,48px);border-radius:32px;box-shadow:0 18px 60px -18px rgba(0,0,0,0.55)}
        h2{margin:0 0 24px;font-size:1.4rem;font-weight:800;letter-spacing:.5px;background:linear-gradient(90deg,var(--grad1),var(--grad2),var(--grad3));-webkit-background-clip:text;color:transparent}
        label{display:block;margin-top:18px;font-weight:700;color:var(--muted);font-size:.65rem;letter-spacing:1px;text-transform:uppercase}
        input,select{width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,0.2);background:linear-gradient(135deg,#ffffff,#f1f5f9);color:#0f172a;font-weight:600;margin-top:8px;font-size:.8rem;outline:none;transition:.3s}
        select option{color:#0f172a;background:#ffffff}
        input:focus,select:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.45);background:#ffffff}
        .actions{margin-top:28px;display:flex;gap:12px;flex-wrap:wrap}
        .actions .btn{flex:0 0 auto}
        @media (max-width:640px){body{padding: calc(var(--nav-h) + 24px) 22px 48px}.card{padding:32px 26px;border-radius:28px}}
    </style>
<link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">
<link rel="apple-touch-icon" href="image/title-logo/Sofzenix IT Solutions.png">
</head>
<body>
    <?php require_once __DIR__ . '/admin_nav.php'; ?>
    <div class="card">
    <h2>Edit Certificate</h2>
    <form method="post" enctype="multipart/form-data">
      <label>Certificate Number<input name="cert_number" value="<?php echo htmlspecialchars($record['cert_number']); ?>"></label>
      <label>Full Name<input name="name" value="<?php echo htmlspecialchars($record['name']); ?>"></label>
      <label>Type<select name="type"><option value="">-- Select --</option><option value="Internship" <?php echo $record['type']==='Internship'?'selected':''; ?>>Internship</option><option value="Employee" <?php echo $record['type']==='Employee'?'selected':''; ?>>Employee</option></select></label>
      <label>Issue Date<input type="date" name="issue_date" value="<?php echo htmlspecialchars($record['issue_date']); ?>"></label>
    <label>Date of Birth<input type="date" name="dob" value="<?php echo htmlspecialchars($record['dob'] ?? ''); ?>"></label>
      <label>Replace File (optional)<input type="file" name="certificate_file" accept="application/pdf,image/*"></label>
      <div class="actions"><button class="btn" type="submit">Save Changes</button> <a href="manage.php" class="btn" style="background:gray">Cancel</a></div>
    </form>
  </div>
</body>
</html>
