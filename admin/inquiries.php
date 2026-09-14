<?php
require_once __DIR__ . '/config.php';
require_admin_auth();
$db = get_db();
$services = service_labels(); $statuses = status_labels();
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 20;
$q = trim($_GET['q'] ?? ''); $st = trim($_GET['status'] ?? ''); $sv = trim($_GET['service'] ?? '');
$where = []; $p = [];
if ($st !== '' && isset($statuses[$st])) { $where[] = 'status = :st'; $p[':st'] = $st; }
if ($sv !== '' && isset($services[$sv])) { $where[] = 'service = :sv'; $p[':sv'] = $sv; }
if ($q !== '') { $where[] = '(company LIKE :q1 OR name LIKE :q2 OR email LIKE :q3 OR message LIKE :q4)'; $p[':q1'] = $p[':q2'] = $p[':q3'] = $p[':q4'] = "%$q%"; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = (int)$db->value("SELECT COUNT(*) FROM inquiries$w", $p);
$pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages); $off = ($page - 1) * $per;
$rows = $db->all("SELECT id, company, name, email, phone, service, country, status, created_at, message FROM inquiries$w ORDER BY created_at DESC, id DESC LIMIT $per OFFSET $off", $p);
$qs = fn($n) => '?' . http_build_query(['page' => $n, 'q' => $q, 'status' => $st, 'service' => $sv]);
$badge = fn($s) => $s === 'completed' ? 'done' : ($s === 'in_progress' ? 'progress' : 'pending');

$pageTitle = '문의 관리'; $activeMenu = 'inquiries'; $pageLead = '상담 신청 폼으로 접수된 문의. 행을 누르면 상세와 메모를 볼 수 있습니다.';
ob_start();
?>
<div class="page-actions">
  <div><h2>문의 <?= number_format($total) ?>건</h2><p class="helper">미처리 <?= $db->count('inquiries', ['status' => 'pending']) ?> · 처리중 <?= $db->count('inquiries', ['status' => 'in_progress']) ?> · 완료 <?= $db->count('inquiries', ['status' => 'completed']) ?></p></div>
  <div class="btn-group">
    <select id="bulkStatus" class="btn btn--ghost" data-bulk disabled style="padding-right:28px"><?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?>(으)로</option><?php endforeach; ?></select>
    <button type="button" class="btn" data-bulk id="btnBulkStatus" disabled>상태 일괄 변경</button>
    <button type="button" class="btn btn--danger" data-bulk id="btnDelete" disabled>선택 삭제</button>
    <a href="api/inquiries.php?action=export&<?= e(http_build_query(['q' => $q, 'status' => $st, 'service' => $sv])) ?>&_csrf=<?= e(csrf_token()) ?>" class="btn btn--ghost">CSV 내려받기</a>
  </div>
</div>

<div class="card">
  <div class="card__head">
    <form method="get" class="filters" data-autosubmit>
      <select name="status" aria-label="상태"><option value="">전체 상태</option><?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      <select name="service" aria-label="서비스"><option value="">전체 서비스</option><?php foreach ($services as $k => $l): ?><option value="<?= $k ?>" <?= $sv === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="회사 · 담당자 · 이메일 · 내용 검색" aria-label="검색">
      <button type="submit" class="btn btn--ghost btn--sm" style="height:42px">검색</button>
    </form>
  </div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th class="cb"><input type="checkbox" data-check-all aria-label="전체 선택"></th><th>회사 · 담당자</th><th>연락처</th><th>서비스</th><th>문의 요약</th><th>상태</th><th>접수일</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty">조건에 맞는 문의가 없습니다.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="cb"><input type="checkbox" class="row-cb" value="<?= (int)$r['id'] ?>" aria-label="선택"></td>
        <td><a href="#" class="row-link" data-open="<?= (int)$r['id'] ?>"><?= e($r['company']) ?></a><span class="cell-sub"><?= e($r['name']) ?></span></td>
        <td><?= e($r['email']) ?><span class="cell-sub"><?= e($r['phone']) ?></span></td>
        <td class="muted"><?= e($services[$r['service']] ?? $r['service']) ?><?php if ($r['country']): ?><span class="cell-sub"><?= e($r['country']) ?></span><?php endif; ?></td>
        <td class="muted" style="max-width:320px"><a href="#" data-open="<?= (int)$r['id'] ?>" style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', $r['message']), 0, 60, '…')) ?></a></td>
        <td><span class="badge badge--<?= $badge($r['status']) ?>"><?= e($statuses[$r['status']] ?? $r['status']) ?></span></td>
        <td class="muted nowrap"><?= e(substr($r["created_at"], 0, 16)) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if ($pages > 1): ?><nav class="pagination" aria-label="페이지"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= e($qs($i)) ?>" class="<?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</div>

<div class="modal" id="inqModal" role="dialog" aria-modal="true" aria-labelledby="inqTitle">
  <div class="modal__box">
    <div class="modal__head"><h2 id="inqTitle">문의 상세</h2><button type="button" class="modal__close" data-close="inqModal" aria-label="닫기">×</button></div>
    <div class="modal__body" id="inqBody">불러오는 중…</div>
    <div class="modal__foot"><button type="button" class="btn btn--ghost" data-close="inqModal">닫기</button><button type="button" class="btn btn--solid" id="btnSaveInq">상태 · 메모 저장</button></div>
  </div>
</div>

<script>
(function () {
  var services = <?= json_encode($services, JSON_UNESCAPED_UNICODE) ?>, statuses = <?= json_encode($statuses, JSON_UNESCAPED_UNICODE) ?>;
  var current = null;
  function open(id) {
    api('api/inquiries.php', { action: 'get', id: id }).then(function (r) {
      var d = r.data; current = d.id;
      var opts = Object.keys(statuses).map(function (k) { return '<option value="' + k + '"' + (d.status === k ? ' selected' : '') + '>' + statuses[k] + '</option>'; }).join('');
      document.getElementById('inqBody').innerHTML =
        '<dl class="kv">' +
        '<dt>회사</dt><dd>' + escapeHtml(d.company) + '</dd><dt>담당자</dt><dd>' + escapeHtml(d.name) + '</dd>' +
        '<dt>이메일</dt><dd><a href="mailto:' + escapeHtml(d.email) + '" class="card__link">' + escapeHtml(d.email) + '</a></dd>' +
        '<dt>연락처</dt><dd>' + escapeHtml(d.phone || '–') + '</dd>' +
        '<dt>관심 서비스</dt><dd>' + escapeHtml(services[d.service] || d.service) + '</dd><dt>희망 지역</dt><dd>' + escapeHtml(d.country || '–') + '</dd>' +
        '<dt>문의 내용</dt><dd class="pre">' + escapeHtml(d.message) + '</dd>' +
        '<dt>접수</dt><dd>' + escapeHtml(d.created_at) + (d.source_page ? ' · ' + escapeHtml(d.source_page) : '') + (d.processed_at ? '<br>처리 완료 ' + escapeHtml(d.processed_at) : '') + '</dd>' +
        '<dt>상태</dt><dd><div class="field" style="max-width:220px"><select id="inqStatus">' + opts + '</select></div></dd>' +
        '<dt>내부 메모</dt><dd><div class="field"><textarea id="inqNote" rows="4" placeholder="담당자, 통화 내용, 다음 액션">' + escapeHtml(d.admin_note || '') + '</textarea></div></dd>' +
        '</dl>';
      openModal('inqModal');
    });
  }
  document.addEventListener('click', function (e) { var a = e.target.closest('[data-open]'); if (a) { e.preventDefault(); open(a.dataset.open); } });
  document.getElementById('btnSaveInq').addEventListener('click', function () {
    if (!current) return;
    api('api/inquiries.php', { action: 'update', id: current, status: document.getElementById('inqStatus').value, note: document.getElementById('inqNote').value })
      .then(function () { toast('저장했습니다.'); setTimeout(function () { location.reload(); }, 500); });
  });
  document.getElementById('btnBulkStatus').addEventListener('click', function () {
    var ids = checkedIds(), s = document.getElementById('bulkStatus').value; if (!ids.length) return;
    api('api/inquiries.php', { action: 'bulk_status', ids: ids, status: s }).then(function () { location.reload(); });
  });
  document.getElementById('btnDelete').addEventListener('click', function () {
    var ids = checkedIds(); if (!ids.length || !confirm(ids.length + '건을 삭제할까요? 되돌릴 수 없습니다.')) return;
    api('api/inquiries.php', { action: 'delete', ids: ids }).then(function () { location.reload(); });
  });
  var auto = new URLSearchParams(location.search).get('open'); if (auto) open(auto);
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
