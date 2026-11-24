<?php
/**
 * احراز هویت امن پنل مدیریت مستقل (/admin)
 */

if (!defined('SITE_ROOT')) {
    exit('Direct access denied.');
}

function init_brochure_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('VAHID_BROCHURE_SESS');
        $cookiePath = BASE_URL !== '' ? BASE_URL . '/' : '/';
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params(array(
                'lifetime' => 0,
                'path'     => $cookiePath,
                'httponly' => true,
                'samesite' => 'Lax',
            ));
        } else {
            session_set_cookie_params(0, $cookiePath, '', false, true);
        }
        @session_start();
    }
}

function is_admin_logged_in()
{
    return !empty($_SESSION['vahid_admin_logged_in']) && $_SESSION['vahid_admin_logged_in'] === true;
}

function require_admin_login()
{
    if (!is_admin_logged_in()) {
        redirect('admin/login.php');
    }
}

function csrf_token()
{
    if (empty($_SESSION['vahid_csrf'])) {
        $_SESSION['vahid_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['vahid_csrf'];
}

function csrf_input()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token()
{
    $sent = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('نشست امنیتی منقضی شده است؛ لطفاً صفحه را مجدداً بارگذاری کنید.');
    }
}

function attempt_admin_login($username, $password)
{
    $lockUntil = isset($_SESSION['vahid_lock_until']) ? (int)$_SESSION['vahid_lock_until'] : 0;
    if (time() < $lockUntil) {
        return array('ok' => false, 'error' => 'تعداد تلاش‌های ناموفق بیش از حد مجاز است. چند دقیقه دیگر تلاش کنید.');
    }

    $admin = get_admin_credentials();
    $userMatch = hash_equals((string)$admin['username'], trim((string)$username));
    $passMatch = password_verify((string)$password, (string)$admin['password_hash']);

    if (!$userMatch || !$passMatch) {
        $fails = (isset($_SESSION['vahid_login_fails']) ? (int)$_SESSION['vahid_login_fails'] : 0) + 1;
        if ($fails >= 5) {
            $_SESSION['vahid_lock_until'] = time() + 300;
            $fails = 0;
        }
        $_SESSION['vahid_login_fails'] = $fails;
        return array('ok' => false, 'error' => 'نام کاربری یا رمز عبور نادرست است.');
    }

    @session_regenerate_id(true);
    unset($_SESSION['vahid_login_fails'], $_SESSION['vahid_lock_until']);
    $_SESSION['vahid_admin_logged_in'] = true;
    $_SESSION['vahid_admin_user'] = $admin['username'];
    return array('ok' => true);
}

function logout_admin()
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    @session_destroy();
}
