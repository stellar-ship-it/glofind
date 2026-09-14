# 글로핀드 인사이트 아티클 10편 — 반영 안내

## 1. 파일 구성
- `insights/*.html` — 아티클 상세 10개 + 갱신된 목록 페이지(index.html)
- `assets/images/insights/*.webp` — 썸네일 10장 + 본문 이미지 20장 (1600x900, 총 약 1.3MB)
- `sitemap-insights-부분.xml` — 기존 sitemap.xml 에 합칠 <url> 블록

## 2. 반영 방법
1. `insights/` 와 `assets/images/insights/` 를 사이트 루트에 그대로 덮어씁니다.
   (기존 `insights/index.html` 은 `index.backup.html` 로 자동 백업됩니다)
2. `sitemap.xml` 에 위 XML 의 `<url>` 항목을 추가합니다.
3. Google Search Console 에서 sitemap 재제출 후 대표 아티클 3개를 색인 요청합니다.

## 3. 아티클 목록
| # | 발행일 | 카테고리 | 제목 | 주요 키워드 | URL |
|---|---|---|---|---|---|
| 1 | 2026-08-18 | SEO · AI 검색 | 2026 GEO 완전 가이드: ChatGPT가 브랜드를 추천하게 만드는 7가지 전략 | GEO 최적화 | /insights/geo-complete-guide-2026.html |
| 2 | 2026-08-11 | SEO · AI 검색 | AEO 실전 가이드: 구조화 데이터와 답변형 콘텐츠로 '답'에 실리는 법 | AEO | /insights/aeo-llms-txt-schema.html |
| 3 | 2026-08-04 | 퍼포먼스 광고 | 클릭은 나오는데 문의가 없는 이유 — B2B 해외 광고 타겟·퍼널 진단 | B2B 해외 광고 | /insights/b2b-ads-lead-quality.html |
| 4 | 2026-07-28 | 해외 진출 전략 | 해외 시장 선정 5단계 프레임워크 — 감이 아니라 점수로 정하는 법 | 해외 시장 선정 | /insights/market-selection-framework.html |
| 5 | 2026-07-21 | 해외 진출 전략 | B2B 해외 바이어 ICP·페르소나 정의법 — 예산이 새기 전에 할 일 | B2B ICP | /insights/b2b-buyer-icp.html |
| 6 | 2026-07-14 | 퍼포먼스 광고 | Meta·Google·LinkedIn 채널별 특성과 예산 배분 — 어디에 얼마를 넣을까 | B2B 광고 예산 배분 | /insights/ad-channel-cpl-budget.html |
| 7 | 2026-07-07 | 브랜드 미디어 | B2B 글로벌 SNS 채널 선택 가이드 — 다 열지 말고 하나부터 | B2B 글로벌 SNS | /insights/global-sns-channel-guide.html |
| 8 | 2026-06-30 | 브랜드 미디어 | 번역과 현지화는 다르다 — 국·영문 투트랙 운영법 | 콘텐츠 현지화 | /insights/localization-vs-translation.html |
| 9 | 2026-06-23 | 인플루언서 | 동남아 인플루언서 캠페인 실행 가이드 — 선정·협상·계약·측정 | 동남아 인플루언서 마케팅 | /insights/sea-influencer-campaign.html |
| 10 | 2026-06-16 | 인플루언서 | B2B LinkedIn KOL 선별 5가지 기준 — 팔로워 수로 고르지 않는 법 | B2B 인플루언서 마케팅 | /insights/linkedin-kol-selection.html |

## 4. 적용된 GEO / SEO / AEO 구조
| 구분 | 적용 내용 |
|---|---|
| AEO | H1 직후 '한 줄 답변' 박스, 모든 H2를 질문형으로, 각 섹션 첫 문단 직답, FAQ 5~6개 |
| GEO | 인용 단위(표·단계·체크리스트·정의문) 다량 배치, '핵심 요약' 5문장 단독 완결형, 브랜드 엔티티 일관 표기 |
| SEO | title 60자 이내, meta description 80~155자, canonical, OG/Twitter, 목차 앵커, 서비스 페이지 및 아티클 간 상호 내부링크 |
| 구조화 데이터 | Organization · WebSite · Article · BreadcrumbList · FAQPage (@graph 통합), 목록 페이지에 CollectionPage · ItemList |
| 접근성·성능 | 모든 img alt, hero는 fetchpriority=high, 본문 이미지 lazy-load, webp 1600x900, 표 caption·scope 지정 |

## 5. 사실 확인 사항 (원고 작성 시 확인한 내용) 🔎
- Google 은 2026년 5월 7일부터 FAQ 리치 결과 노출을 중단했습니다. → 9·10번 아티클에 반영
  출처: https://www.searchenginejournal.com/google-drops-faq-rich-results-from-search/574429/
- Google 은 2026년 5월 AI 기능 최적화 안내에서 llms.txt 가 AI 개요·AI 모드에 필요하지 않다고 밝혔습니다.
  출처: https://www.getpassionfruit.com/blog/should-i-create-an-llms.txt-file-google-s-2026-guidance-explained
- 위 두 건은 현재 `services/seo-geo-aeo.html` 의 서술(“AEO: FAQ 스키마 · Featured Snippet”, “llms.txt 는 GEO 최적화의 핵심 기반 요소”)과 어긋납니다. 서비스 페이지 문구 수정 검토가 필요합니다.

## 6. 원고에 통계를 쓰지 않은 이유
검증 가능한 출처가 없는 수치(업계 평균 CTR·CPL, 인플루언서 단가, 시장 규모 등)는 일절 쓰지 않았습니다.
AI 검색에서 잘못된 수치가 인용되면 정정이 어렵고, 브랜드 신뢰도에 직접 타격이 되기 때문입니다.
숫자가 필요한 자리는 (a) 글로핀드가 자사 사이트에서 이미 밝힌 값 또는 (b) 범위 추정임을 문장에 명시한 표현으로 대체했습니다.
