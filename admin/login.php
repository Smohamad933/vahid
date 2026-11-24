<?php
/**
 * صفحه مستقل ورود به پنل مدیریت بروشور اجرای دیجیتال
 * تنها از طریق آدرس مستقیم دامنه قابل دسترسی است (/admin/login.php)
 */
require_once dirname(__DIR__) . '/includes/init.php';
init_brochure_session();

if (is_admin_logged_in()) {
    redirect('admin/index.php');
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $u = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $p = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $res = attempt_admin_login($u, $p);
    if (!empty($res['ok'])) {
        redirect('admin/index.php');
    }
    $loginError = isset($res['error']) ? $res['error'] : 'خطا در ورود به سیستم.';
}

$favUrl = asset_url(!empty($siteData['seo']['favicon']) ? $siteData['seo']['favicon'] : 'assets/images/favicon.png');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>ورود به پنل مدیریت | بروشور اجرای دیجیتال</title>
  <link rel="icon" type="image/png" href="<?= e($favUrl) ?>">
  <style><?= build_brochure_font_css(isset($siteData['typography']) ? $siteData['typography'] : array()) ?></style>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/admin.css')) ?>">
</head>
<body class="adm-login-body">
  <div class="adm-login-card">
    <div class="adm-login-brand">
      <img src="<?= e($favUrl) ?>" alt="بروشور اجرای دیجیتال" class="adm-login-avatar">
      <div>
        <h1>پنل مدیریت بروشور دیجیتال</h1>
        <p>احراز هویت مدیر سامانه</p>
      </div>
    </div>

    <?php if ($loginError !== ''): ?>
      <div class="adm-alert adm-alert-error"><?= e($loginError) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('admin/login.php')) ?>" autocomplete="on" class="adm-login-form">
      <?= csrf_input() ?>
      <div class="adm-field">
        <label for="adm-user">نام کاربری</label>
        <input id="adm-user" type="text" name="username" dir="ltr" required autofocus autocomplete="username">
      </div>
      <div class="adm-field">
        <label for="adm-pass">رمز عبور</label>
        <input id="adm-pass" type="password" name="password" dir="ltr" required autocomplete="current-password">
      </div>
      <button type="submit" class="adm-btn adm-btn-gold adm-btn-full">ورود به پنل مدیریت</button>
    </form>
  </div>
</body>
</html>
