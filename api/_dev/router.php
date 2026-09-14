<?php
/* 로컬 개발 서버(php -S)용 라우터 — 아파치의 DirectorySlash 를 흉내낸다.
   /admin → /admin/ , /about → /about/ 처럼 폴더 요청에 슬래시를 붙여 준다. 그 외는 내장 서버 기본 처리. */
$root = dirname(__DIR__, 2);
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if ($path !== '/' && !str_ends_with($path, '/') && is_dir($root . $path)) {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $path . '/' . ($qs !== '' ? '?' . $qs : ''), true, 301);
    exit;
}
return false;
