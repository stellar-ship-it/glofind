<?php
require_once __DIR__ . '/config.php';
require_admin_auth();
$db = get_db();
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 15;
$q = trim($_GET['q'] ?? ''); $cat = trim($_GET['cat'] ?? ''); $st = trim($_GET['status'] ?? '');
$where = []; $p = [];
if ($cat !== '') { $where[] = 'category = :cat'; $p[':cat'] = $cat; }
if ($st === 'published') $where[] = 'is_published = 1'; elseif ($st === 'draft') $where[] = 'is_published = 0';
if ($q !== '') { $where[] = '(title LIKE :q1 OR slug LIKE :q2 OR keywords LIKE :q3)'; $p[':q1'] = $p[':q2'] = $p[':q3'] = "%$q%"; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = (int)$db->value("SELECT COUNT(*) FROM insights$w", $p);
$pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages); $off = ($page - 1) * $per;
$rows = $db->all("SELECT id, slug, category, title, hero_image, is_published, is_featured, published_at, views, read_minutes FROM insights$w ORDER BY published_at DESC, id DESC LIMIT $per OFFSET $off", $p);
$cats = insight_categories($db);
$qs = fn($n) => '?' . http_build_query(['page' => $n, 'q' => $q, 'cat' => $cat, 'status' => $st]);

$pageTitle = '인사이트'; $activeMenu = 'insights'; $pageLead = '아티클을 저장하면 공개 페이지·목록·sitemap.xml 이 바로 다시 생성됩니다.';
ob_start();
?>
<div class="page-actions">
  <div><h2>아티클 <?= number_format($total) ?>편</h2><p class="helper">발행 <?= $db->count('insights', ['is_published' => 1]) ?> · 임시저장 <?= $db->count('insights', ['is_published' => 0]) ?></p></div>
  <div class="btn-group">
    <button type="button" class="btn btn--ghost" id="btnRebuild">전체 재발행</button>
    <button type="button" class="btn btn--danger" data-bulk id="btnDelete" disabled>선택 삭제</button>
    <a href="insight_edit.php" class="btn btn--solid">새 아티클</a>
  </div>
</div>

<div class="card">
  <div class="card__head">
    <form method="get" class="filters" data-autosubmit>
      <select name="cat" aria-label="카테고리"><option value="">전체 카테고리</option><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
      <select name="status" aria-label="상태"><option value="">전체 상태</option><option value="published" <?= $st === 'published' ? 'selected' : '' ?>>발행</option><option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>>임시저장</option></select>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="제목 · 슬러그 · 키워드 검색" aria-label="검색">
      <button type="submit" class="btn btn--ghost btn--sm" style="height:42px">검색</button>
    </form>
  </div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th class="cb"><input type="checkbox" data-check-all aria-label="전체 선택"></th><th>아티클</th><th>카테고리</th><th>발행</th><th>추천</th><th class="num">조회</th><th>발행일</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="8" class="empty">조건에 맞는 아티클이 없습니다.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="cb"><input type="checkbox" class="row-cb" value="<?= (int)$r['id'] ?>" aria-label="선택"></td>
        <td class="cell-main"><div style="display:flex; gap:12px; align-items:center"><?php if ($r['hero_image']): ?><img class="thumb" src="<?= e($r['hero_image']) ?>" alt="" loading="lazy"><?php else: ?><span class="thumb"></span><?php endif; ?><div><a href="insight_edit.php?id=<?= (int)$r['id'] ?>" class="row-link"><?= e($r['title']) ?></a><span class="cell-sub">/insights/<?= e($r['slug']) ?>/ · <?= (int)$r['read_minutes'] ?>분</span></div></div></td>
        <td class="muted"><?= e($r['category']) ?></td>
        <td><label class="toggle"><input type="checkbox" <?= $r['is_published'] ? 'checked' : '' ?> data-toggle="is_published" data-id="<?= (int)$r['id'] ?>" aria-label="발행"><i></i></label></td>
        <td><label class="toggle"><input type="checkbox" <?= $r['is_featured'] ? 'checked' : '' ?> data-toggle="is_featured" data-id="<?= (int)$r['id'] ?>" aria-label="추천"><i></i></label></td>
        <td class="num"><?= number_format($r['views']) ?></td>
        <td class="muted nowrap"><?= e(substr((string)$r["published_at"], 0, 10)) ?></td>
        <td style="white-space:nowrap"><a href="insight_edit.php?id=<?= (int)$r['id'] ?>" class="btn btn--ghost btn--sm">수정</a> <a href="<?= $r['is_published'] ? '/insights/' . e($r['slug']) . '/' : 'preview.php?id=' . (int)$r['id'] ?>" target="_blank" rel="noopener" class="btn btn--ghost btn--sm"><?= $r['is_published'] ? '보기' : '미리보기' ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if ($pages > 1): ?><nav class="pagination" aria-label="페이지"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= e($qs($i)) ?>" class="<?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</div>

<script>
document.querySelectorAll('[data-toggle]').forEach(function (cb) {
  cb.addEventListener('change', function () {
    api('api/insights.php', { action: 'toggle', id: cb.dataset.id, field: cb.dataset.toggle, value: cb.checked ? 1 : 0 })
      .then(function () { toast(cb.dataset.toggle === 'is_published' ? (cb.checked ? '발행했습니다.' : '발행을 내렸습니다.') : (cb.checked ? '추천 아티클로 지정했습니다.' : '추천을 해제했습니다.')); if (cb.dataset.toggle === 'is_featured' && cb.checked) setTimeout(function () { location.reload(); }, 600); })
      .catch(function () { cb.checked = !cb.checked; });
  });
});
document.getElementById('btnDelete').addEventListener('click', function () {
  var ids = checkedIds(); if (!ids.length) return;
  if (!confirm(ids.length + '편을 삭제합니다. 공개된 페이지 파일도 함께 삭제됩니다. 계속할까요?')) return;
  api('api/insights.php', { action: 'delete', ids: ids }).then(function () { location.reload(); });
});
document.getElementById('btnRebuild').addEventListener('click', function () {
  var b = this; b.disabled = true;
  api('api/insights.php', { action: 'publish_all' }).then(function (r) { toast('파일 ' + r.written + '개를 다시 생성했습니다.'); }).finally(function () { b.disabled = false; });
});
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
