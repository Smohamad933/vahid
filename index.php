<?php
/**
 * صفحه اصلی بروشور اجرای دیجیتال
 * آماده اجرا در ریشه دامنه یا زیرپوشه‌های IIS (مانند negahm.ir/vahidtaj و negahm.ir/vahid-taj)
 */
require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/html; charset=utf-8');
echo render_brochure_page($siteData);
