<?php
/* 알림 메일 — 수신자 여러 명(쉼표), HTML + 평문 multipart. 사용자 입력은 헤더에 직접 넣지 않는다. */

function mail_recipients(string $raw, string $fallback): array {
    $list = [];
    foreach (preg_split('/[,\s;]+/', $raw) as $a) {
        $a = trim($a);
        if ($a !== '' && filter_var($a, FILTER_VALIDATE_EMAIL)) $list[] = $a;
    }
    if (!$list && filter_var($fallback, FILTER_VALIDATE_EMAIL)) $list[] = $fallback;
    return array_values(array_unique($list));
}

function mail_subject(string $s): string {
    return '=?UTF-8?B?' . base64_encode(preg_replace('/[\r\n]+/', ' ', $s)) . '?=';
}

/* 문의 1건 → [제목, 평문, HTML] */
function inquiry_mail_body(array $q, array $services, string $adminUrl): array {
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $svc = $services[$q['service']] ?? $q['service'];
    $rows = [['회사명', $q['company']], ['담당자', $q['name']], ['이메일', $q['email']], ['연락처', $q['phone'] ?: '–'],
             ['관심 서비스', $svc], ['진출 희망 지역', $q['country'] ?: '–'], ['접수', $q['created_at']]];
    $subject = "[글로파인드] 새 상담 신청 — {$q['company']} {$q['name']}";

    $text = "새 상담 신청이 접수되었습니다. (#{$q['id']})\n\n";
    foreach ($rows as [$k, $v]) $text .= "$k: $v\n";
    $text .= "\n문의 내용:\n{$q['message']}\n\n관리자에서 처리: $adminUrl\n";

    $tr = '';
    foreach ($rows as [$k, $v]) {
        $val = $k === '이메일' ? '<a href="mailto:' . $e($v) . '" style="color:#007F8B;text-decoration:none">' . $e($v) . '</a>' : $e($v);
        $tr .= '<tr><td style="padding:10px 0;border-bottom:1px solid #F2F5F8;font-size:14px;color:#6B7684;width:120px;vertical-align:top">' . $e($k) . '</td>'
             . '<td style="padding:10px 0;border-bottom:1px solid #F2F5F8;font-size:15px;color:#191F28">' . $val . '</td></tr>';
    }
    $html = '<!DOCTYPE html><html lang="ko"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $e($subject) . '</title></head>'
      . '<body style="margin:0;padding:0;background:#F2F5F8;font-family:Pretendard,-apple-system,BlinkMacSystemFont,\'Apple SD Gothic Neo\',\'Malgun Gothic\',sans-serif;word-break:keep-all">'
      . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F2F5F8;padding:32px 16px"><tr><td align="center">'
      . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FFFFFF;border:1px solid #E5E8EB">'
      . '<tr><td style="padding:28px 32px 22px;border-bottom:1px solid #E5E8EB">'
      . '<div style="font-family:Montserrat,Arial,sans-serif;font-size:12px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:#00ADBD">Glofind · New inquiry</div>'
      . '<div style="font-size:22px;font-weight:600;letter-spacing:-.02em;color:#191F28;margin-top:10px">새 상담 신청이 접수되었습니다</div>'
      . '<div style="font-size:14px;color:#6B7684;margin-top:6px">접수번호 #' . (int)$q['id'] . ' · 1영업일 내 회신 약속</div></td></tr>'
      . '<tr><td style="padding:8px 32px 4px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $tr . '</table></td></tr>'
      . '<tr><td style="padding:20px 32px 0"><div style="font-size:14px;font-weight:500;color:#6B7684;margin-bottom:8px">문의 내용</div>'
      . '<div style="padding:16px 18px;background:#FAFBFD;border:1px solid #F2F5F8;font-size:15px;line-height:1.75;color:#191F28;white-space:pre-wrap">' . $e($q['message']) . '</div></td></tr>'
      . '<tr><td style="padding:24px 32px 32px"><a href="' . $e($adminUrl) . '" style="display:inline-block;padding:12px 22px;background:#00ADBD;color:#FFFFFF;font-size:14px;font-weight:500;text-decoration:none">관리자에서 처리하기 →</a>'
      . '<div style="font-size:14px;color:#8B95A1;margin-top:14px">회신은 이 메일에 답장하면 문의자에게 바로 갑니다.</div></td></tr>'
      . '</table>'
      . '<div style="font-family:Montserrat,Arial,sans-serif;font-size:12px;letter-spacing:.08em;color:#8B95A1;margin-top:18px">GLO-FIND.COM</div>'
      . '</td></tr></table></body></html>';
    return [$subject, $text, $html];
}

/* multipart/alternative 로 발송. 성공 여부 반환 */
function send_html_mail(array $to, string $subject, string $text, string $html, string $from, string $replyTo = ''): bool {
    if (!$to) return false;
    $b = 'b' . bin2hex(random_bytes(12));
    $headers  = "From: " . $from . "\r\n";
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $headers .= "Reply-To: " . $replyTo . "\r\n";
    $headers .= "MIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$b\"\r\n";
    $body  = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text));
    $body .= "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html));
    $body .= "--$b--\r\n";
    return @mail(implode(', ', $to), mail_subject($subject), $body, $headers);
}
