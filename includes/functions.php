<?php
/**
 * توابع کمکی عمومی، امنیتی، آپلود فایل و تولید سئو
 */

if (!defined('SITE_ROOT')) {
    exit('Direct access denied.');
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    $base = rtrim(BASE_URL, '/');
    $clean = ltrim((string)$path, '/');
    if ($clean === '') {
        return ($base === '' ? '/' : $base . '/');
    }
    return $base . '/' . $clean;
}

function asset_url($path)
{
    $p = trim((string)$path);
    if ($p === '') {
        return '';
    }
    if (preg_match('#^(https?:|data:|blob:)#i', $p)) {
        return $p;
    }
    return url(ltrim($p, '/'));
}

function full_url($path = '')
{
    $p = trim((string)$path);
    if (preg_match('#^https?://#i', $p)) {
        return $p;
    }
    $clean = ltrim($p, '/');
    return $clean === '' ? (SITE_FULL_BASE . '/') : (SITE_FULL_BASE . '/' . $clean);
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function fa_num($value, $enabled = true)
{
    if (!$enabled) {
        return (string)$value;
    }
    $en = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $fa = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    return str_replace($en, $fa, (string)$value);
}

function new_id($prefix = 'id_')
{
    return $prefix . substr(bin2hex(random_bytes(6)), 0, 10);
}

function detect_font_format($path)
{
    $clean = strtolower( strtok((string)$path, '?') );
    if (substr($clean, -6) === '.woff2') return 'woff2';
    if (substr($clean, -5) === '.woff')  return 'woff';
    if (substr($clean, -4) === '.otf')   return 'opentype';
    if (substr($clean, -4) === '.ttf')   return 'truetype';
    return 'woff2';
}

/* ================== پیام‌های Flash پنل ================== */

function set_flash($message, $type = 'success')
{
    $_SESSION['brochure_flash'] = array('message' => (string)$message, 'type' => (string)$type);
}

function get_flash()
{
    if (!empty($_SESSION['brochure_flash'])) {
        $f = $_SESSION['brochure_flash'];
        unset($_SESSION['brochure_flash']);
        return $f;
    }
    return null;
}

function render_flash()
{
    $f = get_flash();
    if (!$f) return '';
    $cls = ($f['type'] === 'error') ? 'adm-alert adm-alert-error' : 'adm-alert adm-alert-success';
    return '<div class="' . $cls . '">' . e($f['message']) . '</div>';
}

/* ================== آپلود امن تصویر / فونت / صوت ================== */

function upload_brochure_file($fileInfo, $category)
{
    if (empty($fileInfo) || !isset($fileInfo['error']) || $fileInfo['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => false, 'empty' => true, 'error' => '');
    }
    if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'empty' => false, 'error' => 'خطا هنگام بارگذاری فایل (کد ' . (int)$fileInfo['error'] . ').');
    }
    if ($fileInfo['size'] > MAX_UPLOAD_SIZE) {
        return array('ok' => false, 'empty' => false, 'error' => 'حجم فایل بیش از سقف مجاز است.');
    }

    $allowedMap = array(
        'images' => array('png', 'jpg', 'jpeg', 'webp', 'svg', 'ico', 'gif'),
        'fonts'  => array('woff2', 'woff', 'ttf', 'otf'),
        'audio'  => array('mp3', 'm4a', 'wav', 'ogg', 'aac', 'flac')
    );
    if (!isset($allowedMap[$category])) {
        return array('ok' => false, 'empty' => false, 'error' => 'دسته‌بندی آپلود نامعتبر است.');
    }

    $origName = isset($fileInfo['name']) ? (string)$fileInfo['name'] : 'file';
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedMap[$category], true)) {
        return array(
            'ok'    => false,
            'empty' => false,
            'error' => 'پسوند فایل مجاز نیست. فرمت‌های مجاز: ' . implode(', ', $allowedMap[$category])
        );
    }

    $targetDir = UPLOADS_DIR . '/' . $category;
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true)) {
        return array('ok' => false, 'empty' => false, 'error' => 'پوشه uploads/' . $category . ' قابل نوشتن نیست (دسترسی IIS_IUSRS را بررسی کنید).');
    }

    $baseSlug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', pathinfo($origName, PATHINFO_FILENAME));
    $baseSlug = trim($baseSlug, '_');
    if ($baseSlug === '') {
        $baseSlug = $category;
    }
    $safeName = substr($baseSlug, 0, 40) . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    $destPath = $targetDir . '/' . $safeName;

    if (!@move_uploaded_file($fileInfo['tmp_name'], $destPath)) {
        return array('ok' => false, 'empty' => false, 'error' => 'انتقال فایل به پوشه uploads انجام نشد.');
    }

    return array(
        'ok'           => true,
        'empty'        => false,
        'relative_url' => 'uploads/' . $category . '/' . $safeName,
        'original'     => $origName
    );
}

/* ================== بازتولید خودکار فایل‌های سئو ================== */

function regenerate_seo_files(array $data)
{
    $canonical = !empty($data['seo']['canonicalUrl'])
        ? rtrim((string)$data['seo']['canonicalUrl'], '/') . '/'
        : (SITE_FULL_BASE . '/');

    $lastmod = date('Y-m-d');
    $urls = array($canonical);
    // اگر هنوز دامنه پیش‌فرض ثبت نشده، هر دو مسیر درخواستی negahm.ir را در نقشه سایت لحاظ کن
    if (empty($data['seo']['canonicalUrl'])) {
        $urls = array_values(array_unique(array(
            $canonical,
            'https://negahm.ir/vahid-taj/',
            'https://negahm.ir/vahidtaj/'
        )));
    }

    $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($urls as $loc) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
        $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= "</urlset>\n";
    @file_put_contents(SITE_ROOT . '/sitemap.xml', $xml);

    $robotsRule = (isset($data['seo']['robots']) && strpos((string)$data['seo']['robots'], 'noindex') !== false)
        ? "Disallow: /\n"
        : "Allow: /\nDisallow: " . rtrim(BASE_URL, '/') . "/admin/\nDisallow: " . rtrim(BASE_URL, '/') . "/data/\nDisallow: " . rtrim(BASE_URL, '/') . "/includes/\n";

    $robots  = "User-agent: *\n" . $robotsRule;
    $robots .= "Sitemap: " . rtrim($canonical, '/') . "/sitemap.xml\n";
    @file_put_contents(SITE_ROOT . '/robots.txt', $robots);
}
