<?php
/**
 * ============================================================
 *  تنظیمات اصلی بروشور اجرای دیجیتال — سازگار با Windows IIS
 *  پشتیبانی خودکار از ریشه دامنه و زیرپوشه‌ها:
 *  مثلاً https://negahm.ir/vahidtaj و https://negahm.ir/vahid-taj
 * ============================================================
 */

define('SITE_ROOT', dirname(__DIR__));
define('DATA_DIR', SITE_ROOT . '/data');
define('UPLOADS_DIR', SITE_ROOT . '/uploads');

// در صورت تمایل به تنظیم دستی زیرپوشه، مقدار زیر را پر کنید (مثلاً '/vahid-taj')؛
// در حالت خالی '' به‌صورت کاملاً خودکار از آدرس فعلی تشخیص داده می‌شود.
$FORCE_BASE_URL = '';

if ($FORCE_BASE_URL !== '') {
    define('BASE_URL', '/' . trim($FORCE_BASE_URL, '/'));
} else {
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']) : '';
    $detectedDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($detectedDir === '.' || $detectedDir === '/') {
        $detectedDir = '';
    }
    if (substr($detectedDir, -6) === '/admin') {
        $detectedDir = substr($detectedDir, 0, -6);
    }
    define('BASE_URL', $detectedDir);
}

// تشخیص پروتکل و دامنه کامل پشت IIS / Reverse Proxy / SSL
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https');

$scheme = $isHttps ? 'https' : 'http';
$host   = isset($_SERVER['HTTP_X_FORWARDED_HOST']) && $_SERVER['HTTP_X_FORWARDED_HOST'] !== ''
    ? trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_HOST'])[0])
    : (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '' ? (string)$_SERVER['HTTP_HOST'] : 'negahm.ir');

define('SITE_ORIGIN', $scheme . '://' . $host);
define('SITE_FULL_BASE', rtrim(SITE_ORIGIN . BASE_URL, '/'));

define('MAX_UPLOAD_SIZE', 200 * 1024 * 1024); // 200MB
date_default_timezone_set('Asia/Tehran');
