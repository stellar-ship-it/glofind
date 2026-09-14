<?php
require_once __DIR__ . '/config.php';
require_admin_auth();
require_once __DIR__ . '/lib/stats.php';

$db = get_db();
$today = date('Y-m-d'); $yesterday = date('Y-m-d', strtotime('-1 day'));
$series = visitor_series($db);
$d = $series['daily']; $n = count($d);
$todayUv = $d[$n-1]['uv']; $todayPv = $d[$n-1]['pv']; $yUv = $d[$n-2]['uv'];
$week = $series['weekly'][11]; $lastWeek = $series['weekly'][10];
$month = $series['monthly'][11];
$pending = $db->count('inquiries', ['status' => 'pending']);
$todayInq = (int)$db->value('SELECT COUNT(*) FROM inquiries WHERE created_at >= :t', [':t' => $today . ' 00:00:00']);
$totalInq = $db->count('inquiries');
$published = $db->count('insights', ['is_published' => 1]);
$drafts = $db->count('insights', ['is_published' => 0]);
$recentInq = $db->all('SELECT id, company, name, service, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 6');
$recentIns = $db->all('SELECT id, slug, title, category, is_published, published_at, views FROM insights ORDER BY published_at DESC LIMIT 5');
$pages = top_pages($db);
$services = service_labels(); $statuses = status_labels();
$delta = fn($now, $prev) => $prev > 0 ? round(($now - $prev) / $prev * 100) : null;

$pageTitle = '대시보드'; $activeMenu = 'dashboard'; $pageLead = date('Y년 n월 j일') . ' 기준';
ob_start();
?>
<section class="stats" aria-label="핵심 지표">
  <div class="stat">
    <p class="stat__label">오늘 방문자</p>
    <p class="stat__value"><?= number_format($todayUv) ?><small>명 · 조회 <?= number_format($todayPv) ?></small></p>
    <p class="stat__helper">어제 <?= number_format($yUv) ?>명<?php if (($dl = $delta($todayUv, $yUv)) !== null): ?> · <b class="<?= $dl < 0 ? 'down' : '' ?>"><?= $dl >= 0 ? '+' : '' ?><?= $dl ?>%</b><?php endif; ?></p>
  </div>
  <div class="stat">
    <p class="stat__label">이번 주 방문자</p>
    <p class="stat__value"><?= number_format($week['uv']) ?><small>명</small></p>
    <p class="stat__helper">지난주 <?= number_format($lastWeek['uv']) ?>명<?php if (($dl = $delta($week['uv'], $lastWeek['uv'])) !== null): ?> · <b class="<?= $dl < 0 ? 'down' : '' ?>"><?= $dl >= 0 ? '+' : '' ?><?= $dl ?>%</b><?php endif; ?> · 이번 달 <?= number_format($month['uv']) ?>명</p>
  </div>
  <div class="stat">
    <p class="stat__label">미처리 문의</p>
    <p class="stat__value"><?= number_format($pending) ?><small>건</small></p>
    <p class="stat__helper">오늘 접수 <b><?= number_format($todayInq) ?>건</b> · 누적 <?= number_format($totalInq) ?>건</p>
  </div>
  <div class="stat">
    <p class="stat__label">발행 아티클</p>
    <p class="stat__value"><?= number_format($published) ?><small>편</small></p>
    <p class="stat__helper">임시저장 <?= number_format($drafts) ?>편 · <a href="insight_edit.php" class="card__link">새 아티클 →</a></p>
  </div>
</section>

<section class="grid-7-5">
  <div class="card">
    <div class="card__head">
      <div><p class="micro">Traffic</p><h2>방문자 추이</h2></div>
      <div class="tabs" role="tablist" aria-label="기간">
        <button type="button" role="tab" aria-selected="true" data-range="daily">일간</button>
        <button type="button" role="tab" aria-selected="false" data-range="weekly">주간</button>
        <button type="button" role="tab" aria-selected="false" data-range="monthly">월간</button>
      </div>
    </div>
    <div class="card__body">
      <div class="legend" style="margin-bottom:14px"><span><i></i>방문자</span><span><i class="ink"></i>페이지 조회</span></div>
      <div class="chart"><canvas id="visitChart" aria-label="방문자 추이 그래프" role="img"></canvas></div>
    </div>
    <div class="card__body card__body--flush" style="border-top:1px solid var(--line); max-height:280px; overflow:auto">
      <table class="table table--compact" id="visitTable">
        <thead><tr><th>기간</th><th class="num">방문자</th><th class="num">페이지 조회</th><th class="num">1인당 조회</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div style="display:grid; gap:24px; align-content:start">
    <div class="card">
      <div class="card__head"><div><p class="micro">Inbound</p><h2>최근 문의</h2></div><a href="inquiries.php" class="card__link">전체 보기 →</a></div>
      <div class="table-wrap"><table class="table table--compact">
        <thead><tr><th>회사 · 담당자</th><th>서비스</th><th>상태</th><th>접수</th></tr></thead>
        <tbody>
        <?php if (!$recentInq): ?><tr><td colspan="4" class="empty">아직 접수된 문의가 없습니다.</td></tr><?php endif; ?>
        <?php foreach ($recentInq as $q): $st = $q['status']; ?>
          <tr>
            <td><a href="inquiries.php?open=<?= (int)$q['id'] ?>" class="row-link"><?= e($q['company']) ?></a><span class="cell-sub"><?= e($q['name']) ?></span></td>
            <td class="muted"><?= e($services[$q['service']] ?? $q['service']) ?></td>
            <td><span class="badge badge--<?= $st === 'completed' ? 'done' : ($st === 'in_progress' ? 'progress' : 'pending') ?>"><?= e($statuses[$st] ?? $st) ?></span></td>
            <td class="muted"><?= e(substr($q['created_at'], 5, 11)) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <div class="card">
      <div class="card__head"><div><p class="micro">Top pages · 30d</p><h2>많이 본 페이지</h2></div></div>
      <div class="table-wrap"><table class="table table--compact">
        <thead><tr><th>페이지</th><th class="num">조회</th><th class="num">방문자</th></tr></thead>
        <tbody>
        <?php if (!$pages): ?><tr><td colspan="3" class="empty">아직 기록이 없습니다. 사이트에 방문 기록 스크립트가 배포되면 쌓입니다.</td></tr><?php endif; ?>
        <?php foreach ($pages as $p): ?>
          <tr><td><a href="<?= e(SITE_URL . $p['page_url']) ?>" target="_blank" rel="noopener" class="row-link"><?= e($p['page_url']) ?></a></td><td class="num"><?= number_format($p['pv']) ?></td><td class="num muted"><?= number_format($p['uv']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</section>

<section class="card">
  <div class="card__head"><div><p class="micro">Content</p><h2>최근 인사이트</h2></div><a href="insights.php" class="card__link">전체 보기 →</a></div>
  <div class="table-wrap"><table class="table table--compact">
    <thead><tr><th>제목</th><th>카테고리</th><th>상태</th><th class="num">조회</th><th>발행일</th></tr></thead>
    <tbody>
    <?php foreach ($recentIns as $i): ?>
      <tr>
        <td><a href="insight_edit.php?id=<?= (int)$i['id'] ?>" class="row-link"><?= e($i['title']) ?></a></td>
        <td class="muted"><?= e($i['category']) ?></td>
        <td><span class="badge <?= $i['is_published'] ? 'badge--done' : 'badge--off' ?>"><?= $i['is_published'] ? '발행' : '임시저장' ?></span></td>
        <td class="num"><?= number_format($i['views']) ?></td>
        <td class="muted"><?= e(substr((string)$i['published_at'], 0, 10)) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
  var series = <?= json_encode($series, JSON_UNESCAPED_UNICODE) ?>;
  var canvas = document.getElementById('visitChart'), tbody = document.querySelector('#visitTable tbody');
  var chart = null;
  var css = getComputedStyle(document.documentElement);
  var cyan = css.getPropertyValue('--cyan').trim() || '#00ADBD', ink30 = css.getPropertyValue('--ink-30').trim() || '#8B95A1', line2 = css.getPropertyValue('--line-2').trim() || '#F2F5F8';
  function render(range) {
    var rows = series[range];
    var labels = rows.map(function (r) { return r.label; });
    if (window.Chart) {
      if (chart) chart.destroy();
      chart = new Chart(canvas, {
        type: 'line',
        data: { labels: labels, datasets: [
          { label: '방문자', data: rows.map(function (r) { return r.uv; }), borderColor: cyan, backgroundColor: cyan, borderWidth: 2, pointRadius: 2.5, pointHoverRadius: 4, tension: .3 },
          { label: '페이지 조회', data: rows.map(function (r) { return r.pv; }), borderColor: ink30, backgroundColor: ink30, borderWidth: 1.5, pointRadius: 0, tension: .3 }
        ] },
        options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
          plugins: { legend: { display: false }, tooltip: { backgroundColor: '#191F28', cornerRadius: 0, padding: 10, titleFont: { family: 'Pretendard' }, bodyFont: { family: 'Pretendard' } } },
          scales: { x: { grid: { display: false }, ticks: { font: { family: 'Pretendard', size: 12 }, color: ink30, maxTicksLimit: range === 'daily' ? 10 : 12 } },
                    y: { beginAtZero: true, grid: { color: line2 }, border: { display: false }, ticks: { font: { family: 'Pretendard', size: 12 }, color: ink30, precision: 0 } } } }
      });
    }
    var html = '';
    rows.slice().reverse().forEach(function (r) {
      html += '<tr><td>' + escapeHtml(r.label) + '</td><td class="num">' + fmtNum(r.uv) + '</td><td class="num">' + fmtNum(r.pv) + '</td><td class="num muted">' + (r.uv ? (r.pv / r.uv).toFixed(1) : '–') + '</td></tr>';
    });
    tbody.innerHTML = html;
  }
  document.querySelectorAll('[data-range]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('[data-range]').forEach(function (x) { x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
      render(b.dataset.range);
    });
  });
  render('daily');
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
