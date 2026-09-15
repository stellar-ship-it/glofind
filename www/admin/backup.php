<?php
require_once __DIR__ . '/config.php';
require_admin_auth();
$db = get_db();
$counts = ['insights' => $db->count('insights'), 'inquiries' => $db->count('inquiries'), 'visitor_stats' => $db->count('visitor_stats')];
$pageTitle = '백업 · 복원'; $activeMenu = 'backup'; $pageLead = '호스팅을 옮기거나 파일을 덮어쓰기 전에 DB 내용을 내려받아 두세요.';
ob_start();
?>
<section class="grid-2">
  <div class="card">
    <div class="card__head"><div><p class="micro">Export</p><h2>백업 내려받기</h2></div></div>
    <div class="card__body" style="display:grid; gap:16px">
      <p class="note">아티클 <b><?= number_format($counts['insights']) ?>편</b> · 문의 <b><?= number_format($counts['inquiries']) ?>건</b> · 방문 기록 <b><?= number_format($counts['visitor_stats']) ?>행</b> · 설정을 JSON 한 파일로 내려받습니다. 업로드한 이미지(assets/uploads)는 FTP 로 따로 받으세요.</p>
      <div class="btn-group">
        <a href="api/backup.php?action=export&_csrf=<?= e(csrf_token()) ?>" class="btn btn--solid">전체 백업 (JSON)</a>
        <a href="api/backup.php?action=export&skip_visits=1&_csrf=<?= e(csrf_token()) ?>" class="btn btn--ghost">방문 기록 제외</a>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card__head"><div><p class="micro">Import</p><h2>백업 파일로 복원</h2></div></div>
    <div class="card__body" style="display:grid; gap:16px">
      <p class="note">백업 시점의 아티클·문의·설정으로 <b>덮어씁니다</b>(현재 데이터 삭제). 복원 후 공개 페이지를 전부 다시 생성합니다. 비밀번호는 백업에 포함되지 않습니다.</p>
      <div class="btn-group">
        <button type="button" class="btn btn--danger" onclick="document.getElementById('backupFile').click()">백업 파일 선택 · 복원</button>
        <input type="file" id="backupFile" accept=".json,application/json" hidden>
        <span class="hint" id="restoreStatus"></span>
      </div>
    </div>
  </div>
</section>
<script>
document.getElementById('backupFile').addEventListener('change', function () {
  var f = this.files[0]; this.value = ''; if (!f) return;
  if (!confirm('현재 데이터를 백업 파일 내용으로 교체합니다. 계속할까요?')) return;
  var fd = new FormData(); fd.append('action', 'import'); fd.append('backup', f);
  var s = document.getElementById('restoreStatus'); s.textContent = '복원 중…';
  api('api/backup.php', fd).then(function (r) { s.textContent = '복원 완료 — 아티클 ' + r.insights + '편 · 문의 ' + r.inquiries + '건 · 파일 ' + r.written + '개 재생성'; toast('복원했습니다.'); })
    .catch(function () { s.textContent = '복원 실패'; });
});
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
