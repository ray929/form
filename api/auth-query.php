<?php
/**
 * Query auth API — login with form-specific password
 * POST /api/auth/query?form_id=X
 * Verifies password against form.query_password (bcrypt hash).
 * Falls back to htpasswd if form has no password set.
 */
require_post();

$formId = (int)($_GET['form_id'] ?? 0);
$password = $_POST['password'] ?? '';
$turnstileToken = $_POST['turnstile_token'] ?? '';

if (!turnstile_verify($turnstileToken, App::config()['turnstile_secret_key'])) {
    json_response(['success' => false, 'error' => App::lang()['turnstile_failed']], 400);
}

$authenticated = false;

if ($formId > 0) {
    // Try form-specific password first
    $stmt = App::db()->prepare("SELECT query_password FROM forms WHERE id = ?");
    $stmt->execute([$formId]);
    $formPass = $stmt->fetchColumn();

    if ($formPass && $formPass !== '') {
        $authenticated = password_verify($password, $formPass);
    }
}

// Fallback: htpasswd global password
if (!$authenticated) {
    $htpasswdPath = App::config()['htpasswd_path'];
    if (!file_exists($htpasswdPath)) {
        json_response(['success' => false, 'error' => 'System not configured'], 500);
    }
    $lines = file($htpasswdPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if ($line[0] === '#') continue;
        $parts = explode(':', $line, 2);
        if (count($parts) === 2 && password_verify($password, $parts[1])) {
            $authenticated = true;
            break;
        }
    }
}

if ($authenticated) {
    session_regenerate_id(true);
    if (!isset($_SESSION['query_logged_in']) || !is_array($_SESSION['query_logged_in'])) {
        $_SESSION['query_logged_in'] = [];
    }
    $_SESSION['query_logged_in'][$formId] = true;
    json_response(['success' => true]);
} else {
    json_response(['success' => false, 'error' => App::lang()['login_failed']], 401);
}
