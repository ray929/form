<?php
/**
 * Send email API — for manually retrying failed emails
 */
require_post();
App::requireAdmin();

$submissionId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($submissionId <= 0) {
    json_response(['success' => false, 'error' => 'Invalid submission ID'], 400);
}

$stmt = App::db()->prepare("SELECT s.*, f.name as form_name, f.recipient_email as form_recipient FROM submissions s LEFT JOIN forms f ON s.form_id = f.id WHERE s.id = ?");
$stmt->execute([$submissionId]);
$sub = $stmt->fetch();

if (!$sub) {
    json_response(['success' => false, 'error' => 'Submission not found'], 404);
}

$recipient = $sub['form_recipient'] ?: App::config()['default_recipient'];
$isZh = $sub['content'] && preg_match('/[\x{4e00}-\x{9fff}]/u', $sub['content']);
$emailLang = $isZh ? 'zh' : 'en';

if ($emailLang === 'zh') {
    $subject = "表单提交 - {$sub['form_name']}";
    $htmlBody = "<h2>表单提交</h2>"
        . "<p><strong>表单：</strong>" . h($sub['form_name']) . "</p>"
        . "<p><strong>姓名：</strong>" . h($sub['name']) . "</p>"
        . "<p><strong>邮箱：</strong>" . h($sub['email']) . "</p>"
        . "<p><strong>电话：</strong>" . h($sub['phone']) . "</p>"
        . "<p><strong>内容：</strong>" . nl2br(h($sub['content'])) . "</p>";
} else {
    $subject = "Form Submission - {$sub['form_name']}";
    $htmlBody = "<h2>Form Submission</h2>"
        . "<p><strong>Form:</strong> " . h($sub['form_name']) . "</p>"
        . "<p><strong>Name:</strong> " . h($sub['name']) . "</p>"
        . "<p><strong>Email:</strong> " . h($sub['email']) . "</p>"
        . "<p><strong>Phone:</strong> " . h($sub['phone']) . "</p>"
        . "<p><strong>Message:</strong> " . nl2br(h($sub['content'])) . "</p>";
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

if ($httpCode >= 200 && $httpCode < 300 && !$error) {
    App::db()->prepare("UPDATE submissions SET email_status = 'sent', email_error = NULL WHERE id = ?")->execute([$submissionId]);
    json_response(['success' => true]);
} else {
    $errMsg = $error ?: "HTTP $httpCode";
    App::db()->prepare("UPDATE submissions SET email_status = 'failed', email_error = ? WHERE id = ?")
       ->execute([$errMsg, $submissionId]);
    json_response(['success' => false, 'error' => $errMsg], 500);
}
