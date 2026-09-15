<?php
require_once __DIR__ . '/../config.php';
require_admin_auth();
require_once __DIR__ . '/../lib/publisher.php';
$db = get_db();

if (($_GET['action'] ?? '') === 'export') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['_csrf'] ?? '')) json_out(['ok' => false, 'error' => '보안 토큰 오류'], 403);
    $settings = [];
    foreach ($db->all('SELECT skey, svalue FROM settings') as $s) if ($s['skey'] !== 'admin_password_hash') $settings[$s['skey']] = $s['svalue'];
    $out = ['app' => 'glofind-admin', 'version' => 1, 'exported_at' => date('c'), 'site' => SITE_URL,
            'insights' => $db->all('SELECT * FROM insights ORDER BY id'), 'inquiries' => $db->all('SELECT * FROM inquiries ORDER BY id'),
            'settings' => $settings, 'visitor_stats' => empty($_GET['skip_visits']) ? $db->all('SELECT * FROM visitor_stats ORDER BY id') : []];
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="glofind-backup-' . date('Ymd-Hi') . '.json"');
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

verify_csrf();
if (($_POST['action'] ?? '') !== 'import' || empty($_FILES['backup']['tmp_name'])) json_out(['ok' => false, 'error' => '백업 파일이 없습니다.'], 422);
$data = json_decode(file_get_contents($_FILES['backup']['tmp_name']), true);
if (!is_array($data) || ($data['app'] ?? '') !== 'glofind-admin') json_out(['ok' => false, 'error' => '글로파인드 관리자 백업 파일이 아닙니다.'], 422);

$pdo = $db->pdo();
try {
    $pdo->beginTransaction();
    foreach (['insights', 'inquiries', 'visitor_stats'] as $t) {
        if (!isset($data[$t]) || !is_array($data[$t])) continue;
        if ($t === 'visitor_stats' && !$data[$t]) continue;   // 방문 기록 제외 백업이면 현재 기록 유지
        $pdo->exec("DELETE FROM $t");
        foreach ($data[$t] as $row) { unset($row['id']); if ($row) $db->insert($t, $row); }
    }
    foreach ((array)($data['settings'] ?? []) as $k => $v) if ($k !== 'admin_password_hash' && preg_match('/^[a-z_]+$/', $k)) $db->setSetting($k, (string)$v);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[backup] import: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => '복원 실패: ' . $e->getMessage()], 500);
}
$written = publish_all($db);
json_out(['ok' => true, 'insights' => $db->count('insights'), 'inquiries' => $db->count('inquiries'), 'written' => count($written)]);
