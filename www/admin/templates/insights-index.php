<?php /* 인사이트 목록 템플릿 — 변수: $all, $featured, $list, $cats, $counts */
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$desc = '해외 시장 진입, 해외 진출 전략, SEO·GEO·AEO, 인플루언서 마케팅에 관한 실전 인사이트를 공유합니다.';
$items = []; $pos = 0;
foreach ($all as $a) $items[] = ['@type' => 'ListItem', 'position' => ++$pos, 'url' => pub_url($a['slug']), 'name' => $a['title']];
$graph = [
  ['@type' => 'CollectionPage', '@id' => SITE_URL . '/insights/#collection', 'url' => SITE_URL . '/insights/', 'name' => '글로벌 B2B 마케팅 인사이트 | Glofind', 'description' => '해외 시장 진입, 해외 진출 전략, SEO·GEO·AEO, 인플루언서 마케팅에 관한 실전 인사이트.', 'inLanguage' => 'ko-KR', 'isPartOf' => ['@id' => SITE_URL . '/#website'], 'publisher' => ['@id' => SITE_URL . '/#organization']],
  ['@type' => 'BreadcrumbList', '@id' => SITE_URL . '/insights/#breadcrumb', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => '홈', 'item' => SITE_URL . '/'], ['@type' => 'ListItem', 'position' => 2, 'name' => 'Insights', 'item' => SITE_URL . '/insights/']]],
  ['@type' => 'ItemList', '@id' => SITE_URL . '/insights/#list', 'name' => '글로파인드 인사이트 아티클', 'numberOfItems' => count($items), 'itemListElement' => $items],
];
$arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$ogImg = $featured ? pub_abs($featured['hero_image']) : SITE_URL . '/assets/images/og-home.jpg';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <title>Insights — 글로벌 B2B 마케팅 인사이트 | Glofind</title>
  <meta name="description" content="<?= $e($desc) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Glofind">
  <meta property="og:locale" content="ko_KR">
  <meta property="og:title" content="글로벌 B2B 마케팅 인사이트 | Glofind">
  <meta property="og:description" content="<?= $e($desc) ?>">
  <meta property="og:url" content="<?= SITE_URL ?>/insights/">
  <meta property="og:image" content="<?= $e($ogImg) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#191F28">
  <link rel="canonical" href="<?= SITE_URL ?>/insights/">

  <script type="application/ld+json">
<?= pub_json(['@context' => 'https://schema.org', '@graph' => $graph]) ?>

  </script>

  <link rel="stylesheet" href="/assets/css/tokens.css?v=<?= PUB_ASSET_VER['tokens'] ?>">
  <link rel="stylesheet" href="/assets/css/site.css?v=<?= PUB_ASSET_VER['site'] ?>">
  <link rel="stylesheet" href="/assets/css/pages/insights.css?v=<?= PUB_ASSET_VER['insights'] ?>">
  <link rel="stylesheet" href="/assets/css/motion.css?v=<?= PUB_ASSET_VER['motion'] ?>">
</head>
<?= pub_partial('index-top') ?>
<main id="main">

<!-- ============================================================
     01. PAGE HERO — 크럼 / 제목 좌 / 리드 우 / 카테고리 필터
     ============================================================ -->
<section class="page-hero" aria-labelledby="page-h1">
  <div class="container">
    <nav class="page-hero__crumb" aria-label="breadcrumb">
      <i aria-hidden="true"></i>
      <a href="/">Home</a>
      <span aria-hidden="true">/</span>
      <span aria-current="page">Insights</span>
    </nav>
    <div class="page-hero__grid">
      <h1 class="t-display-2 m-split" id="page-h1">글로벌 B2B<br>마케팅 인사이트<em>.</em></h1>
      <div>
        <p class="t-lead reveal">해외 시장 진입·해외 진출 전략·SEO·GEO·AEO·인플루언서 마케팅. 실전에서 검증한 인사이트를 공유합니다.</p>
      </div>
    </div>
    <div class="ins-filter reveal" role="tablist" aria-label="카테고리 필터">
      <button type="button" role="tab" aria-selected="true" data-cat="all">전체<b><?= count($all) ?></b></button>
<?php foreach ($cats as $c): if (empty($counts[$c])) continue; ?>      <button type="button" role="tab" aria-selected="false" data-cat="<?= $e($c) ?>"><?= $e($c) ?><b><?= $counts[$c] ?></b></button>
<?php endforeach; ?>    </div>
  </div>
</section>

<?php if ($featured): ?>
<!-- ============================================================
     02. FEATURED — 7:5 · 사진 좌 / 본문 우
     ============================================================ -->
<section class="ins-featured" aria-labelledby="featured-h">
  <div class="container">
    <p class="label reveal">Featured</p>
    <h2 class="sr-only" id="featured-h">추천 아티클</h2>
    <article class="ins-feature reveal" data-cat="<?= $e($featured['category']) ?>">
      <a href="/insights/<?= $e($featured['slug']) ?>/" class="ins-feature__media reveal-media duo" tabindex="-1" aria-hidden="true"><img src="<?= $e($featured['hero_image']) ?>" alt="" loading="eager" width="1600" height="900" decoding="async"></a>
      <div class="ins-feature__body">
        <p class="ins-meta"><span class="tag tag--cyan"><?= $e($featured['category']) ?></span><span class="tag"><?= $e($featured['author']) ?></span><span class="tag"><time datetime="<?= pub_date_iso($featured['published_at']) ?>"><?= pub_date_kr($featured['published_at']) ?></time></span><span class="tag"><?= (int)$featured['read_minutes'] ?>분 읽기</span></p>
        <h2><a href="/insights/<?= $e($featured['slug']) ?>/"><?= $e($featured['title']) ?></a></h2>
        <p><?= $e($featured['excerpt']) ?></p>
        <a href="/insights/<?= $e($featured['slug']) ?>/" class="arrow">아티클 읽기 <?= $arrow ?></a>
      </div>
    </article>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     03. ALL ARTICLES — 헤어라인 3열 격자 (≤1023 1열) · 쿨 그레이
     ============================================================ -->
<section class="ins-list" aria-labelledby="articles-h">
  <div class="container">
    <div class="ins-list__head">
      <div>
        <p class="label reveal">Latest</p>
        <h2 class="t-h2 m-split" id="articles-h">최신 아티클</h2>
      </div>
      <p class="ins-list__count reveal" aria-live="polite"><b id="ins-count"><?= count($all) ?></b> / <?= count($all) ?></p>
    </div>
    <div class="ins-grid ins-grid--lines" id="ins-grid">
<?php foreach ($list as $a) echo pub_card($a, false, 'h3'); ?>    </div>
  </div>
</section>

<?= pub_partial('index-bottom') ?>
