<?php /* 아티클 템플릿 — 변수: $a(pub_prepare 결과), $related */ 
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$url = pub_url($a['slug']); $img = pub_abs($a['hero_image']); $pub = pub_date_iso($a['published_at']); $mod = pub_date_iso($a['updated_at'] ?: $a['published_at']);
$graph = [
  ['@type' => 'Organization', '@id' => SITE_URL . '/#organization', 'name' => '글로파인드', 'alternateName' => 'Glofind', 'url' => SITE_URL . '/', 'logo' => SITE_URL . '/assets/images/logo-light.webp', 'email' => 'hello@glofind.co', 'description' => 'B2B 기업의 해외 시장 진입과 성장을 설계하는 글로벌 마케팅 파트너.', 'sameAs' => ['https://www.linkedin.com/company/glofind']],
  ['@type' => 'WebSite', '@id' => SITE_URL . '/#website', 'url' => SITE_URL . '/', 'name' => 'Glofind', 'publisher' => ['@id' => SITE_URL . '/#organization'], 'inLanguage' => 'ko-KR'],
  ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => '홈', 'item' => SITE_URL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Insights', 'item' => SITE_URL . '/insights/'],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $a['title'], 'item' => $url]]],
  ['@type' => 'Article', '@id' => $url . '#article', 'headline' => $a['title'], 'description' => $a['description'], 'image' => [$img], 'inLanguage' => 'ko-KR',
   'datePublished' => $pub, 'dateModified' => $mod, 'author' => ['@type' => 'Organization', '@id' => SITE_URL . '/#organization', 'name' => $a['author']],
   'publisher' => ['@id' => SITE_URL . '/#organization'], 'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url], 'articleSection' => $a['category'],
   'keywords' => $a['keywords'], 'isPartOf' => ['@id' => SITE_URL . '/#website'], 'abstract' => $a['lead_text']],
];
if ($a['faq']) $graph[] = ['@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => pub_text($f['a'])]], $a['faq'])];
$arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <title><?= $e($a['meta_title'] ?: $a['title']) ?> | Glofind</title>
  <meta name="description" content="<?= $e($a['description']) ?>">
  <meta name="keywords" content="<?= $e($a['keywords']) ?>">
  <meta name="author" content="<?= $e($a['author']) ?>">
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
  <meta property="og:type" content="article">
  <meta property="og:site_name" content="Glofind">
  <meta property="og:locale" content="ko_KR">
  <meta property="og:title" content="<?= $e($a['title']) ?>">
  <meta property="og:description" content="<?= $e($a['description']) ?>">
  <meta property="og:url" content="<?= $e($url) ?>">
  <meta property="og:image" content="<?= $e($img) ?>">
  <meta property="article:published_time" content="<?= $pub ?>T09:00:00+09:00">
  <meta property="article:section" content="<?= $e($a['category']) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $e($a['title']) ?>">
  <meta name="twitter:description" content="<?= $e($a['description']) ?>">
  <meta name="twitter:image" content="<?= $e($img) ?>">
  <meta name="theme-color" content="#191F28">
  <link rel="canonical" href="<?= $e($url) ?>">

  <script type="application/ld+json">
<?= pub_json(['@context' => 'https://schema.org', '@graph' => $graph]) ?>

  </script>

  <link rel="stylesheet" href="/assets/css/tokens.css?v=<?= PUB_ASSET_VER['tokens'] ?>">
  <link rel="stylesheet" href="/assets/css/site.css?v=<?= PUB_ASSET_VER['site'] ?>">
  <link rel="stylesheet" href="/assets/css/pages/insights.css?v=<?= PUB_ASSET_VER['insights'] ?>">
  <link rel="stylesheet" href="/assets/css/motion.css?v=<?= PUB_ASSET_VER['motion'] ?>">
</head>
<?= pub_partial('site-top') ?>
<main id="main">
<article>

<!-- ============================================================
     01. PAGE HERO — 8:4 · 크럼 / 카테고리 라벨 + 제목 좌 / 메타 + 한 줄 답변 우
     ============================================================ -->
<section class="page-hero" aria-labelledby="page-h1">
  <div class="container">
    <nav class="page-hero__crumb" aria-label="breadcrumb">
      <i aria-hidden="true"></i>
      <a href="/">Home</a>
      <span aria-hidden="true">/</span>
      <a href="/insights/">Insights</a>
      <span aria-hidden="true">/</span>
      <span aria-current="page"><?= $e($a['category']) ?></span>
    </nav>
    <div class="page-hero__grid">
      <div>
        <p class="label reveal"><?= $e($a['category']) ?></p>
        <h1 class="t-display-2 m-split ins-title" id="page-h1"><?= $e($a['title']) ?></h1>
      </div>
      <div>
        <p class="ins-meta reveal"><span class="tag"><?= $e($a['author']) ?></span><span class="tag"><time datetime="<?= $pub ?>"><?= pub_date_kr($a['published_at']) ?></time></span><span class="tag"><?= (int)$a['read_minutes'] ?>분 읽기</span></p>
        <p class="t-lead reveal"><?= $e($a['lead_text']) ?></p>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     02. HERO MEDIA — 컨테이너 폭 21:9
     ============================================================ -->
<div class="ins-hero">
  <div class="container">
    <figure class="ins-hero__fig reveal-media">
      <img src="<?= $e($a['hero_image']) ?>" alt="<?= $e($a['hero_alt']) ?>" width="1600" height="900" fetchpriority="high" decoding="async">
    </figure>
  </div>
</div>

<!-- ============================================================
     03. BODY — 3:8 · 좌 스티키 목차 / 우 본문 (.legal / .prose 재사용)
     ============================================================ -->
<section class="legal" aria-label="본문">
  <div class="container">
    <div class="legal__grid">

      <nav class="legal__toc reveal" aria-label="목차" id="toc">
        <p class="ins-toc__t">목차</p>
<?php if ($a['summary']): ?>        <a href="#summary">핵심 요약</a>
<?php endif; foreach ($a['sections'] as $s): ?>        <a href="#<?= $s['id'] ?>"><?= $e($s['title']) ?></a>
<?php endforeach; if ($a['faq']): ?>        <a href="#faq">자주 묻는 질문</a>
<?php endif; ?>      </nav>

      <div class="prose prose--article reveal">
<?php if ($a['summary']): ?>
        <div class="prose__summary" id="summary">
          <h2>핵심 요약</h2>
          <ul role="list">
<?php foreach ($a['summary'] as $li): ?>            <li><?= $li ?></li>
<?php endforeach; ?>          </ul>
        </div>
<?php endif; ?>

<?php foreach ($a['sections'] as $s): ?>        <section id="<?= $s['id'] ?>"><?= $s['html'] ?></section>

<?php endforeach; ?>
<?php if ($a['faq']): ?>        <section class="prose__faq" id="faq">
          <h2>자주 묻는 질문</h2>
<?php foreach ($a['faq'] as $f): ?>          <details><summary><?= $e($f['q']) ?></summary><div><?= str_starts_with(trim($f['a']), '<') ? $f['a'] : '<p>' . $e($f['a']) . '</p>' ?></div></details>
<?php endforeach; ?>        </section>
<?php endif; ?>
<?php if ($a['links']): ?>
        <div class="prose__links">
          <p class="prose__links-t">함께 보면 좋은 페이지</p>
          <ul role="list">
<?php foreach ($a['links'] as $l): ?>            <li><a href="<?= $e($l['url']) ?>"><?= $e($l['title']) ?></a></li>
<?php endforeach; ?>          </ul>
        </div>
<?php endif; ?>
<?php if ($a['cta_text'] || $a['cta_label']): ?>
        <div class="prose__cta">
          <p><?= $e($a['cta_text']) ?></p>
          <a href="/#contact" class="arrow"><?= $e($a['cta_label'] ?: '전략 세션 신청') ?> <?= $arrow ?></a>
        </div>
<?php endif; ?>

      </div>
    </div>
  </div>
</section>

</article>

<!-- ============================================================
     04. RELATED — 3열 카드 · 쿨 그레이
     ============================================================ -->
<section class="ins-related" aria-labelledby="rel-heading">
  <div class="container">
    <div class="ins-related__head">
      <h2 class="t-h2 m-split" id="rel-heading">함께 읽으면 좋은 아티클</h2>
    </div>
    <div class="ins-grid">
<?php foreach ($related as $r) echo pub_card($r, true, 'h3'); ?>    </div>
  </div>
</section>

<?= pub_partial('article-bottom') ?>
