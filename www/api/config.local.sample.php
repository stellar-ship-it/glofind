<?php
/* config.local.php 로 복사해서 값을 채운다. 이 파일(config.local.php)은 Git 에 올리지 않는다. */

// 카페24(MySQL)
define('DB_DRIVER', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'glofind');
define('DB_USER', 'glofind');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 로컬 개발(SQLite) — 위 mysql 블록 대신 이 줄만 두면 된다
// define('DB_DRIVER', 'sqlite');

define('ADMIN_ID', 'admin');              // 관리자 아이디. 비밀번호는 최초 접속 시 화면에서 설정한다
define('ADMIN_EMAIL', 'hello@glofind.co'); // 문의 알림 수신(기본값, 관리자 > 설정에서 변경 가능)
define('SITE_URL', 'https://glo-find.com');
define('APP_SALT', '아무 긴 무작위 문자열');   // 방문자 IP 해시용
define('INSTALL_KEY', '설치용 비밀키');       // api/_dev/install.php?key= 에 쓰고 설치 후 삭제
