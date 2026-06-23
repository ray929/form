<?php
/**
 * Submit form API — receives form submissions from embed form
 * POST /api/submit/{form_id}
 *
 * Flow: validate input → verify Turnstile → insert submission
 *       → respond to user → async send email
 */
require_post();

$formId = (int)($_GET['form_id'] ?? 0);
if (!$formId) {
    json_response(['success' => false, 'error' => 'Form not found'], 404);
}

// Get form
$stmt = App::db()->prepare("SELECT * FROM forms WHERE id = ? AND status = 1");
$stmt->execute([$formId]);
$form = $stmt->fetch();

if (!$form) {
    json_response(['success' => false, 'error' => 'Form not found'], 404);
}

// Validate Turnstile
$turnstileToken = $_POST['turnstile_token'] ?? '';
if (!turnstile_verify($turnstileToken, App::config()['turnstile_secret_key'])) {
    json_response(['success' => false, 'error' => App::lang()['turnstile_failed']], 400);
}

// Validate input
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($name === '' || $content === '') {
    json_response(['success' => false, 'error' => App::lang()['form_validation_error']], 400);
}

// At least one contact method required
if ($email === '' && $phone === '') {
    json_response(['success' => false, 'error' => App::lang()['form_contact_type_required']], 400);
}

// Insert submission
$stmt = App::db()->prepare("INSERT INTO submissions (form_id, name, email, phone, content) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$form['id'], $name, $email, $phone, $content]);
$submissionId = App::db()->lastInsertId();

// Determine email language from URL parameter
$emailLang = $_GET['lang'] ?? 'en';
if (!in_array($emailLang, ['en', 'zh'])) {
    $emailLang = 'en';
}

// Determine recipient
$recipient = $form['recipient_email'] ?: App::config()['default_recipient'];

// === Respond to user immediately, then continue async ===
ob_start();
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'id' => $submissionId], JSON_UNESCAPED_UNICODE);
close_connection_and_continue();

// === Async: send email ===
$contactInfo = [];
if ($email) $contactInfo[] = 'Email: ' . $email;
if ($phone) $contactInfo[] = 'Phone: ' . $phone;
$contactStr = implode(' | ', $contactInfo);

if ($emailLang === 'zh') {
    $subject = "新的表单提交 - {$form['name']}";
    $htmlBody = "<h2>新的表单提交</h2>"
        . "<p><strong>表单：</strong>" . h($form['name']) . "</p>"
        . "<p><strong>姓名：</strong>" . h($name) . "</p>"
        . "<p><strong>联系方式：</strong>" . h($contactStr) . "</p>"
        . "<p><strong>内容：</strong></p>"
        . "<p>" . nl2br(h($content)) . "</p>";
} else {
    $subject = "New Form Submission - {$form['name']}";
    $htmlBody = "<h2>New Form Submission</h2>"
        . "<p><strong>Form:</strong> " . h($form['name']) . "</p>"
        . "<p><strong>Name:</strong> " . h($name) . "</p>"
        . "<p><strong>Contact:</strong> " . h($contactStr) . "</p>"
        . "<p><strong>Message:</strong></p>"
        . "<p>" . nl2br(h($content)) . "</p>";
}

$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . App::config()['resend_api_key'],
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'from' => App::config()['from_email'],
        'to' => [$recipient],
        'subject' => $subject,
        'html' => $htmlBody,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
curl_apply_proxy($ch);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Update email status
if ($httpCode >= 200 && $httpCode < 300 && !$error) {
    App::db()->prepare("UPDATE submissions SET email_status = 'sent' WHERE id = ?")->execute([$submissionId]);
} else {
    $errMsg = $error ?: "HTTP $httpCode: " . substr($result, 0, 200);
    App::db()->prepare("UPDATE submissions SET email_status = 'failed', email_error = ? WHERE id = ?")
       ->execute([$errMsg, $submissionId]);
}
