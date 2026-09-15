<?php
/* 방문자 통계 집계 — 일간 30일 · 주간 12주 · 월간 12개월. DB 에서 일자별로 받아 PHP 에서 묶는다(방언 회피). */
function visitor_daily_rows(Database $db, string $from): array {
    $rows = $db->all('SELECT visit_date, COUNT(*) AS pv, COUNT(DISTINCT ip_hash) AS uv FROM visitor_stats WHERE visit_date >= :f GROUP BY visit_date ORDER BY visit_date', [':f' => $from]);
    $map = [];
    foreach ($rows as $r) $map[$r['visit_date']] = ['pv' => (int)$r['pv'], 'uv' => (int)$r['uv']];
    return $map;
}

function visitor_series(Database $db): array {
    $today = new DateTimeImmutable('today');
    $map = visitor_daily_rows($db, $today->modify('-370 days')->format('Y-m-d'));

    $daily = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = $today->modify("-$i days"); $k = $d->format('Y-m-d');
        $daily[] = ['key' => $k, 'label' => $d->format('n/j'), 'uv' => $map[$k]['uv'] ?? 0, 'pv' => $map[$k]['pv'] ?? 0];
    }
    $weekly = [];
    $monday = $today->modify('monday this week');
    for ($i = 11; $i >= 0; $i--) {
        $s = $monday->modify("-" . ($i * 7) . " days"); $e = $s->modify('+6 days'); $uv = $pv = 0;
        for ($d = $s; $d <= $e; $d = $d->modify('+1 day')) { $k = $d->format('Y-m-d'); $uv += $map[$k]['uv'] ?? 0; $pv += $map[$k]['pv'] ?? 0; }
        $weekly[] = ['key' => $s->format('Y-m-d'), 'label' => $s->format('n/j') . '~' . $e->format('n/j'), 'uv' => $uv, 'pv' => $pv];
    }
    $monthly = [];
    $first = $today->modify('first day of this month');
    for ($i = 11; $i >= 0; $i--) {
        $m = $first->modify("-$i months"); $prefix = $m->format('Y-m'); $uv = $pv = 0;
        foreach ($map as $k => $v) if (str_starts_with($k, $prefix)) { $uv += $v['uv']; $pv += $v['pv']; }
        $monthly[] = ['key' => $prefix, 'label' => $m->format('Y.m'), 'uv' => $uv, 'pv' => $pv];
    }
    return ['daily' => $daily, 'weekly' => $weekly, 'monthly' => $monthly];
}

function top_pages(Database $db, int $days = 30, int $limit = 6): array {
    $from = (new DateTimeImmutable('today'))->modify("-$days days")->format('Y-m-d');
    return $db->all("SELECT page_url, COUNT(*) AS pv, COUNT(DISTINCT ip_hash) AS uv FROM visitor_stats WHERE visit_date >= :f GROUP BY page_url ORDER BY pv DESC LIMIT $limit", [':f' => $from]);
}
