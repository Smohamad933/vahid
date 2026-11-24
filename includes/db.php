<?php
/**
 * لایه ذخیره‌سازی مبتنی بر فایل‌های JSON در پوشه محافظت‌شده data
 */

if (!defined('SITE_ROOT')) {
    exit('Direct access denied.');
}

function json_read_file($filePath, $default = array())
{
    if (!is_file($filePath)) {
        return $default;
    }
    $raw = @file_get_contents($filePath);
    if ($raw === false || trim($raw) === '') {
        return $default;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

function json_write_file($filePath, array $payload)
{
    $dir = dirname($filePath);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('امکان ساخت پوشه data وجود ندارد.');
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $fh = @fopen($filePath, 'c+');
    if ($fh === false) {
        throw new RuntimeException('امکان نوشتن در پوشه data وجود ندارد؛ دسترسی Modify کاربر IIS_IUSRS را بررسی کنید.');
    }
    if (flock($fh, LOCK_EX)) {
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, $json);
        fflush($fh);
        flock($fh, LOCK_UN);
    }
    fclose($fh);
}

function get_site_data()
{
    return json_read_file(DATA_DIR . '/site.json', array());
}

function save_site_data(array $data)
{
    if (!isset($data['meta']) || !is_array($data['meta'])) {
        $data['meta'] = array();
    }
    $data['meta']['updatedAt'] = date('c');
    json_write_file(DATA_DIR . '/site.json', $data);
    regenerate_seo_files($data);
    return $data;
}

function get_admin_credentials()
{
    $default = array(
        'username'      => 'Mohusyn',
        'password_hash' => '$2y$12$2k32cd.ryxC.OlL0yit7nuszfMQN06ptNVHyegmhVVoWKHP3okiaK'
    );
    $loaded = json_read_file(DATA_DIR . '/admin.json', $default);
    return array_merge($default, $loaded);
}

function save_admin_credentials($username, $plainPassword = null)
{
    $curr = get_admin_credentials();
    $curr['username'] = trim((string)$username);
    if ($plainPassword !== null && $plainPassword !== '') {
        $curr['password_hash'] = password_hash((string)$plainPassword, PASSWORD_BCRYPT);
    }
    $curr['updated_at'] = date('c');
    json_write_file(DATA_DIR . '/admin.json', $curr);
}
