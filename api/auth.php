<?php
/**
 * Admin auth API — login
 */
require_post();

$input = get_json_input();
$password = $input['password'] ?? $_POST['password'] ?? '';
$turnstileToken = $input['turnstile_token'] ?? $_POST['turnstile_token'] ?? '';

if (!turnstile_verify($turnstileToken, App::config()['turnstile_secret_key'])) {
    json_response(['success' => false, 'error' => App::lang()['turnstile_failed']], 400);
}

$htpasswdPath = App::config()['htpasswd_path'];
if (!file_exists($htpasswdPath)) {
    json_response(['success' => false, 'error' => 'System not configured'], 500);
}

$lines = file($htpasswdPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$authenticated = false;
foreach ($lines as $line) {
    if ($line[0] === '#') continue;
    $parts = explode(':', $line, 2);
    if (count($parts) === 2 && password_verify($password, $parts[1])) {
        $authenticated = true;
        break;
    }
}

if ($authenticated) {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    json_response(['success' => true]);
} else {
    json_response(['success' => false, 'error' => App::lang()['login_failed']], 401);
}
