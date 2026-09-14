# 글로파인드 웹사이트 — 핸드오프 (2026-09-03 · v2)

## 폴더 구조

```
index.html              메인 (신규 시스템)
about.html              About (신규 시스템)
cases.html · services/* · insights/*   구 시스템(main.css) — 승인 후 이관 예정
assets/css/tokens.css   디자인 토큰 — 색·타입·간격·모션. 다른 파일에서 hex/px 직접 사용 금지
assets/css/site.css     컴포넌트 (신규 시스템 페이지 전용)
assets/css/motion.css   모션·질감 레이어
assets/css/main.css     구 시스템 (이관 전까지 유지)
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
2. canonical · OG url 이 `https://glofind.co` 인지 확인 (로컬 주소 잔존 금지).
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

- 모든 라이브 페이지가 `tokens.css + site.css + motion.css`(+ `assets/css/pages/services.css` · `insights.css`)로 동작한다. `main.css`는 `_backup_20260723/`과 프로토타입 파일만 참조 — 삭제 가능 시점.
- 신규: `cases.html`(실사례 6건 4블록 + 후기), `privacy.html`(개인정보처리방침 초안, "확인 필요" 표시), about `.history` 섹션, 폼 동의 체크박스(`.field--consent`, site.js `contactForm`이 체크박스 검증), 푸터 `.footer__legal`.
- 로고: `assets/images/logo-{light,dark}.webp`(투명, 14KB). 원본 PNG는 체커보드가 박힌 불투명 파일이었으므로 다시 쓰지 말 것. `logo-*.png`도 투명본으로 교체됨.
- 승인·확인 대기 항목은 `보완사항_체크리스트_20260904.md` 상단 "진행 현황" 참조.
