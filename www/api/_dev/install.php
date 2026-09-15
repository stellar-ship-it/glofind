<?php
/* 최초 설치 — 테이블 생성 + 기존 아티클 10편 시드. config.local.php 의 INSTALL_KEY 와 ?key= 가 일치해야 실행된다.
   CLI: php api/_dev/install.php   (키 검사 없음)   설치 후 이 폴더는 삭제한다. */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
$cli = PHP_SAPI === 'cli';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (!defined('INSTALL_KEY') || !hash_equals(INSTALL_KEY, $_GET['key'] ?? '')) { http_response_code(403); exit("forbidden\n"); }
}
echo "config: " . (CONFIG_LOCAL_FILE ?: '(없음 — api/config.local.php 를 만드세요)') . "\n";
echo "driver: " . DB_DRIVER . (DB_DRIVER === 'mysql' ? " · host=" . DB_HOST . " · db=" . DB_NAME . " · user=" . DB_USER : '') . "\n";
echo "php: " . PHP_VERSION . " · pdo_mysql " . (extension_loaded('pdo_mysql') ? 'ok' : '없음') . "\n";
try {
    $db = get_db();
} catch (Throwable $e) {
    $prev = $e->getPrevious();
    echo "DB 접속 실패: " . ($prev ? $prev->getMessage() : $e->getMessage()) . "\n";
    echo "→ 카페24 관리 > MySQL 에서 DB명·사용자·비밀번호를 확인해 config.local.php 에 넣으세요. 호스트는 보통 localhost 입니다.\n";
    exit;
}
$db->ensureSchema();
echo "schema ok (" . $db->driver() . ")\n";
if ($db->count('insights') === 0 && file_exists(__DIR__ . '/insights-seed.json')) {
    $rows = json_decode(file_get_contents(__DIR__ . '/insights-seed.json'), true);
    foreach ($rows as $r) {
        $db->insert('insights', [
            'slug' => $r['slug'], 'category' => $r['category'], 'title' => $r['title'], 'meta_title' => $r['meta_title'] ?? '',
            'lead_text' => $r['lead'], 'excerpt' => $r['excerpt'], 'description' => $r['description'],
            'keywords' => $r['keywords'], 'author' => $r['author'], 'read_minutes' => $r['read_minutes'],
            'hero_image' => $r['hero_image'], 'hero_alt' => $r['hero_alt'],
            'summary_json' => json_encode($r['summary'], JSON_UNESCAPED_UNICODE),
            'body_html' => $r['body_html'],
            'faq_json' => json_encode($r['faq'], JSON_UNESCAPED_UNICODE),
            'links_json' => json_encode($r['links'], JSON_UNESCAPED_UNICODE),
            'cta_text' => $r['cta_text'], 'cta_label' => $r['cta_label'],
            'is_published' => $r['is_published'], 'is_featured' => $r['is_featured'],
            'published_at' => $r['published_at'], 'views' => 0,
            'created_at' => $r['published_at'], 'updated_at' => $r['published_at'],
        ]);
    }
    echo "seeded " . count($rows) . " insights\n";
} else {
    echo "insights: " . $db->count('insights') . " rows (seed skipped)\n";
}
if ($db->setting('categories') === null) {
    $db->setSetting('categories', "해외 진출 전략\n브랜드 미디어\n인플루언서\n퍼포먼스 광고\nSEO · AI 검색");
}
echo "done\n";
