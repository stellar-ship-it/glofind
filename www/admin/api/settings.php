<?php
require_once __DIR__ . '/../config.php';
require_admin_auth();
verify_csrf();
require_once __DIR__ . '/../lib/publisher.php';
$db = get_db(); $in = json_input(); $action = $in['action'] ?? '';
try {
    if ($action === 'save') {
        $email = trim((string)($in['notify_email'] ?? ''));
        foreach (preg_split('/[,\s;]+/', $email) as $a) {
            if ($a !== '' && !filter_var($a, FILTER_VALIDATE_EMAIL)) json_out(['ok' => false, 'error' => "올바른 이메일이 아닙니다: $a"], 422);
        }
        $email = implode(', ', array_filter(array_map('trim', preg_split('/[,\s;]+/', $email))));
        $cats = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($in['categories'] ?? ''))))));
        if (!$cats) json_out(['ok' => false, 'error' => '카테고리는 최소 1개 필요합니다.'], 422);
        $db->setSetting('notify_email', $email);
        $db->setSetting('categories', implode("\n", $cats));
        $all = pub_published($db);
        pub_write('insights/index.html', pub_render_index($all, $cats));
        json_out(['ok' => true]);
    }
    if ($action === 'test_mail') {
        require_once __DIR__ . '/../../api/mail.php';
        $to = mail_recipients((string)$db->setting('notify_email', ''), ADMIN_EMAIL);
        if (!$to) json_out(['ok' => false, 'error' => '수신 이메일이 없습니다. 먼저 저장하세요.'], 422);
        [$subj, $text, $html] = inquiry_mail_body([
            'id' => 0, 'company' => '(주)테스트기업', 'name' => '홍길동', 'email' => 'test@example.com', 'phone' => '010-0000-0000',
            'service' => 'gtm', 'country' => '미국, 동남아', 'message' => "테스트 메일입니다.\n관리자 > 설정에서 보냈습니다.", 'created_at' => date('Y-m-d H:i'),
        ], service_labels(), SITE_URL . '/admin/inquiries.php');
        $ok = send_html_mail($to, '[테스트] ' . $subj, $text, $html, ADMIN_EMAIL);
        if (!$ok) json_out(['ok' => false, 'error' => '서버가 메일 발송을 거부했습니다. 호스팅의 mail() 설정을 확인하세요.'], 500);
        json_out(['ok' => true, 'to' => $to]);
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
