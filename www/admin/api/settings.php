<?php
require_once __DIR__ . '/../config.php';
require_admin_auth();
verify_csrf();
require_once __DIR__ . '/../lib/publisher.php';
$db = get_db(); $in = json_input(); $action = $in['action'] ?? '';
try {
    if ($action === 'save') {
        $email = trim((string)($in['notify_email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['ok' => false, 'error' => '올바른 이메일이 아닙니다.'], 422);
        $cats = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($in['categories'] ?? ''))))));
        if (!$cats) json_out(['ok' => false, 'error' => '카테고리는 최소 1개 필요합니다.'], 422);
        $db->setSetting('notify_email', $email);
        $db->setSetting('categories', implode("\n", $cats));
        $all = pub_published($db);
        pub_write('insights/index.html', pub_render_index($all, $cats));
        json_out(['ok' => true]);
    }
    if ($action === 'password') {
        $hash = $db->setting('admin_password_hash');
        if (!$hash || !password_verify((string)($in['current'] ?? ''), $hash)) json_out(['ok' => false, 'error' => '현재 비밀번호가 일치하지 않습니다.'], 422);
        $new = (string)($in['password'] ?? '');
        if (strlen($new) < 8) json_out(['ok' => false, 'error' => '새 비밀번호는 8자 이상이어야 합니다.'], 422);
        $db->setSetting('admin_password_hash', password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]));
        json_out(['ok' => true]);
    }
    json_out(['ok' => false, 'error' => '알 수 없는 요청입니다.'], 400);
} catch (Throwable $e) {
    error_log('[admin/settings] ' . $e->getMessage());
    json_out(['ok' => false, 'error' => '처리 실패: ' . $e->getMessage()], 500);
}
