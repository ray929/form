<?php
/**
 * Forms — Entry point / Router
 * Run: php -S localhost:8080 -t public/ public/index.php
 */

// Error reporting — disable in production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Load core
require __DIR__ . '/../App.php';
require __DIR__ . '/../helpers.php';
require __DIR__ . '/../db.php';

// Init App container
App::$config = require __DIR__ . '/../config.php';
App::$db = db_connect(App::config()['db_path']);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Parse URL ===
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// === Language detection ===
// Embed routes support ?lang=en|zh; admin/query always use English
App::$langCode = App::config()['default_lang'];
if (!empty($_GET['lang']) && in_array($_GET['lang'], ['en', 'zh'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
// Only apply session lang for embed/form routes
$isEmbedRoute = preg_match('#^/(embed|api/submit|api/embed-config)/#', $uri);
if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'zh']) && $isEmbedRoute) {
    App::$langCode = $_SESSION['lang'];
}
App::$lang = load_lang(App::langCode());

// Local aliases for included files (template convenience)
$lang = App::lang();
$langCode = App::langCode();

// === CORS for embed endpoints ===
if (preg_match('#^/(embed|api/submit)/#', $uri)) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// === Static file fallback ===
if (preg_match('#\.(css|js|png|jpg|svg|ico|woff2?)$#', $uri)) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        return false; // Let built-in server handle it
    }
}

// === ROUTING ===

// Home: blank for security
if ($uri === '/') {
    http_response_code(200);
    exit;
}

// Admin
if ($uri === '/admin') {
    App::requireAdmin();
    require __DIR__ . '/../views/admin/dashboard.php';
    exit;
}

if ($uri === '/admin/login') {
    if (App::isAdmin()) { redirect('/admin'); }
    require __DIR__ . '/../views/admin/login.php';
    exit;
}

if ($uri === '/admin/logout') {
    session_destroy();
    redirect('/admin/login');
}

if ($uri === '/admin/forms') {
    App::requireAdmin();
    require __DIR__ . '/../views/admin/forms-list.php';
    exit;
}

if ($uri === '/admin/forms/create') {
    App::requireAdmin();
    require __DIR__ . '/../views/admin/form-edit.php';
    exit;
}

if ($uri === '/admin/forms/edit') {
    App::requireAdmin();
    require __DIR__ . '/../views/admin/form-edit.php';
    exit;
}

if ($uri === '/admin/submissions') {
    App::requireAdmin();
    require __DIR__ . '/../views/admin/submissions.php';
    exit;
}

if ($uri === '/admin/embed') {
    App::requireAdmin();
    require __DIR__ . '/../views/admin/embed.php';
    exit;
}

// Query: /query/{form_id} — data page, /query/{form_id}/login — login, /query/{form_id}/logout
if (preg_match('#^/query/(\d+)$#', $uri, $m)) {
    $formId = (int)$m[1];
    // Validate form exists
    $stmt = App::db()->prepare("SELECT id, name FROM forms WHERE id = ?");
    $stmt->execute([$formId]);
    $qForm = $stmt->fetch();
    if (!$qForm) {
        http_response_code(404);
        echo 'Form not found';
        exit;
    }
    if (App::isQueryUser($formId)) {
        $_GET['form_id'] = $formId;
        $_GET['form_name'] = $qForm['name'];
        require __DIR__ . '/../views/query/data.php';
    } else {
        redirect('/query/' . $formId . '/login');
    }
    exit;
}

if (preg_match('#^/query/(\d+)/login$#', $uri, $m)) {
    $formId = (int)$m[1];
    if (App::isQueryUser($formId)) { redirect('/query/' . $formId); }
    $_GET['form_id'] = $formId;
    require __DIR__ . '/../views/query/login.php';
    exit;
}

if (preg_match('#^/query/(\d+)/logout$#', $uri, $m)) {
    $formId = (int)$m[1];
    unset($_SESSION['query_logged_in'][$formId]);
    redirect('/query/' . $formId . '/login');
}

// API
if ($uri === '/api/auth') {
    require __DIR__ . '/../api/auth.php';
    exit;
}

if ($uri === '/api/auth/query') {
    require __DIR__ . '/../api/auth-query.php';
    exit;
}

if ($uri === '/api/forms') {
    App::requireAdmin();
    require __DIR__ . '/../api/forms.php';
    exit;
}

if ($uri === '/api/submissions') {
    require __DIR__ . '/../api/submissions.php';
    exit;
}

if ($uri === '/api/send') {
    require __DIR__ . '/../api/send.php';
    exit;
}

// Embed form (specific id)
if (preg_match('#^/embed/(\d+)$#', $uri, $m)) {
    $_GET['form_id'] = (int)$m[1];
    require __DIR__ . '/../views/embed/form.php';
    exit;
}

// Embed API endpoints
if (preg_match('#^/api/submit/(\d+)$#', $uri, $m)) {
    $_GET['form_id'] = (int)$m[1];
    require __DIR__ . '/../api/submit-form.php';
    exit;
}

if (preg_match('#^/api/embed-config/(\d+)$#', $uri, $m)) {
    $_GET['form_id'] = (int)$m[1];
    require __DIR__ . '/../api/embed-config.php';
    exit;
}

// 404
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>404</title><style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#1d1d1f;background:#f5f5f7;}div{text-align:center}h1{font-size:4rem;font-weight:300;margin:0;color:#d1d1d6}p{margin-top:8px;color:#8e8e93}a{color:#2563eb}</style></head><body><div><h1>404</h1><p>Page not found</p><p><a href="/admin">Back to Admin</a></p></div></body></html>';
