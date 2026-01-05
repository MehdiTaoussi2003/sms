<?php
/**
 * Global App Config and URL helpers
 * Stock Management System (SMS)
 */

// Define include guard early so other includes (e.g., database.php) allow access
if (!defined('SMS_INCLUDED')) {
    define('SMS_INCLUDED', true);
}

// Detect scheme reliably (supports proxies)
function sms_detect_scheme(): string {
    $https = $_SERVER['HTTPS'] ?? '';
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if (!empty($proto)) {
        $p = strtolower(trim(explode(',', $proto)[0]));
        return $p === 'https' ? 'https' : 'http';
    }
    if (!empty($https) && strtolower($https) !== 'off') {
        return 'https';
    }
    return 'http';
}

// Detect host
function sms_detect_host(): string {
    return $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
}

// Compute base path from filesystem relationship between DOCUMENT_ROOT and project root (parent of config)
$__app_root = realpath(dirname(__DIR__));
$__doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';
$__base_path = '/';
if ($__doc_root && $__app_root && strpos($__app_root, $__doc_root) === 0) {
    $rel = str_replace('\\', '/', substr($__app_root, strlen($__doc_root)));
    $__base_path = '/' . ltrim($rel, '/');
    if ($__base_path !== '/') {
        $__base_path = rtrim($__base_path, '/') . '/';
    }
} else {
    // Fallback to script directory if relation cannot be determined
    $dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $dir = str_replace('\\', '/', $dir);
    $__base_path = rtrim($dir, '/') . '/';
    if ($__base_path === '//' || $__base_path === '') {
        $__base_path = '/';
    }
}

// Normalize base path
if ($__base_path !== '/' && strpos($__base_path, '//') === 0) {
    $__base_path = '/' . ltrim($__base_path, '/');
}

// Define constants
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $__base_path); // URL path (leading and trailing slash except when root is '/')
}
if (!defined('BASE_SCHEME')) {
    define('BASE_SCHEME', sms_detect_scheme());
}
if (!defined('BASE_HOST')) {
    define('BASE_HOST', sms_detect_host());
}
if (!defined('BASE_URL')) {
    $url = BASE_SCHEME . '://' . BASE_HOST;
    if (BASE_PATH !== '/') {
        $url .= rtrim(BASE_PATH, '/');
    }
    $url .= '/';
    define('BASE_URL', $url);
}

// Helper to build absolute URL from path or return as-is if already absolute
function url(string $path = ''): string {
    if ($path === '' || $path === '/') {
        return BASE_URL;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path; // already absolute URL
    }
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// Helper to redirect safely using absolute URL
function redirect_to(string $pathOrUrl, int $code = 302): void {
    $target = preg_match('#^https?://#i', $pathOrUrl) ? $pathOrUrl : url($pathOrUrl);
    header('Location: ' . $target, true, $code);
    exit();
}

// Helper to get current full URL
function current_url(): string {
    $scheme = sms_detect_scheme();
    $host = sms_detect_host();
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return $scheme . '://' . $host . $uri;
}

?>
