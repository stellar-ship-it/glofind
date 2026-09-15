<?php
/* 관리자 공통 — 세션·인증·CSRF·이스케이프. 모든 admin 페이지와 admin/api 는 이 파일을 먼저 읽는다. */
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/db.php';
session_start();

define('ADMIN_SESSION_TIMEOUT', 3600);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 900);

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function require_admin_auth(): void {
    if (empty($_SESSION['admin_authenticated'])) {
        if (is_api_request()) json_out(['ok' => false, 'error' => '로그인이 필요합니다.'], 401);
        header('Location: login.php'); exit;
    }
    if (time() - ($_SESSION['admin_last_activity'] ?? 0) > ADMIN_SESSION_TIMEOUT) {
        session_unset(); session_destroy();
        if (is_api_request()) json_out(['ok' => false, 'error' => '세션이 만료되었습니다.'], 401);
        header('Location: login.php?timeout=1'); exit;
    }
    $_SESSION['admin_last_activity'] = time();
}

function is_api_request(): bool {
    return str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/api/');
}

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)$token)) {
        json_out(['ok' => false, 'error' => '보안 토큰이 유효하지 않습니다. 새로고침 후 다시 시도하세요.'], 403);
    }
}

function json_input(): array {
    $in = json_decode(file_get_contents('php://input'), true);
    return is_array($in) ? $in : [];
}

/* 카테고리 목록 — 설정에 줄바꿈 구분으로 저장 */
function insight_categories(Database $db): array {
    $raw = (string)$db->setting('categories', "해외 진출 전략\n브랜드 미디어\n인플루언서\n퍼포먼스 광고\nSEO · AI 검색");
    return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $raw))));
}

function service_labels(): array {
    return ['gtm' => '해외 진출 전략 수립', 'seo' => 'SEO · GEO · AEO', 'media' => '브랜드 미디어 운영',
            'ads' => '퍼포먼스 광고', 'influencer' => '인플루언서 캠페인', 'bundle' => '복합 서비스'];
}
function status_labels(): array {
    return ['pending' => '미처리', 'in_progress' => '처리중', 'completed' => '완료'];
}
