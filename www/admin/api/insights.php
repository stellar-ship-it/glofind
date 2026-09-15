<?php
require_once __DIR__ . '/../config.php';
require_admin_auth();
verify_csrf();
require_once __DIR__ . '/../lib/publisher.php';

$db = get_db();
$in = json_input();
$action = $in['action'] ?? '';

function ins_slug(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

try {
    if ($action === 'save') {
        $d = $in['data'] ?? [];
        $id = (int)($d['id'] ?? 0);
        $title = trim((string)($d['title'] ?? ''));
        if ($title === '') json_out(['ok' => false, 'error' => '제목을 입력하세요.'], 422);
        $slug = ins_slug((string)($d['slug'] ?? ''));
        if ($slug === '' || $slug === 'index') $slug = 'article-' . date('Ymd-Hi');
        if (strlen($slug) > 120) json_out(['ok' => false, 'error' => 'URL 슬러그가 너무 깁니다.'], 422);
        $dupe = $db->one('SELECT id FROM insights WHERE slug = :s', [':s' => $slug]);
        if ($dupe && (int)$dupe['id'] !== $id) json_out(['ok' => false, 'error' => "슬러그 '{$slug}' 는 이미 사용 중입니다."], 422);

        $summary = array_values(array_filter(array_map('trim', is_array($d['summary'] ?? null) ? $d['summary'] : preg_split('/\r?\n/', (string)($d['summary'] ?? '')))));
        $faq = []; foreach ((array)($d['faq'] ?? []) as $f) { $q = trim((string)($f['q'] ?? '')); $a = trim((string)($f['a'] ?? '')); if ($q !== '' && $a !== '') $faq[] = ['q' => $q, 'a' => $a]; }
        $links = []; foreach ((array)($d['links'] ?? []) as $l) { $t = trim((string)($l['title'] ?? '')); $u = trim((string)($l['url'] ?? '')); if ($t !== '' && $u !== '' && preg_match('#^(/|https?://)#', $u)) $links[] = ['title' => $t, 'url' => $u]; }
        $pubAt = trim((string)($d['published_at'] ?? ''));
        $pubAt = $pubAt ? date('Y-m-d H:i:s', strtotime($pubAt)) : date('Y-m-d H:i:s');
        $now = date('Y-m-d H:i:s');
        $row = [
            'slug' => $slug, 'category' => trim((string)($d['category'] ?? '')), 'title' => $title, 'meta_title' => mb_substr(trim((string)($d['meta_title'] ?? '')), 0, 255),
            'lead_text' => trim((string)($d['lead_text'] ?? '')), 'excerpt' => trim((string)($d['excerpt'] ?? '')),
            'description' => trim((string)($d['description'] ?? '')) ?: mb_substr(trim((string)($d['excerpt'] ?? '')), 0, 160),
            'keywords' => trim((string)($d['keywords'] ?? '')), 'author' => trim((string)($d['author'] ?? '')) ?: '글로파인드 전략팀',
            'read_minutes' => max(1, (int)($d['read_minutes'] ?? 8)),
            'hero_image' => trim((string)($d['hero_image'] ?? '')), 'hero_alt' => trim((string)($d['hero_alt'] ?? '')),
            'summary_json' => json_encode($summary, JSON_UNESCAPED_UNICODE), 'body_html' => (string)($d['body_html'] ?? ''),
            'faq_json' => json_encode($faq, JSON_UNESCAPED_UNICODE), 'links_json' => json_encode($links, JSON_UNESCAPED_UNICODE),
            'cta_text' => trim((string)($d['cta_text'] ?? '')), 'cta_label' => trim((string)($d['cta_label'] ?? '')),
            'is_published' => !empty($d['is_published']) ? 1 : 0, 'is_featured' => !empty($d['is_featured']) ? 1 : 0,
            'published_at' => $pubAt, 'updated_at' => $now,
        ];
        if ($row['is_published'] && $row['hero_image'] === '') json_out(['ok' => false, 'error' => '발행하려면 대표 이미지가 필요합니다.'], 422);
        $oldSlug = null;
        if ($id) {
            $old = $db->one('SELECT slug FROM insights WHERE id = :id', [':id' => $id]);
            if (!$old) json_out(['ok' => false, 'error' => '아티클을 찾을 수 없습니다.'], 404);
            $oldSlug = $old['slug'];
            $db->update('insights', $row, ['id' => $id]);
        } else {
            $row['created_at'] = $now; $row['views'] = 0;
            $id = $db->insert('insights', $row);
        }
        if ($row['is_featured']) $db->run('UPDATE insights SET is_featured = 0 WHERE id <> :id', [':id' => $id]);
        if ($oldSlug && $oldSlug !== $slug) pub_unpublish_file($oldSlug);
        $written = publish_all($db);
        json_out(['ok' => true, 'id' => $id, 'slug' => $slug, 'published' => $row['is_published'], 'written' => count($written)]);
    }

    if ($action === 'toggle') {
        $id = (int)($in['id'] ?? 0); $field = $in['field'] ?? ''; $val = !empty($in['value']) ? 1 : 0;
        if (!$id || !in_array($field, ['is_published', 'is_featured'], true)) json_out(['ok' => false, 'error' => '값이 올바르지 않습니다.'], 422);
        if ($field === 'is_published' && $val) {
            $r = $db->one('SELECT hero_image FROM insights WHERE id = :id', [':id' => $id]);
            if (!$r || $r['hero_image'] === '') json_out(['ok' => false, 'error' => '대표 이미지가 없는 글은 발행할 수 없습니다.'], 422);
        }
        $db->update('insights', [$field => $val, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        if ($field === 'is_featured' && $val) $db->run('UPDATE insights SET is_featured = 0 WHERE id <> :id', [':id' => $id]);
        publish_all($db);
        json_out(['ok' => true]);
    }

    if ($action === 'delete') {
        $ids = array_values(array_filter(array_map('intval', (array)($in['ids'] ?? []))));
        if (!$ids) json_out(['ok' => false, 'error' => '삭제할 항목이 없습니다.'], 422);
        foreach ($db->all('SELECT slug FROM insights WHERE id IN (' . implode(',', $ids) . ')') as $r) pub_unpublish_file($r['slug']);
        $db->delete('insights', ['id' => ['in' => $ids]]);
        publish_all($db);
        json_out(['ok' => true]);
    }

    if ($action === 'publish_all') {
        $written = publish_all($db);
        json_out(['ok' => true, 'written' => count($written), 'files' => $written]);
    }

    json_out(['ok' => false, 'error' => '알 수 없는 요청입니다.'], 400);
} catch (Throwable $e) {
    error_log('[admin/insights] ' . $e->getMessage());
    json_out(['ok' => false, 'error' => '처리 실패: ' . $e->getMessage()], 500);
}
