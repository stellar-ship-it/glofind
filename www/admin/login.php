<?php
/* 로그인 — 비밀번호 해시는 DB settings(admin_password_hash)에만 둔다.
   해시가 없으면(최초 설치) 이 화면에서 비밀번호를 만든다. 소스에 폴백 계정을 두지 않는다. */
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['admin_authenticated'])) { header('Location: dashboard.php'); exit; }

$error = ''; $notice = ''; $dbError = ''; $hash = null;
try {
    $db = get_db();
    $hash = $db->setting('admin_password_hash');
} catch (Throwable $e) {
    $dbError = '데이터베이스에 연결할 수 없습니다. api/config.local.php 를 확인하고 api/_dev/install.php 를 실행하세요.';
}
$setupMode = !$dbError && empty($hash);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dbError) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_csrf'] ?? '')) {
        $error = '잘못된 요청입니다. 새로고침 후 다시 시도하세요.';
    } elseif ($setupMode) {
        $pw = $_POST['password'] ?? ''; $pw2 = $_POST['password2'] ?? '';
        if (strlen($pw) < 8) $error = '비밀번호는 8자 이상이어야 합니다.';
        elseif ($pw !== $pw2) $error = '비밀번호가 서로 다릅니다.';
        else {
            $db->setSetting('admin_password_hash', password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]));
            $notice = '비밀번호를 설정했습니다. 로그인하세요.';
            $setupMode = false;
        }
    } else {
        $lockUntil = $_SESSION['login_lockout_until'] ?? 0;
        if (time() < $lockUntil) {
            $error = '로그인 시도 횟수 초과. ' . ceil(($lockUntil - time()) / 60) . '분 후 다시 시도하세요.';
        } else {
            $id = trim($_POST['id'] ?? ''); $pw = $_POST['password'] ?? '';
            if (hash_equals(ADMIN_ID, $id) && password_verify($pw, $hash)) {
                session_regenerate_id(true);
                $_SESSION['admin_authenticated'] = true;
                $_SESSION['admin_last_activity'] = time();
                $_SESSION['login_attempts'] = 0;
                unset($_SESSION['login_lockout_until']);
                header('Location: dashboard.php'); exit;
            }
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
                $_SESSION['login_lockout_until'] = time() + LOGIN_LOCKOUT_SECONDS;
                $_SESSION['login_attempts'] = 0;
                $error = '로그인 시도 횟수 초과. 15분 후 다시 시도하세요.';
            } else {
                $error = '아이디 또는 비밀번호가 올바르지 않습니다. (남은 시도 ' . (LOGIN_MAX_ATTEMPTS - $_SESSION['login_attempts']) . '회)';
            }
        }
    }
}
if (isset($_GET['timeout'])) $notice = '세션이 만료되었습니다. 다시 로그인하세요.';
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>로그인 — Glofind Admin</title>
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/admin.css?v=20260914c">
</head>
<body class="login-body">
<main class="login">
  <div class="login__brand">
    <img src="/assets/images/logo-dark.webp" alt="Glofind" width="131" height="30">
    <p class="micro">Admin Console</p>
  </div>
  <div class="login__card">
    <h1><?= $setupMode ? '관리자 비밀번호 설정' : '관리자 로그인' ?></h1>
    <p class="login__sub"><?= $setupMode ? '최초 접속입니다. 사용할 비밀번호를 만들어주세요.' : '관리자 계정으로 로그인하세요.' ?></p>

    <?php if ($dbError): ?><div class="alert alert--error"><?= e($dbError) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="alert alert--info"><?= e($notice) ?></div><?php endif; ?>

    <?php if (!$dbError): ?>
    <form method="post" class="login__form" autocomplete="on">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <?php if ($setupMode): ?>
        <div class="field"><label for="password">새 비밀번호</label><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" autofocus></div>
        <div class="field"><label for="password2">비밀번호 확인</label><input type="password" id="password2" name="password2" required minlength="8" autocomplete="new-password"></div>
        <button type="submit" class="btn btn--solid btn--full">비밀번호 설정</button>
      <?php else: ?>
        <div class="field"><label for="id">아이디</label><input type="text" id="id" name="id" required autocomplete="username" autofocus></div>
        <div class="field"><label for="password">비밀번호</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
        <button type="submit" class="btn btn--solid btn--full">로그인</button>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </div>
  <p class="login__foot">© Glofind · <a href="/">glofind.co</a></p>
</main>
</body>
</html>
