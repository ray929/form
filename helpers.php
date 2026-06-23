<?php
/**
 * Forms — Helper functions
 */
function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function load_lang(string $lang): array
{
    $file = __DIR__ . "/lang/$lang.php";
    if (!file_exists($file)) {
        $file = __DIR__ . '/lang/en.php';
    }
    return require $file;
}

/**
 * Apply proxy to a cURL handle (if configured)
 */
function curl_apply_proxy($ch): void
{
    $proxy = App::config()['curl_proxy'] ?? '';
    if ($proxy) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    }
}

/**
 * Close the HTTP connection to the client and continue processing.
 * Works with both PHP-FPM (fastcgi_finish_request) and built-in server (Connection: close trick).
 */
function close_connection_and_continue(): void
{
    if (function_exists('fastcgi_finish_request')) {
        session_write_close();
        fastcgi_finish_request();
        return;
    }
    // Fallback for PHP built-in server / Apache mod_php
    ignore_user_abort(true);
    session_write_close();
    $size = ob_get_length();
    header("Content-Length: $size");
    header('Connection: close');
    ob_end_flush();
    if (ob_get_level() > 0) ob_flush();
    flush();
}

function turnstile_verify(string $token, string $secret): bool
{
    if (empty($token)) {
        error_log('[Turnstile] Empty token');
        return false;
    }
    // Test keys always pass — skip API call
    if ($secret === '1x0000000000000000000000000000000AA') {
        return true;
    }
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false, // dev: Windows 缺少 CA bundle
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    curl_apply_proxy($ch);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($result === false || $curlError) {
        error_log('[Turnstile] cURL error: ' . ($curlError ?: 'empty result') . ' HTTP:' . $httpCode);
        return false;
    }
    if ($httpCode !== 200) {
        error_log('[Turnstile] Non-200 HTTP: ' . $httpCode . ' body:' . substr($result, 0, 200));
        return false;
    }
    $data = json_decode($result, true);
    if (!is_array($data)) {
        error_log('[Turnstile] Invalid JSON response: ' . substr($result, 0, 200));
        return false;
    }
    if (!empty($data['success'])) return true;

    $errors = implode(', ', $data['error-codes'] ?? ['unknown']);
    error_log('[Turnstile] Verification failed: ' . $errors);
    return false;
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
        || isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['error' => 'Method not allowed'], 405);
    }
}

function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function truncate(string $text, int $maxLen = 50): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $maxLen) return h($text);
    return h(mb_substr($text, 0, $maxLen)) . '…';
}
