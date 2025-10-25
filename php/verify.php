<?php
// Public certificate verification page (no login required)
require_once __DIR__ . '/config.php';

// --- Maintenance gate (test-only): if file php/maintenance.flag exists, show a 503 maintenance message.
// Create the file c:\xampp\htdocs\COMPANY-WEBSITE\php\maintenance.flag to enable maintenance for this page.
$maintenance_flag = __DIR__ . '/maintenance.flag';
if (file_exists($maintenance_flag)) {
  // Send 503 Service Unavailable so automated clients and search engines know this is temporary
  if (!headers_sent()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable', true, 503);
    header('Retry-After: 3600'); // optional: advise clients to retry after 1 hour
    header('Content-Type: text/html; charset=utf-8');
  }
  // Minimal inline maintenance page (keeps it self-contained for testing)
  echo '<!doctype html><html><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width,initial-scale=1" /><title>Verification temporarily unavailable</title>';
  echo '<style>body{font-family:Inter,system-ui,Arial,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;height:100vh;margin:0} .panel{max-width:820px;padding:34px;border-radius:16px;background:linear-gradient(180deg,#0b1220,#0f172a);border:1px solid rgba(255,255,255,0.04);text-align:center;box-shadow:0 18px 46px -18px #000} .icon{width:84px;margin:0 auto 12px;display:block} .icon svg{width:84px;height:84px;display:block} h1{margin:0 0 12px;font-size:1.6rem;font-weight:800;background:linear-gradient(90deg,#38bdf8,#6366f1);-webkit-background-clip:text;color:transparent} p.lead{color:#cbd5e1;margin:8px 0 0;font-size:1rem;line-height:1.5}</style><link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">

</head><body><div class="panel"><span class="icon" aria-hidden="true">' .
       '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true">' .
         '<circle cx="12" cy="12" r="10" fill="rgba(56,189,248,0.06)" stroke="#38bdf8" stroke-width="1.2"/>' .
         '<path d="M9.5 12.5l1.5-1.5" stroke="#38bdf8" stroke-width="1.4" stroke-linecap="round"/>' .
         '<path d="M13 15l2-2" stroke="#38bdf8" stroke-width="1.4" stroke-linecap="round"/>' .
         '<path d="M8 8l8 8" stroke="#6366f1" stroke-width="1" stroke-linecap="round" stroke-opacity="0.18"/>' .
    '</svg>' .
    '</span><h1>Verification temporarily unavailable</h1><p class="lead">We are doing a quick health check on the verification service. Please try again in a few minutes. If this takes longer than expected, contact us at <a href="mailto:contact@sofzenix.in" style="color:#38bdf8;text-decoration:none">contact@sofzenix.in</a> or call <strong>+91 63058 18324</strong>.</p>' .
    '<div style="margin-top:20px"><a href="../index.html" style="display:inline-block;background:linear-gradient(90deg,#38bdf8,#6366f1);color:#fff;padding:12px 20px;border-radius:12px;text-decoration:none;font-weight:700;box-shadow:0 12px 36px -16px rgba(99,102,241,0.45)">Back to Home</a></div></div></body></html>';
  exit;
}
$found = null; $error = ''; $num=''; $type='';
// Handle PDF download request
if (isset($_GET['download']) && $_GET['download'] === '1') {
  $d_num = trim($_GET['cert_number'] ?? '');
  $d_type = trim($_GET['type'] ?? '');
  if ($d_num && $d_type) {
    $download_found = null;
    if ($pdo) {
      try {
        $stmt = $pdo->prepare('SELECT * FROM certificates WHERE LOWER(cert_number)=LOWER(:num) AND LOWER(type)=LOWER(:type) LIMIT 1');
        $stmt->execute([':num'=>$d_num,':type'=>$d_type]);
        $download_found = $stmt->fetch();
      } catch (Exception $e) {
        $data_file = __DIR__ . '/data/certificates.json';
        $records = file_exists($data_file) ? (json_decode(file_get_contents($data_file), true) ?: []) : [];
        foreach ($records as $r) {
          if (strcasecmp($r['cert_number'],$d_num)===0 && strcasecmp($r['type'],$d_type)===0) { $download_found=$r; break; }
        }
      }
    } else {
      $data_file = __DIR__ . '/data/certificates.json';
      $records = file_exists($data_file) ? (json_decode(file_get_contents($data_file), true) ?: []) : [];
      foreach ($records as $r) {
        if (strcasecmp($r['cert_number'],$d_num)===0 && strcasecmp($r['type'],$d_type)===0) { $download_found=$r; break; }
      }
    }
    if ($download_found) {
      $pdf_path = '';
      if (!empty($download_found['stored_name'])) {
        $pdf_path = __DIR__ . '/../uploads/' . basename($download_found['stored_name']);
      } elseif (!empty($download_found['file'])) {
        $pdf_path = $download_found['file'];
        if (!file_exists($pdf_path)) {
          $pdf_path = __DIR__ . '/../' . ltrim($pdf_path, '/');
        }
      }
      if ($pdf_path && file_exists($pdf_path) && is_pdf($pdf_path)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($pdf_path) . '"');
        header('Content-Length: ' . filesize($pdf_path));
        readfile($pdf_path);
        exit;
      }
    }
  }
  // If download fails, redirect back
  header('Location: verify.php');
  exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $num = trim($_POST['cert_number'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $dob_input = trim($_POST['dob'] ?? '');
  if ($num === '' || $type === '' || $dob_input === '') { $error = 'Enter certificate number, type and date of birth.'; }
  else {
    // If PDO is available, try DB first. If PDO is null (DB offline/maintenance), fall back to the JSON file.
    if ($pdo) {
      try {
        // Try to match by cert_number, type and dob (if dob column exists in DB). If dob column missing, match by cert_number+type then compare dob in PHP.
        // Use case-insensitive match for cert_number and type; dob is compared as date string (YYYY-MM-DD)
        $stmt = $pdo->prepare('SELECT * FROM certificates WHERE LOWER(cert_number)=LOWER(:num) AND LOWER(type)=LOWER(:type) LIMIT 1');
        $stmt->execute([':num'=>$num,':type'=>$type]);
        $found = $stmt->fetch();
        if ($found) {
          // If DB has a dob field, compare it. If not, we'll handle in PHP for fallback records.
          if (!empty($found['dob'])) {
            // Normalize both to YYYY-MM-DD and compare
            $dbDob = substr($found['dob'],0,10);
            $inDob = substr($dob_input,0,10);
            if ($dbDob !== $inDob) {
              $found = null; $error = 'Date of birth does not match our records.';
            }
          } else {
            // No dob stored in DB record — treat as mismatch to be safe
            $found = null; $error = 'Date of birth does not match our records.';
          }
        } else {
          $error = 'No matching certificate found.';
        }
      } catch (Exception $e) {
        // On DB query error, fallback to json
        $data_file = __DIR__ . '/data/certificates.json';
        $records = file_exists($data_file) ? (json_decode(file_get_contents($data_file), true) ?: []) : [];
        foreach ($records as $r) {
          if (strcasecmp($r['cert_number'],$num)===0 && strcasecmp($r['type'],$type)===0) {
            // compare dob if present in record
            $rDob = isset($r['dob']) ? substr($r['dob'],0,10) : '';
            if ($rDob !== '') {
              if ($rDob === substr($dob_input,0,10)) { $found=$r; break; }
            } else {
              // If no dob stored, treat as mismatch (do not return found)
            }
          }
        }
        if (!$found) $error = $error ?: 'No matching certificate found or DOB mismatch.';
      }
    } else {
      // PDO not available (maintenance or DB connection error) — use JSON fallback only
      $data_file = __DIR__ . '/data/certificates.json';
      $records = file_exists($data_file) ? (json_decode(file_get_contents($data_file), true) ?: []) : [];
      foreach ($records as $r) {
        if (strcasecmp($r['cert_number'],$num)===0 && strcasecmp($r['type'],$type)===0) {
          $rDob = isset($r['dob']) ? substr($r['dob'],0,10) : '';
          if ($rDob !== '' && $rDob === substr($dob_input,0,10)) { $found=$r; break; }
        }
      }
      if (!$found) $error = 'No matching certificate found or DOB mismatch.';
    }
  }
}
function is_pdf($file){ return strtolower(pathinfo($file, PATHINFO_EXTENSION))==='pdf'; }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Certificate Verification • Sofzenix</title>
<link rel="stylesheet" href="../style.css" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700,800&display=swap" rel="stylesheet">
<style>
/* Page layout + background (reuse index animated look) */
body.page-fade-in{margin:0;font-family:Inter,system-ui,Arial,sans-serif;min-height:100vh;display:flex;flex-direction:column;color:#e2e8f0;position:relative;overflow-x:hidden}
/* Reuse button styles from index (copied + path safe) */
.verify-btn,.get-started{display:inline-block;padding:8px 14px;border-radius:10px;font-weight:700;text-decoration:none;margin-left:0!important;transition:transform .15s ease,box-shadow .15s ease;font-size:.95rem}
.verify-btn{background:linear-gradient(90deg,#ffffff,#eef2ff);color:#0f172a;border:1px solid rgba(99,102,241,0.12);box-shadow:0 6px 18px rgba(99,102,241,0.06)}
.verify-btn:hover{transform:translateY(-3px);box-shadow:0 10px 30px rgba(99,102,241,0.12)}
.get-started{background:linear-gradient(90deg,#38bdf8,#6366f1);color:#fff;border:0;box-shadow:0 10px 34px -14px rgba(99,102,241,0.5)}
.get-started:hover{transform:translateY(-3px)}
@media (max-width:900px){.verify-btn,.get-started{padding:8px 10px;font-size:.9rem}}
.navbar-container{display:flex;align-items:center;gap:0}
.nav-links{margin-right:auto}
/* Verification card */
.verify-wrapper{flex:1;display:flex;justify-content:center;padding:140px 22px 80px;position:relative;z-index:2}
.verify-card{width:100%;max-width:680px;background:linear-gradient(150deg,rgba(255,255,255,0.09),rgba(255,255,255,0.04));border:1px solid rgba(255,255,255,0.14);padding:46px 44px 52px;border-radius:38px;backdrop-filter:blur(34px) saturate(1.6);box-shadow:0 28px 80px -34px #000}
.verify-card h1{margin:0 0 14px;font-size:1.8rem;font-weight:800;letter-spacing:.6px;background:linear-gradient(90deg,#38bdf8,#6366f1,#f472b6);-webkit-background-clip:text;color:transparent}
.verify-card p.lead{margin:0 0 30px;font-size:.92rem;line-height:1.55;color:#cbd5e1;font-weight:500}
/* Success banner shown after a successful verification */
.verify-success{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;background:linear-gradient(90deg,rgba(34,197,94,0.08),rgba(34,197,94,0.03));border:1px solid rgba(34,197,94,0.14);color:#d1fae5;margin-bottom:18px}
.verify-success .icon{width:44px;height:44px;flex:0 0 44px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;background:rgba(34,197,94,0.12)}
.verify-success .icon svg{width:24px;height:24px}
.verify-success .text{font-weight:800;color:#bbf7d0}
.verify-success .sub{font-weight:600;color:#c7f9d8;font-size:.86rem;opacity:.95}
form.verify-form{display:grid;gap:18px;margin:0 0 6px;grid-template-columns:1fr 170px 130px}
@media (max-width:620px){form.verify-form{grid-template-columns:1fr 1fr}form.verify-form button{grid-column:1/-1}}
form.verify-form input,form.verify-form select{padding:14px 16px;border-radius:18px;border:1px solid rgba(255,255,255,0.18);background:rgba(15,23,42,0.65);color:#f1f5f9;font-weight:500;outline:none;font-size:.85rem;letter-spacing:.4px;transition:.35s;appearance:none;-webkit-appearance:none}
form.verify-form input:focus,form.verify-form select:focus{border-color:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,.3)}
form.verify-form button{background:linear-gradient(120deg,#38bdf8,#6366f1,#f472b6);background-size:200% 100%;border:none;color:#fff;font-weight:800;letter-spacing:.7px;font-size:.95rem;border-radius:18px;cursor:pointer;box-shadow:0 14px 40px -20px #6366f199;transition:.28s;display:inline-flex;align-items:center;justify-content:center;padding:12px 20px;min-height:46px}
form.verify-form button:hover{background-position:100% 50%;transform:translateY(-3px)}
.notice{margin:8px 0 0;font-size:.7rem;letter-spacing:.6px;color:#94a3b8}
.error{margin:16px 0 0;background:#dc2626cc;color:#fff;padding:12px 18px;border-radius:18px;font-size:.7rem;font-weight:600}
.result{margin:30px 0 0}
.verify-card.has-result{max-width:1280px}
.verify-result-split{display:grid;grid-template-columns:minmax(260px,340px) 1fr;gap:34px;align-items:start}
.meta-panel{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:28px;padding:26px 26px 30px;display:flex;flex-direction:column;gap:16px}
.meta-panel h2{margin:0 0 4px;font-size:1rem;font-weight:800;letter-spacing:.5px;color:#f1f5f9}
.meta-items{display:flex;flex-direction:column;gap:10px;font-size:.7rem;font-weight:600}
.meta-item{display:flex;flex-direction:column;gap:4px;padding:10px 14px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:14px}
.meta-item span.label{font-size:.62rem;letter-spacing:1px;text-transform:uppercase;color:#94a3b8;font-weight:700}
.pdf-pane{background:#0f172a;border:1px solid rgba(255,255,255,0.14);border-radius:28px;padding:24px 24px 30px;height:50vh;overflow:auto;display:flex;flex-direction:column;gap:20px;align-items:center}
.pdf-pane .pdf-page-canvas{background:#fff;max-width:100%;height:auto;display:block;box-shadow:0 10px 36px -18px #000c !important;border-radius:14px}
.pdf-pane .pdf-fallback-frame{width:100%;height:100%;border:0;flex:1;border-radius:14px;background:#111827}
.pdf-nav{display:flex;align-items:center;gap:12px;font-size:.6rem;letter-spacing:.9px;font-weight:700;color:#94a3b8}
.pdf-nav button{background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);color:#e2e8f0;padding:6px 12px;border-radius:12px;font-size:.55rem;font-weight:700;letter-spacing:.8px;cursor:pointer;transition:.3s}
.pdf-nav button:hover{background:rgba(255,255,255,0.16)}
.pdf-nav button:disabled{opacity:.35;cursor:not-allowed}
@media (max-width:960px){.verify-result-split{grid-template-columns:1fr}.pdf-pane{height:auto}}
footer{padding:50px 0 46px;text-align:center;font-size:.6rem;letter-spacing:.6px;color:#64748b;z-index:2}
/* Subtle overlay glows */
.verify-glow:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 70% 30%,rgba(99,102,241,.25),transparent 70%),radial-gradient(circle at 25% 70%,rgba(56,189,248,.25),transparent 70%);mix-blend-mode:overlay;opacity:.55;pointer-events:none}
</style>
<link rel="icon" type="image/png" href="image/title-logo/Sofzenix IT Solutions.png">
<link rel="apple-touch-icon" href="image/title-logo/Sofzenix IT Solutions.png">
</head>
<body class="page-fade-in">
  <style>body{padding-top:78px}</style>
  <div class="animated-bg"></div>
  <div class="floating-shapes">
    <div class="shape shape1"></div>
    <div class="shape shape2"></div>
    <div class="shape shape3"></div>
    <div class="shape shape4"></div>
  </div>
  <!-- Unified User Navbar (same as public site) -->
  <nav class="sfz-navbar">
    <div class="sfz-nav-inner">
      <div class="sfz-brand"><a href="../index.html"><img src="../Sofzenix IT Solution.png" alt="Sofzenix IT Solutions LLP Logo"></a></div>
      <input type="checkbox" id="sfz-nav-toggle" aria-label="Toggle navigation">
      <label for="sfz-nav-toggle" class="sfz-burger" aria-label="Menu"><span></span><span></span><span></span></label>
      <ul class="sfz-links">
        <li><a href="../index.html#home">Home</a></li>
         <li><a href="../about.html">About</a></li>
                 <li><a href="../index.html#portfolio">Portfolio</a></li>

        <li class="sfz-drop">
          <input type="checkbox" id="sfz-services" class="sfz-drop-toggle">
          <label for="sfz-services" class="sfz-drop-label">Services</label>
          <div class="sfz-drop-panel">
            <a href="../web-dev.html">Web Platforms</a>
            <a href="../mobile-app-dev.html">Mobile Products</a>
            <a href="../digital-marketing.html">Digital Marketing</a>
            <a href="../ai.html">AI & Automation</a>
            <a href="../graphic-designing.html">UI & UX</a>
            <a href="../saleforce-dev.html">Salesforce Cloud</a>
            <a href="../maintenance-support.html">Managed Ops & 24/7</a>
          </div>
        </li>
                  <li><a href="../partners.html">Partners</a></li>
                  <li><a href="../hiring.html">Hire</a></li>
        <li><a href="../index.html#contact">Contact</a></li>
        <li class="sfz-mobile-actions">
          <div class="sfz-mobile-btns">
            <a href="verify.php" class="verify-btn">Verify</a>
            <a href="login.php" class="get-started">Login</a>
          </div>
        </li>
      </ul>
      <div class="sfz-actions">
        <a href="verify.php" class="verify-btn">Verify</a>
        <a href="login.php" class="get-started">Login</a>
      </div>
    </div>
  </nav>

  <div class="verify-wrapper verify-glow">
    <section class="verify-card">
      <h1>Verify a Certificate</h1>
  <p class="lead">Enter the certificate number, type (Internship / Employee) and date of birth to validate authenticity. Only PDF certificates display inline.</p>
      <form method="post" class="verify-form" autocomplete="off" novalidate>
        <input name="cert_number" placeholder="Certificate Number" value="<?php echo htmlspecialchars($num); ?>" required />
        <select name="type" required>
          <option value="" <?php if($type==='') echo 'selected';?>>Type</option>
          <option value="Internship" <?php if(strcasecmp($type,'Internship')===0) echo 'selected';?>>Internship</option>
          <option value="Employee" <?php if(strcasecmp($type,'Employee')===0) echo 'selected';?>>Employee</option>
        </select>
        <input name="dob" type="date" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>" required placeholder="Date of birth" />
        <button type="submit">Verify →</button>
      </form>
      <div class="notice">Case-insensitive match. PDFs render below if found.</div>
      <?php if($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <?php if($found): ?>
        <div class="result">
          <div class="verify-success" role="status" aria-live="polite">
            <span class="icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true">
                <circle cx="12" cy="12" r="10" fill="rgba(34,197,94,0.08)" stroke="#22c55e" stroke-width="1"/>
                <path d="M7.5 12.5l2.5 2.5 6-7" stroke="#16a34a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <div>
              <div class="text">Certificate verified</div>
              <div class="sub">The certificate matches our records.</div>
            </div>
            
          </div>
          <div class="verify-result-split">
            <div class="meta-panel">
              <h2>Certificate Info</h2>
              <div class="meta-items">
                <div class="meta-item"><span class="label">Certificate #</span><?php echo htmlspecialchars($found['cert_number']); ?></div>
                <div class="meta-item"><span class="label">Name</span><?php echo htmlspecialchars($found['name']); ?></div>
                <div class="meta-item"><span class="label">Type</span><?php echo htmlspecialchars($found['type']); ?></div>
                <div class="meta-item"><span class="label">Issue Date</span><?php echo htmlspecialchars($found['issue_date']); ?></div>
              </div>
            </div>
            <div class="pdf-pane" id="pdf-viewer" data-pdf="<?php echo !empty($found['stored_name']) && is_pdf($found['stored_name']) ? htmlspecialchars('../uploads/'.basename($found['stored_name'])) : (!empty($found['file']) && is_pdf($found['file']) ? htmlspecialchars($found['file']) : ''); ?>">
              <?php if((!empty($found['stored_name']) && is_pdf($found['stored_name'])) || (!empty($found['file']) && is_pdf($found['file']))): ?>
                <div style="font-size:.65rem;color:#64748b;font-weight:600;letter-spacing:.8px;">Loading PDF...</div>
              <?php elseif(!empty($found['stored_name']) || !empty($found['file'])): ?>
                <div style="font-size:.7rem;color:#94a3b8">File is not PDF. <a href="<?php echo htmlspecialchars((!empty($found['stored_name']) ? ('../uploads/'.basename($found['stored_name'])) : $found['file'])); ?>" target="_blank" style="color:#38bdf8;text-decoration:none;font-weight:600">Download</a></div>
              <?php else: ?>
                <div style="font-size:.7rem;color:#94a3b8">No file attached.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php if((!empty($found['stored_name']) && is_pdf($found['stored_name'])) || (!empty($found['file']) && is_pdf($found['file']))): ?>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
          <script>
            (function(){
              const container = document.getElementById('pdf-viewer');
              const url = container.getAttribute('data-pdf');
              if(!url){return;}
              if(!window.pdfjsLib){
                container.innerHTML = '<iframe class="pdf-fallback-frame" src="'+url+'#toolbar=0&navpanes=0&scrollbar=0"></iframe>';
                return;
              }
              pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
              pdfjsLib.getDocument(url).promise.then(function(pdf){
                container.querySelector('div')?.remove();
                let currentPage = 1; const total = pdf.numPages;
                const canvas = document.createElement('canvas');
                canvas.className='pdf-page-canvas';
                const ctx = canvas.getContext('2d');
                const nav = document.createElement('div');
                nav.className='pdf-nav';
                const prevBtn = document.createElement('button'); prevBtn.textContent='Prev';
                const info = document.createElement('span');
                const nextBtn = document.createElement('button'); nextBtn.textContent='Next';
                nav.appendChild(prevBtn); nav.appendChild(info); nav.appendChild(nextBtn);
                container.appendChild(canvas); container.appendChild(nav);
                function update(){
                  prevBtn.disabled = currentPage<=1; nextBtn.disabled = currentPage>=total;
                  info.textContent = 'Page '+currentPage+' / '+total;
                }
                function render(){
                  pdf.getPage(currentPage).then(function(page){
                    const scale = 1.5; // enforce single page visibility
                    const viewport = page.getViewport({ scale });
                    canvas.width = viewport.width; canvas.height = viewport.height;
                    ctx.clearRect(0,0,canvas.width,canvas.height);
                    page.render({ canvasContext: ctx, viewport });
                    update();
                  });
                }
                prevBtn.addEventListener('click',()=>{ if(currentPage>1){currentPage--; render(); container.scrollTop=0;} });
                nextBtn.addEventListener('click',()=>{ if(currentPage<total){currentPage++; render(); container.scrollTop=0;} });
                if(total===1){ nav.style.display='none'; }
                render();
              }).catch(function(){
                container.innerHTML = '<iframe class="pdf-fallback-frame" src="'+url+'#toolbar=0&navpanes=0&scrollbar=0"></iframe>';
              });
            })();
          </script>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
  <footer>© <?php echo date('Y'); ?> Sofzenix IT Solutions LLP. All rights reserved.</footer>
</body>
</html>
