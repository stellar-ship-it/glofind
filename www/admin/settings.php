<?php
require_once __DIR__ . '/config.php';
require_admin_auth();
$db = get_db();
$notify = $db->setting('notify_email', ADMIN_EMAIL);
$cats = implode("\n", insight_categories($db));
$writable = ['insights/' => is_writable(SITE_ROOT . '/insights'), 'assets/uploads/' => is_writable(SITE_ROOT . '/assets/uploads'), 'sitemap.xml' => is_writable(SITE_ROOT . '/sitemap.xml')];
$pageTitle = '설정'; $activeMenu = 'settings'; $pageLead = '알림 메일, 카테고리 순서, 비밀번호.';
ob_start();
?>
<section class="grid-2">
  <div class="card">
    <div class="card__head"><div><p class="micro">Notification</p><h2>문의 알림 · 카테고리</h2></div></div>
    <div class="card__body" style="display:grid; gap:20px">
      <div class="field"><label for="notify_email">문의 알림 받을 이메일</label><input type="email" id="notify_email" value="<?= e($notify) ?>"><span class="hint">상담 신청이 들어오면 이 주소로 메일이 갑니다. 발신은 <?= e(ADMIN_EMAIL) ?>.</span></div>
      <div class="field"><label for="categories">인사이트 카테고리 (한 줄에 하나 · 표시 순서)</label><textarea id="categories" rows="6"><?= e($cats) ?></textarea><span class="hint">목록 페이지의 필터 버튼 순서가 됩니다. 저장하면 목록을 다시 생성합니다.</span></div>
      <div><button type="button" class="btn btn--solid" id="btnSaveSettings">저장</button></div>
    </div>
  </div>

  <div style="display:grid; gap:24px; align-content:start">
    <div class="card">
      <div class="card__head"><div><p class="micro">Security</p><h2>비밀번호 변경</h2></div></div>
      <div class="card__body" style="display:grid; gap:16px">
        <div class="field"><label for="pw_current">현재 비밀번호</label><input type="password" id="pw_current" autocomplete="current-password"></div>
        <div class="field"><label for="pw_new">새 비밀번호 (8자 이상)</label><input type="password" id="pw_new" autocomplete="new-password" minlength="8"></div>
        <div class="field"><label for="pw_new2">새 비밀번호 확인</label><input type="password" id="pw_new2" autocomplete="new-password" minlength="8"></div>
        <div><button type="button" class="btn" id="btnPassword">비밀번호 변경</button></div>
      </div>
    </div>
    <div class="card card--tint">
      <div class="card__head"><div><p class="micro">System</p><h2>환경</h2></div></div>
      <div class="card__body">
        <dl class="kv">
          <dt>관리자 ID</dt><dd><?= e(ADMIN_ID) ?></dd>
          <dt>데이터베이스</dt><dd><?= e(strtoupper($db->driver())) ?><?= $db->driver() === 'sqlite' ? ' (로컬 개발용 — 운영은 MySQL)' : '' ?></dd>
          <dt>PHP</dt><dd><?= e(PHP_VERSION) ?> · GD webp <?= function_exists('imagewebp') ? '지원' : '미지원' ?></dd>
          <dt>사이트 URL</dt><dd><?= e(SITE_URL) ?></dd>
          <dt>쓰기 권한</dt><dd><?php foreach ($writable as $k => $ok): ?><span class="badge <?= $ok ? 'badge--done' : 'badge--pending' ?>" style="margin:0 6px 6px 0"><?= e($k) ?> <?= $ok ? 'OK' : '불가' ?></span><?php endforeach; ?></dd>
        </dl>
        <p class="note" style="margin-top:14px">쓰기 권한이 '불가'면 발행이 실패합니다. FTP 에서 해당 폴더 권한을 755(소유자 쓰기)로 맞추세요.</p>
      </div>
    </div>
  </div>
</section>
<script>
document.getElementById('btnSaveSettings').addEventListener('click', function () {
  api('api/settings.php', { action: 'save', notify_email: document.getElementById('notify_email').value, categories: document.getElementById('categories').value }).then(function () { toast('저장했습니다.'); });
});
document.getElementById('btnPassword').addEventListener('click', function () {
  var a = document.getElementById('pw_new').value, b = document.getElementById('pw_new2').value;
  if (a.length < 8) { toast('새 비밀번호는 8자 이상이어야 합니다.', true); return; }
  if (a !== b) { toast('새 비밀번호가 서로 다릅니다.', true); return; }
  api('api/settings.php', { action: 'password', current: document.getElementById('pw_current').value, password: a }).then(function () { toast('비밀번호를 변경했습니다. 다시 로그인하세요.'); setTimeout(function () { location.href = 'logout.php'; }, 1200); });
});
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
