<?php
/* 공통 레이아웃 — 각 페이지는 $pageTitle, $activeMenu, $content 를 채운 뒤 include 한다. */
require_once __DIR__ . '/config.php';
$pageTitle  = $pageTitle ?? 'Admin';
$activeMenu = $activeMenu ?? 'dashboard';
$pageLead   = $pageLead ?? '';
$menu = [
    ['key' => 'dashboard', 'label' => '대시보드', 'href' => 'dashboard.php', 'group' => '',
     'icon' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
    ['key' => 'insights', 'label' => '인사이트', 'href' => 'insights.php', 'group' => '콘텐츠',
     'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
    ['key' => 'inquiries', 'label' => '문의 관리', 'href' => 'inquiries.php', 'group' => '고객',
     'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
    ['key' => 'settings', 'label' => '설정', 'href' => 'settings.php', 'group' => '시스템',
     'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
    ['key' => 'backup', 'label' => '백업 · 복원', 'href' => 'backup.php', 'group' => '시스템',
     'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'],
];
$pendingCount = 0;
try { $pendingCount = get_db()->count('inquiries', ['status' => 'pending']); } catch (Throwable $e) {}
$lastGroup = null;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle) ?> — Glofind Admin</title>
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/admin.css?v=20260914c">
  <script src="assets/js/admin.js?v=20260914b"></script>
</head>
<body>
<div class="adm">
  <aside class="adm-side">
    <a class="adm-logo" href="dashboard.php"><img src="/assets/images/logo-dark.webp" alt="Glofind" width="98" height="24"><span>Admin</span></a>
    <nav class="adm-nav" aria-label="관리자 메뉴">
      <?php foreach ($menu as $m): ?>
        <?php if ($m['group'] !== $lastGroup): $lastGroup = $m['group']; ?>
          <?php if ($m['group'] !== ''): ?><p class="adm-nav__group"><?= e($m['group']) ?></p><?php endif; ?>
        <?php endif; ?>
        <a href="<?= e($m['href']) ?>" class="<?= $activeMenu === $m['key'] ? 'is-active' : '' ?>" <?= $activeMenu === $m['key'] ? 'aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $m['icon'] ?></svg>
          <span><?= e($m['label']) ?></span>
          <?php if ($m['key'] === 'inquiries' && $pendingCount > 0): ?><b class="adm-nav__count"><?= (int)$pendingCount ?></b><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="adm-side__foot">
      <a href="/" target="_blank" rel="noopener">사이트 보기 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></a>
      <a href="logout.php">로그아웃</a>
    </div>
  </aside>

  <div class="adm-main">
    <header class="adm-head">
      <button type="button" class="adm-burger" aria-label="메뉴 열기" aria-expanded="false" data-burger><span></span></button>
      <div>
        <h1 class="adm-head__title"><?= e($pageTitle) ?></h1>
        <?php if ($pageLead): ?><p class="adm-head__lead"><?= e($pageLead) ?></p><?php endif; ?>
      </div>
      <div class="adm-head__right">
        <span class="adm-user"><i aria-hidden="true"></i><?= e(ADMIN_ID) ?></span>
        <time class="adm-clock" data-clock></time>
      </div>
    </header>
    <main class="adm-content">
      <?= $content ?? '' ?>
    </main>
  </div>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
