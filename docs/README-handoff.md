# 글로파인드 웹사이트 — 핸드오프 (2026-09-03 · v2)

## 폴더 구조 (2026-09-15 재편)

**규칙 하나: `www/` 안의 내용만 서버에 올린다.** 나머지는 로컬 전용.

```
www/                    ← 서버 문서 루트에 그대로 올리는 폴더 (아래 항목은 모두 www/ 안)
dev/                    로컬 전용 — config.local.php(SQLite 설정) · data/(SQLite) · router.php(개발 서버)
docs/                   로컬 전용 — 이 문서 · 카페24_배포안내.txt
vercel.json             Vercel 은 outputDirectory=www 로 www 만 서비스한다
index.html              메인
about/ · cases/ · privacy/          각 폴더의 index.html — URL 은 /about/ 처럼 확장자 없이 노출 (2026-09-14)
services/<slug>/ · insights/<slug>/   동일 구조. 링크·에셋 경로는 전부 루트 절대경로(/assets/…)
sitemap.xml · robots.txt · favicon.ico   검색 엔진·브라우저용
vercel.json             Vercel — 옛 .html 주소 301 · trailingSlash. .vercelignore 로 문서·.htaccess·admin·api 제외
admin/                  관리자(PHP) — 대시보드·인사이트·문의·설정·백업. 카페24(PHP+MySQL)에서만 동작
api/                    공개 API — inquiry-submit.php(상담 폼) · visit.php(방문 비컨) · db.php · config.local.php(Git 제외)
assets/uploads/         관리자에서 올린 이미지 (Git 제외, 서버에서만 존재)
.htaccess               카페24(Apache) 이전용 — 같은 301 규칙. 폴더째 올리면 그대로 동작
assets/css/tokens.css   디자인 토큰 — 색·타입·간격·모션. 다른 파일에서 hex/px 직접 사용 금지
assets/css/site.css     컴포넌트 (신규 시스템 페이지 전용)
assets/css/motion.css   모션·질감 레이어
assets/css/pages/     services.css · insights.css — 해당 섹션 전용
assets/js/site.js       헤더·모바일 메뉴·카운트업·폼·앵커
assets/js/motion.js     Tier 1 모션 — 의존성 없음 (리빌·단어 스태거·히어로 네트워크 canvas·자기력·상하 버튼)
assets/js/motion2.js    Tier 2 모션 — GSAP + ScrollTrigger + Lenis: 히어로 커튼(clip) · 역량 가로 스크롤 핀 · 스택 상태 · 표 와이프 · 커서 링
assets/images/v2/       힉스필드 생성 미디어 (2026-09-03) — 스틸 12장 webp · 영상 2편 mp4(1920w/1600w, h264 crf27, 무음)
CLAUDE.md               디자인 토큰 근거·금지 조항 (실측 기록)
```

## 외부 라이브러리 (2026-09-03 curl 200 확인)

| 라이브러리 | 경로 |
|---|---|
| GSAP 3.12.5 | https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js |
| ScrollTrigger | https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js |
| Lenis 1.1.13 | https://unpkg.com/lenis@1.1.13/dist/lenis.min.js |

셋 다 `<body>` 끝에서 site.js → motion.js → motion2.js 순으로 로드한다. 하나라도 404면 motion2.js 가 콘솔 경고만 남기고 빠진다.

## v2 섹션 구조 (설계서 v2 기준)

| # | 섹션 | 격자 · 모션 | 구현 위치 |
|---|---|---|---|
| 02 | 히어로 | 풀블리드 영상 100svh · sticky 커튼(clip-path) | `.hero` / motion2 `heroCurtain()` |
| 03 | 문제 | 스티키 5:7 + 104px 사진 · 스포트라이트 | `[data-spot]` / motion `spotlight()` |
| 04 | 지표 | 벤토 7:5 · 오도미터 | `[data-odo]` / site `odometers()` |
| 05 | 사례 | 좌 블리드 영상 + sticky 텍스트 | `.case-lead__media` (video data-src 지연 로드) |
| 06 | 역량 | 가로 스크롤 핀 4패널 · 1024 미만 스냅 캐러셀 | `#caps-track` / motion2 `capsPin()` |
| 07 | 왜 | 지그재그 5·1·6 + 표 행 와이프 | `.zig` / motion2 `tableWipe()` |
| 08 | 프로세스 | 스택 카드 4장 CSS sticky · `--i` 오프셋 | `.stack .step` / motion2 `stackState()` |
| 10 | FAQ | 버튼 + grid-rows 아코디언 · 스포트라이트 | `[data-accordion]` / site `accordion()` |

## 주요 수정 위치

- **색 바꾸기**: `tokens.css` 만. 와디즈 실측 근거는 `CLAUDE.md` "색 체계" 절.
- **히어로/문의 네트워크 노드·라벨**: `motion.js` → `network()` 의 `nodes` 배열 (x·y는 0~1 비율, `data-net="hero"|"light"` 두 벌).
- **히어로 영상 교체**: `assets/images/v2/hero-strategy-web.mp4` + 포스터 `hero-strategy.webp`. 모바일(767 이하)은 영상을 숨기고 포스터만 쓴다.
- **티커 문구**: `index.html` `.ticker__track` — 두 번 반복해야 끊김 없이 돈다.
- **역량 패널 사진**: `index.html` `.cap__media img` — 4:5 유지. 패널 폭은 `.cap { inline-size: 38vw }`.
- **프로세스 스택 간격**: `.stack { gap: 24svh }` 와 `.step { inset-block-start: calc(96px + var(--i) * 16px) }`.
- **지표 밴드 숫자**: `index.html` `#stats` — `data-count` 가 카운트업 목표값.

## 배포 절차

1. `?v=20260903` 캐시버스팅 값을 배포 날짜로 일괄 치환 (index.html · about.html).
2. canonical · OG url 이 `https://glo-find.com` 인지 확인 (로컬 주소 잔존 금지).
3. 정적 호스팅에 폴더째 업로드. 존재하지 않는 URL 이 HTTP 404 를 반환하는지 확인.
4. 콘솔 에러 0 · 375 가로스크롤 없음 확인 (이번 세션에서 index · about 둘 다 확인 완료).

## 승인 필요 목록 (카피 규칙 §8)

- `#stats` 지표 밴드 라벨 "Track Record" — 신규 영문 라벨 (임시).
- `#stats` 두 번째 지표 "12 개국 이상 / 운영 경험" — FAQ 문장 "12개국 이상에서 운영 경험"을 분절해 배치.
- 티커 문구 — 기존 카피(뷰티·테크·제조·식품·의료기기 / 미국·유럽·동남아·일본·중동)에서 발췌.
- about.html 지표(65+ B2B 브랜드 · 11+ 업종 · 320%+ 리드 증가율 · 82%+ 재계약률)와
  index.html 지표(65개+ 업종 · 340%+ 문의 증가)가 서로 다르다 — 원본 카피 그대로 두었으니 어느 쪽이 맞는지 확인 필요.
- primary 버튼 흰 글자(#FFF) on 시안(#00ADBD) 대비 2.6:1 — 와디즈와 같은 방식이지만 WCAG AA 미달.
  AA 가 필요하면 `--cyan-ink`(#007F8B) 면으로 내리면 4.6:1.

## v2 에서 남긴 것 (승인 필요 추가분)

- 사례 카드 2장 썸네일은 아직 기존 제품 스틸(gen/case-beauty · case-tech). 설계서의 4초 루프 영상은 크레딧을 아끼려 생성하지 않았다.
- 문제 섹션 소형 사진 3장(I8–I10)은 별도 생성 대신 역량 컷(cap-02/04/03)을 재사용했다.
- 히어로 스틸은 프롬프트와 달리 웜 톤으로 나왔다. CSS `saturate(.72)` + 스크림으로 눌렀으나, 다크 틸을 원하면 재생성(2 크레딧).
- 힉스필드 크레딧: 76.77 → 32.77 사용(스틸 12장 24 + 영상 2편 20).

## 2026-09-04 밤 — 전체 페이지 신규 시스템 이관 완료

- 모든 라이브 페이지가 `tokens.css + site.css + motion.css`(+ `assets/css/pages/services.css` · `insights.css`)로 동작한다. `main.css`·백업·프로토타입은 2026-09-14 `../글로핀드_archive_20260914/`로 이동했다.
- 신규: `cases.html`(실사례 6건 4블록 + 후기), `privacy.html`(개인정보처리방침 초안, "확인 필요" 표시), about `.history` 섹션, 폼 동의 체크박스(`.field--consent`, site.js `contactForm`이 체크박스 검증), 푸터 `.footer__legal`.
- 로고: `assets/images/logo-{light,dark}.webp`(투명, 14KB). 원본 PNG는 체커보드가 박힌 불투명 파일이었으므로 다시 쓰지 말 것. `logo-*.png`도 투명본으로 교체됨.
- 승인·확인 대기 항목은 `보완사항_체크리스트_20260904.md` 상단 "진행 현황" 참조.

## 관리자 (2026-09-14 추가)

넥스트바이오 관리자 구조(PHP 세션 로그인 · CSRF · JSON API)를 따르되, 화면은 글로파인드 토큰(라운드 0·그림자 0·1px 선·시안 포인트)으로 만들었다.

| 메뉴 | 하는 일 |
|---|---|
| 대시보드 | 오늘·이번 주 방문자, 미처리 문의, 발행 아티클 · 일간 30일/주간 12주/월간 12개월 그래프+표 · 최근 문의 · 많이 본 페이지 |
| 인사이트 | 목록(검색·필터·발행/추천 토글·일괄 삭제·전체 재발행) · 편집기(Summernote, 이미지 업로드 → webp 변환, FAQ·관련 링크·CTA) · 미리보기 |
| 문의 관리 | 상담 폼 접수 목록 · 상세 모달(상태·내부 메모) · 일괄 상태 변경 · CSV 내려받기 |
| 설정 | 알림 이메일 · 카테고리 순서 · 비밀번호 변경 · 쓰기 권한 점검 |
| 백업·복원 | DB 전체를 JSON 으로 내려받기 / 복원(복원 후 전체 재발행) |

**인사이트는 정적 생성이다.** 저장·발행 토글·삭제·설정 변경 때마다 `insights/<slug>/index.html`, `insights/index.html`, `sitemap.xml` 을 다시 쓴다. 방문자는 항상 HTML 파일을 받는다. 기존 10편은 `api/_dev/insights-seed.json` 으로 DB 에 들어가며, 생성 결과는 원본과 동일하다(섹션 id 만 `sec-N`).

### 카페24 설치 순서
1. `www/api/config.local.sample.php` → 서버의 `api/config.local.php` 로 복사해 MySQL 접속 정보, `ADMIN_ID`, `APP_SALT`, `INSTALL_KEY` 를 채운다.
2. `www/` 안의 내용을 서버 `www/` 에 올린 뒤 `https://glo-find.com/api/_dev/install.php?key=INSTALL_KEY` 를 한 번 연다 → 테이블 생성 + 아티클 10편 시드.
3. `api/_dev/` 폴더를 삭제한다.
4. `/admin/` 접속 → 최초 화면에서 관리자 비밀번호를 만든다(소스에 비밀번호 없음, DB 해시만).
5. 설정 화면의 '쓰기 권한' 이 전부 OK 인지 확인한다(`insights/`, `assets/uploads/`, `sitemap.xml` 은 PHP 가 써야 한다).
6. 문의 알림 메일은 PHP `mail()` — 카페24 기본 발송으로 나간다. 발신 주소는 `ADMIN_EMAIL`.

### Vercel 단계에서는
admin·api 가 배포에서 제외되므로 상담 폼은 전송 실패 메시지(메일 안내)를 보이고, 방문 비컨은 조용히 실패한다. 인사이트는 Git 에 든 정적 파일 그대로 서비스된다.

### 로컬 확인
`dev/config.local.php` 에 `define('DB_DRIVER','sqlite');` 가 있으면 `dev/data/glofind.sqlite` 로 동작한다(www 밖이라 업로드되지 않는다). 설치는 `php www/api/_dev/install.php`, 미리보기 서버는 `php -S localhost:8765 -t www dev/router.php`.
