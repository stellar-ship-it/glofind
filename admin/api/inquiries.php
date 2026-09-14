<?php
require_once __DIR__ . '/../config.php';
require_admin_auth();
$db = get_db();

/* CSV 내려받기 — GET 이지만 CSRF 토큰을 쿼리로 검증한다 */
if (($_GET['action'] ?? '') === 'export') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['_csrf'] ?? '')) json_out(['ok' => false, 'error' => '보안 토큰 오류'], 403);
    $services = service_labels(); $statuses = status_labels();
    $where = []; $p = [];
    if (($st = trim($_GET['status'] ?? '')) !== '' && isset($statuses[$st])) { $where[] = 'status = :st'; $p[':st'] = $st; }
    if (($sv = trim($_GET['service'] ?? '')) !== '' && isset($services[$sv])) { $where[] = 'service = :sv'; $p[':sv'] = $sv; }
    if (($q = trim($_GET['q'] ?? '')) !== '') { $where[] = '(company LIKE :q1 OR name LIKE :q2 OR email LIKE :q3 OR message LIKE :q4)'; $p[':q1'] = $p[':q2'] = $p[':q3'] = $p[':q4'] = "%$q%"; }
    $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $rows = $db->all("SELECT * FROM inquiries$w ORDER BY created_at DESC", $p);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="glofind-inquiries-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', '접수일', '회사', '담당자', '이메일', '연락처', '관심 서비스', '희망 지역', '문의 내용', '상태', '처리일', '메모', '유입 페이지']);
    foreach ($rows as $r) fputcsv($out, [$r['id'], $r['created_at'], $r['company'], $r['name'], $r['email'], $r['phone'], $services[$r['service']] ?? $r['service'], $r['country'], $r['message'], $statuses[$r['status']] ?? $r['status'], $r['processed_at'], $r['admin_note'], $r['source_page']]);
    fclose($out); exit;
}

verify_csrf();
$in = json_input(); $action = $in['action'] ?? ''; $allowed = array_keys(status_labels());
try {
    if ($action === 'get') {
        $r = $db->one('SELECT * FROM inquiries WHERE id = :id', [':id' => (int)($in['id'] ?? 0)]);
        if (!$r) json_out(['ok' => false, 'error' => '문의를 찾을 수 없습니다.'], 404);
        unset($r['ip_hash']);
        json_out(['ok' => true, 'data' => $r]);
    }
    if ($action === 'update') {
        $id = (int)($in['id'] ?? 0); $st = $in['status'] ?? '';
        if (!$id || !in_array($st, $allowed, true)) json_out(['ok' => false, 'error' => '값이 올바르지 않습니다.'], 422);
        $data = ['status' => $st, 'admin_note' => mb_substr((string)($in['note'] ?? ''), 0, 5000)];
        if ($st === 'completed') $data['processed_at'] = date('Y-m-d H:i:s');
        $db->update('inquiries', $data, ['id' => $id]);
        json_out(['ok' => true]);
    }
    if ($action === 'bulk_status') {
        $ids = array_values(array_filter(array_map('intval', (array)($in['ids'] ?? [])))); $st = $in['status'] ?? '';
        if (!$ids || !in_array($st, $allowed, true)) json_out(['ok' => false, 'error' => '값이 올바르지 않습니다.'], 422);
        $data = ['status' => $st]; if ($st === 'completed') $data['processed_at'] = date('Y-m-d H:i:s');
        $db->update('inquiries', $data, ['id' => ['in' => $ids]]);
        json_out(['ok' => true]);
    }
    if ($action === 'delete') {
        $ids = array_values(array_filter(array_map('intval', (array)($in['ids'] ?? []))));
        if (!$ids) json_out(['ok' => false, 'error' => '삭제할 항목이 없습니다.'], 422);
        $db->delete('inquiries', ['id' => ['in' => $ids]]);
        json_out(['ok' => true]);
    }
    json_out(['ok' => false, 'error' => '알 수 없는 요청입니다.'], 400);
} catch (Throwable $e) {
    error_log('[admin/inquiries] ' . $e->getMessage());
    json_out(['ok' => false, 'error' => '처리 실패'], 500);
}
