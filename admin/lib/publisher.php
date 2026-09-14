<?php
/* 정적 발행기 — DB 의 insights 를 기존 정적 페이지와 같은 마크업으로 렌더링해 파일로 쓴다.
   왜 동적 렌더링이 아니라 정적 생성인가: 공개 사이트는 Vercel(정적) → 카페24 로 옮겨도 그대로 정적으로 두기 위해서다.
   발행 시점에만 PHP 가 돌고, 방문자는 HTML 파일을 받는다. */
const PUB_ASSET_VER = ['tokens' => '20260911g', 'site' => '20260914k', 'insights' => '20260911g', 'motion' => '20260911g'];
const PUB_STATIC_PAGES = [
    ['/', '1.0', 'weekly'], ['/about/', '0.8', 'monthly'], ['/cases/', '0.8', 'monthly'], ['/services/', '0.9', 'monthly'],
    ['/services/gtm/', '0.8', 'monthly'], ['/services/seo-geo-aeo/', '0.8', 'monthly'], ['/services/media/', '0.8', 'monthly'],
    ['/services/ads/', '0.8', 'monthly'], ['/services/influencer/', '0.8', 'monthly'],
];
const PUB_STATIC_LASTMOD = '2026-09-14';

function pub_partial(string $name): string {
    return file_get_contents(__DIR__ . '/../templates/partials/' . $name . '.html');
}
function pub_url(string $slug): string { return SITE_URL . '/insights/' . $slug . '/'; }
function pub_abs(string $path): string { return str_starts_with($path, 'http') ? $path : SITE_URL . $path; }
function pub_date_kr(string $dt): string { return date('Y. m. d', strtotime($dt)); }
function pub_date_iso(string $dt): string { return date('Y-m-d', strtotime($dt)); }
function pub_text(string $html): string { return trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')); }
function pub_json(array $v): string { return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); }

/* DB 행 → 템플릿용 배열 (JSON 필드 디코드, 본문 섹션 분할) */
function pub_prepare(array $r): array {
    $r['summary'] = json_decode($r['summary_json'] ?: '[]', true) ?: [];
    $r['faq']     = json_decode($r['faq_json'] ?: '[]', true) ?: [];
    $r['links']   = json_decode($r['links_json'] ?: '[]', true) ?: [];
    $r['sections'] = [];
    $chunks = preg_split('/(?=<h2[\s>])/i', trim((string)$r['body_html']));
    $n = 0;
    foreach ($chunks as $c) {
        $c = trim($c); if ($c === '') continue;
        $n++;
        preg_match('/<h2[^>]*>(.*?)<\/h2>/is', $c, $m);
        $r['sections'][] = ['id' => 'sec-' . $n, 'title' => isset($m[1]) ? pub_text($m[1]) : '섹션 ' . $n, 'html' => $c];
    }
    return $r;
}

function pub_card(array $a, bool $media, string $htag = 'h3'): string {
    $url = '/insights/' . $a['slug'] . '/';
    $t = htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
    $h = '  <article class="ins-card reveal" data-cat="' . htmlspecialchars($a['category'], ENT_QUOTES, 'UTF-8') . "\">\n";
    if ($media) $h .= '    <a href="' . $url . '" class="ins-card__media reveal-media duo" tabindex="-1" aria-hidden="true"><img src="' . htmlspecialchars($a['hero_image'], ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy" width="1600" height="900" decoding="async"></a>' . "\n";
    $h .= "    <div class=\"ins-card__body\">\n";
    $h .= '      <p class="ins-meta"><span class="tag tag--cyan">' . htmlspecialchars($a['category'], ENT_QUOTES, 'UTF-8') . '</span><span class="tag"><time datetime="' . pub_date_iso($a['published_at']) . '">' . pub_date_kr($a['published_at']) . "</time></span></p>\n";
    $h .= "      <$htag><a href=\"$url\">$t</a></$htag>\n";
    $h .= '      <p class="ins-card__desc">' . htmlspecialchars($a['excerpt'], ENT_QUOTES, 'UTF-8') . "</p>\n";
    $h .= '      <p class="ins-card__foot"><span>' . (int)$a['read_minutes'] . '분 읽기</span><a href="' . $url . '" class="arrow" aria-label="' . $t . ' 읽기">읽기 ' . $arrow . "</a></p>\n";
    $h .= "    </div>\n  </article>\n";
    return $h;
}

function pub_related(array $self, array $all, int $n = 3): array {
    $same = []; $others = [];
    foreach ($all as $a) {
        if ($a['slug'] === $self['slug']) continue;
        if ($a['category'] === $self['category']) $same[] = $a; else $others[] = $a;
    }
    return array_slice(array_merge($same, $others), 0, $n);
}

function pub_render_article(array $row, array $all): string {
    $a = pub_prepare($row);
    $related = pub_related($a, $all);
    ob_start();
    include __DIR__ . '/../templates/article.php';
    return ob_get_clean();
}

function pub_render_index(array $all, array $categories): string {
    $featured = null;
    foreach ($all as $a) if (!empty($a['is_featured'])) { $featured = $a; break; }
    if (!$featured && $all) $featured = $all[0];
    $list = array_values(array_filter($all, fn($a) => !$featured || $a['slug'] !== $featured['slug']));
    $counts = [];
    foreach ($all as $a) $counts[$a['category']] = ($counts[$a['category']] ?? 0) + 1;
    $cats = array_values(array_unique(array_merge($categories, array_keys($counts))));
    ob_start();
    include __DIR__ . '/../templates/insights-index.php';
    return ob_get_clean();
}

function pub_render_sitemap(array $all): string {
    $x = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach (PUB_STATIC_PAGES as [$p, $pr, $cf]) $x .= "  <url><loc>" . SITE_URL . $p . "</loc><lastmod>" . PUB_STATIC_LASTMOD . "</lastmod><changefreq>$cf</changefreq><priority>$pr</priority></url>\n";
    $latest = $all ? pub_date_iso(max(array_map(fn($a) => $a['updated_at'] ?: $a['published_at'], $all))) : PUB_STATIC_LASTMOD;
    $x .= "  <url><loc>" . SITE_URL . "/insights/</loc><lastmod>$latest</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
    foreach ($all as $a) $x .= "  <url><loc>" . pub_url($a['slug']) . "</loc><lastmod>" . pub_date_iso($a['updated_at'] ?: $a['published_at']) . "</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>\n";
    return $x . "</urlset>\n";
}

function pub_write(string $rel, string $content): string {
    $path = SITE_ROOT . '/' . ltrim($rel, '/');
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new Exception("폴더를 만들 수 없습니다: $rel");
    if (file_put_contents($path, $content, LOCK_EX) === false) throw new Exception("파일을 쓸 수 없습니다: $rel");
    return $rel;
}

function pub_published(Database $db): array {
    return $db->all('SELECT * FROM insights WHERE is_published = 1 ORDER BY published_at DESC, id DESC');
}

/* 전체 재발행: 발행 글 파일 생성, 미발행 글 파일 제거, 목록·사이트맵 갱신. 쓴 파일 목록을 돌려준다. */
function publish_all(Database $db): array {
    $all = pub_published($db);
    $written = [];
    foreach ($all as $a) $written[] = pub_write('insights/' . $a['slug'] . '/index.html', pub_render_article($a, $all));
    foreach ($db->all('SELECT slug FROM insights WHERE is_published = 0') as $d) pub_unpublish_file($d['slug']);
    $written[] = pub_write('insights/index.html', pub_render_index($all, insight_categories($db)));
    $written[] = pub_write('sitemap.xml', pub_render_sitemap($all));
    return $written;
}

function pub_unpublish_file(string $slug): void {
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) return;
    $f = SITE_ROOT . '/insights/' . $slug . '/index.html';
    if (file_exists($f)) { @unlink($f); @rmdir(dirname($f)); }
}
