<?php
/* 미리보기 — 파일을 쓰지 않고 현재 DB 내용으로 아티클을 렌더링한다(임시저장 글도 볼 수 있다). */
require_once __DIR__ . '/config.php';
require_admin_auth();
require_once __DIR__ . '/lib/publisher.php';
$db = get_db();
$row = $db->one('SELECT * FROM insights WHERE id = :id', [':id' => (int)($_GET['id'] ?? 0)]);
if (!$row) { http_response_code(404); exit('아티클을 찾을 수 없습니다.'); }
header('X-Robots-Tag: noindex, nofollow');
echo pub_render_article($row, pub_published($db));
