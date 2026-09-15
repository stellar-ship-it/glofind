<?php
/* 방문 기록 비컨 — 공개 페이지에서 sendBeacon 으로 호출. 응답 본문 없음. */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
http_response_code(204);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
$page = is_array($data) ? ($data['p'] ?? '') : ($_POST['p'] ?? '');
$page = preg_replace('/[^\x20-\x7E\p{L}\p{N}\/\-_.?=&#%]/u', '', (string)$page);
if ($page === '' || str_starts_with($page, '/admin') || str_starts_with($page, '/api')) exit;

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if ($ua === '' || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|lighthouse/i', $ua)) exit;

try {
    get_db()->recordVisit($page, $_SERVER['HTTP_REFERER'] ?? '', $ua, $_SERVER['REMOTE_ADDR'] ?? '');
} catch (Throwable $e) {
    error_log('[visit] ' . $e->getMessage());
}
