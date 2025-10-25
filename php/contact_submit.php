<?php
// php/contact_submit.php
// Handles contact form submissions from the public site.

session_start();
require_once __DIR__ . '/config.php';

// Basic helper
function redirect_with_message($location, $msg) {
    $_SESSION['contact_msg'] = $msg;
    header('Location: ' . $location);
    exit;
}

// If site is in maintenance mode, do not accept submissions — show friendly message instead.
if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true) {
    redirect_with_message('../contact.php#contact', 'We are performing maintenance on the contact service. Please try again in a few minutes. For urgent matters contact us at contact@sofzenix.in or +91 63058 18324.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('../contact.php#contact', 'Invalid request method.');
}

// Read raw POST (form currently in index.html has no names; ensure inputs have names)
$name = trim($_POST['name'] ?? ($_POST['fullname'] ?? ''));
$email = trim($_POST['email'] ?? '');
$company = trim($_POST['company'] ?? '');
$city = trim($_POST['city'] ?? '');
$title = trim($_POST['title'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? ($_POST['project_goals'] ?? ''));

// Basic validation
if ($name === '' || $email === '' || $message === '') {
    redirect_with_message('../contact.php#contact', 'Please fill required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_message('../contact.php#contact', 'Invalid email address.');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $ins = $pdo->prepare(
        'INSERT INTO contact_messages (name, email, phone, company, city, subject, message, ip, user_agent) VALUES (:name, :email, :phone, :company, :city, :subject, :message, :ip, :ua)'
    );
    $ins->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':phone'   => $phone,
        ':company' => $company,
        ':city'    => $city,
        ':subject' => $title,
        ':message' => $message,
        ':ip'      => $ip,
        ':ua'      => $userAgent
    ]);

    // optional email notification to admin
    if (defined('ADMIN_EMAIL') && filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
        $to = ADMIN_EMAIL;
        $sub = 'New contact request from ' . $name;
        $body = "Name: {$name}\nEmail: {$email}\nCompany: {$company}\nCity: {$city}\nPhone: {$phone}\nTitle: {$title}\n\nMessage:\n{$message}\n\nIP: {$ip}\n";
        // Use mail() — may not work locally without SMTP; production use PHPMailer
        @mail($to, $sub, $body, "From: {$email}\r\nReply-To: {$email}");
    }

    redirect_with_message('../contact.php#contact', 'Thanks — your message was received.');
} catch (Exception $e) {
    redirect_with_message('../contact.php#contact', 'Server error. Please try later.');
}
