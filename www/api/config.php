<?php
/* 공통 설정 로더 — 실제 자격증명은 config.local.php(Git 제외)에만 둔다. */
/* 운영 서버: www/api/config.local.php  ·  로컬 개발: 프로젝트/dev/config.local.php (www 밖이라 업로드되지 않는다) */
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
    define('CONFIG_LOCAL_FILE', 'api/config.local.php');
} elseif (file_exists(dirname(__DIR__, 2) . '/dev/config.local.php')) {
    require_once dirname(__DIR__, 2) . '/dev/config.local.php';
    define('CONFIG_LOCAL_FILE', 'dev/config.local.php');
} else {
    define('CONFIG_LOCAL_FILE', '');
}

if (!defined('DB_DRIVER'))      define('DB_DRIVER', 'sqlite');            // mysql | sqlite
if (!defined('DB_SQLITE_PATH')) define('DB_SQLITE_PATH', dirname(__DIR__, 2) . '/dev/data/glofind.sqlite');
if (!defined('DB_HOST'))        define('DB_HOST', 'localhost');
if (!defined('DB_NAME'))        define('DB_NAME', '');
if (!defined('DB_USER'))        define('DB_USER', '');
if (!defined('DB_PASS'))        define('DB_PASS', '');
if (!defined('DB_CHARSET'))     define('DB_CHARSET', 'utf8mb4');
if (!defined('ADMIN_ID'))       define('ADMIN_ID', 'admin');
if (!defined('ADMIN_EMAIL'))    define('ADMIN_EMAIL', 'hello@glofind.co');
if (!defined('SITE_URL'))       define('SITE_URL', 'https://glofind.co');
if (!defined('APP_SALT'))       define('APP_SALT', 'change-me-in-config-local');
if (!defined('SITE_ROOT'))      define('SITE_ROOT', dirname(__DIR__));

date_default_timezone_set('Asia/Seoul');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
}
