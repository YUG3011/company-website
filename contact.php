<?php
// contact.php - wrapper that serves existing index.html and injects a server-side contact feedback message when present.
// This file was created to avoid using index.php so hosting that serves index.php by default won't override index.html.
// It loads index.html unchanged and inserts a small notification into the contact section when a session message exists.

session_start();
$contact_msg = $_SESSION['contact_msg'] ?? null;
if (isset($_SESSION['contact_msg'])) unset($_SESSION['contact_msg']);

$htmlPath = __DIR__ . '/index.html';
if (!file_exists($htmlPath)) {
    echo "Original index.html not found.";
    exit;
}

$html = file_get_contents($htmlPath);
if ($contact_msg) {
    // fixed top feedback box with CSS animation (no JS required)
    $css = '<style>
/* Animated fixed notification: appears from top, stays, then fades up */
@keyframes sfzNotify {
  0% { opacity: 0; transform: translateY(-18px); }
  10% { opacity: 1; transform: translateY(0); }
  80% { opacity: 1; transform: translateY(0); }
  100% { opacity: 0; transform: translateY(-18px); }
}
.contact-feedback-fixed {
  position: fixed !important;
  top: 18px !important;
  left: 50% !important;
  transform: translateX(-50%) !important;
  z-index: 99999 !important;
  min-width: 280px;
  max-width: 92%;
  padding: 12px 18px !important;
  border-radius: 10px !important;
  background: #e6fffa !important;
  color: #064e3b !important;
  border: 1px solid #a7f3d0 !important;
  font-weight: 600 !important;
  box-shadow: 0 8px 28px rgba(2,6,23,0.45) !important;
  animation: sfzNotify 4s ease forwards !important;
  pointer-events: none !important; /* non-interactive */
}
</style>';
    $feedback = $css . '<div class="contact-feedback-fixed">' . htmlspecialchars($contact_msg) . '</div>';
    // insert feedback immediately after the opening of the contact section, fallback to body if not found
    $newHtml = preg_replace('/(<section[^>]*id=["\']contact["\'][^>]*>)/i', '$1' . $feedback, $html, 1);
    if ($newHtml === null) {
        $html = preg_replace('/(<body[^>]*>)/i', '$1' . $feedback, $html, 1);
    } elseif ($newHtml === $html) {
        $html = preg_replace('/(<body[^>]*>)/i', '$1' . $feedback, $html, 1);
    } else {
        $html = $newHtml;
    }
}
// If MAINTENANCE_MODE is on, inject a persistent maintenance banner into the contact section.
if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true) {
    $css = '<style>
.contact-maintenance { padding:18px 20px; border-radius:12px; background:linear-gradient(90deg,#0f172a,#0b1220); color:#cbd5e1; border:1px solid rgba(99,102,241,0.08); box-shadow:0 8px 30px rgba(2,6,23,0.45); font-weight:600; }
.contact-maintenance a{color:#38bdf8;text-decoration:none;font-weight:700}
</style>';
    $feedback = $css . '<div class="contact-maintenance">Verification and contact services are temporarily under maintenance. Please try again in a few minutes. For urgent matters contact <a href="mailto:contact@sofzenix.in">contact@sofzenix.in</a> or call <strong>+91 63058 18324</strong>.</div>';
    $newHtml = preg_replace('/(<section[^>]*id=["\']contact["\'][^>]*>)/i', '$1' . $feedback, $html, 1);
    if ($newHtml !== null && $newHtml !== $html) {
        $html = $newHtml;
    } else {
        $html = preg_replace('/(<body[^>]*>)/i', '$1' . $feedback, $html, 1);
    }
} elseif ($contact_msg) {
    // fixed top feedback box with CSS animation (no JS required)
    $css = '<style>
/* Animated fixed notification: appears from top, stays, then fades up */
@keyframes sfzNotify {
  0% { opacity: 0; transform: translateY(-18px); }
  10% { opacity: 1; transform: translateY(0); }
  80% { opacity: 1; transform: translateY(0); }
  100% { opacity: 0; transform: translateY(-18px); }
}
.contact-feedback-fixed {
  position: fixed !important;
  top: 18px !important;
  left: 50% !important;
  transform: translateX(-50%) !important;
  z-index: 99999 !important;
  min-width: 280px;
  max-width: 92%;
  padding: 12px 18px !important;
  border-radius: 10px !important;
  background: #e6fffa !important;
  color: #064e3b !important;
  border: 1px solid #a7f3d0 !important;
  font-weight: 600 !important;
  box-shadow: 0 8px 28px rgba(2,6,23,0.45) !important;
  animation: sfzNotify 4s ease forwards !important;
  pointer-events: none !important; /* non-interactive */
}
</style>';
    $feedback = $css . '<div class="contact-feedback-fixed">' . htmlspecialchars($contact_msg) . '</div>';
    // insert feedback immediately after the opening of the contact section, fallback to body if not found
    $newHtml = preg_replace('/(<section[^>]*id=["\']contact["\'][^>]*>)/i', '$1' . $feedback, $html, 1);
    if ($newHtml === null) {
        $html = preg_replace('/(<body[^>]*>)/i', '$1' . $feedback, $html, 1);
    } elseif ($newHtml === $html) {
        $html = preg_replace('/(<body[^>]*>)/i', '$1' . $feedback, $html, 1);
    } else {
        $html = $newHtml;
    }
}

echo $html;
