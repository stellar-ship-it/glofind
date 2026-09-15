<?php
/* 상담 신청 폼 접수 — index/about/cases 의 #contact-form 이 JSON 으로 POST 한다. */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function out(bool $ok, string $msg, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, '잘못된 요청입니다.', 405);

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = $_POST;

$clean = fn($k, $max = 255) => mb_substr(trim(preg_replace('/[\r\n\t]+/', ' ', (string)($in[$k] ?? ''))), 0, $max);
$company = $clean('company');
$name    = $clean('name');
$email   = $clean('email');
$phone   = $clean('phone', 50);
$service = $clean('service', 50);
$country = $clean('country');
$message = mb_substr(trim((string)($in['message'] ?? '')), 0, 5000);
$consent = !empty($in['consent']);
$honey   = trim((string)($in['website'] ?? ''));   // honeypot — 사람은 채우지 않는 필드
$page    = $clean('page', 500);

if ($honey !== '') out(true, '문의가 접수되었습니다.');      // 봇에게는 성공처럼 응답하고 버린다
if ($company === '' || $name === '' || $message === '' || $service === '') out(false, '필수 항목을 입력해주세요.', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(false, '올바른 이메일을 입력해주세요.', 422);
if (!$consent) out(false, '개인정보 수집·이용에 동의해주세요.', 422);

$services = ['gtm' => '해외 진출 전략 수립', 'seo' => 'SEO · GEO · AEO', 'media' => '브랜드 미디어 운영',
             'ads' => '퍼포먼스 광고', 'influencer' => '인플루언서 캠페인', 'bundle' => '복합 서비스'];
if (!isset($services[$service])) out(false, '서비스를 선택해주세요.', 422);

try {
    $db = get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ipHash = Database::ipHash($ip);
    // IP 당 10분에 3건까지
    $recent = (int)$db->value('SELECT COUNT(*) FROM inquiries WHERE ip_hash = :h AND created_at >= :t',
        [':h' => $ipHash, ':t' => date('Y-m-d H:i:s', time() - 600)]);
    if ($recent >= 3) out(false, '잠시 후 다시 시도해주세요.', 429);

    $id = $db->insert('inquiries', [
        'company' => $company, 'name' => $name, 'email' => $email, 'phone' => $phone,
        'service' => $service, 'country' => $country, 'message' => $message,
        'status' => 'pending', 'source_page' => $page, 'ip_hash' => $ipHash,
        'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // 알림 메일 — 실패해도 접수는 유지
    try {
        $to = $db->setting('notify_email', ADMIN_EMAIL);
        if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $subject = '=?UTF-8?B?' . base64_encode("[글로파인드] 새 상담 신청 — {$company} {$name}") . '?=';
            $body = "새 상담 신청이 접수되었습니다. (#{$id})\n\n"
                  . "회사명: {$company}\n담당자: {$name}\n이메일: {$email}\n연락처: {$phone}\n"
                  . "관심 서비스: {$services[$service]}\n진출 희망 지역: {$country}\n\n"
                  . "문의 내용:\n{$message}\n\n관리자: " . SITE_URL . "/admin/inquiries.php\n";
            $headers = "From: " . ADMIN_EMAIL . "\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            @mail($to, $subject, $body, $headers);
        }
    } catch (Throwable $e) { error_log('[inquiry] mail: ' . $e->getMessage()); }

    out(true, '문의가 접수되었습니다.');
} catch (Throwable $e) {
    error_log('[inquiry] ' . $e->getMessage());
    out(false, '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.', 500);
}
