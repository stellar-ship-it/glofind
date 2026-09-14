<?php
require_once __DIR__ . '/config.php';
require_admin_auth();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$a = $id ? $db->one('SELECT * FROM insights WHERE id = :id', [':id' => $id]) : null;
if ($id && !$a) { header('Location: insights.php'); exit; }
$isEdit = (bool)$a;
$cats = insight_categories($db);
$summary = $a ? implode("\n", json_decode($a['summary_json'] ?: '[]', true) ?: []) : '';
$faq = $a ? (json_decode($a['faq_json'] ?: '[]', true) ?: []) : [];
$links = $a ? (json_decode($a['links_json'] ?: '[]', true) ?: []) : [['title' => '', 'url' => '/services/']];
$v = fn($k, $d = '') => e($a[$k] ?? $d);

$pageTitle = $isEdit ? '아티클 수정' : '새 아티클'; $activeMenu = 'insights';
$pageLead = $isEdit ? '/insights/' . $a['slug'] . '/' : '저장하면 바로 공개 페이지가 생성됩니다. 임시저장은 발행 토글을 끄세요.';
ob_start();
?>
<form id="insightForm" onsubmit="return false" style="display:contents">
<div class="page-actions">
  <div><h2><?= $isEdit ? e($a['title']) : '새 아티클 작성' ?></h2><p class="helper">대표 이미지 1600×900 권장 · 본문은 H2 단위로 섹션이 나뉘고 목차가 자동 생성됩니다.</p></div>
  <div class="btn-group">
    <a href="insights.php" class="btn btn--ghost">목록</a>
    <?php if ($isEdit): ?><a href="preview.php?id=<?= $id ?>" target="_blank" rel="noopener" class="btn btn--ghost" id="btnPreview">미리보기</a><?php endif; ?>
    <button type="button" class="btn btn--solid" id="btnSave">저장 · 발행 반영</button>
  </div>
</div>

<div class="card">
  <div class="card__head"><div><p class="micro">01</p><h2>기본 정보</h2></div>
    <div class="btn-group">
      <label class="check"><span>발행</span><span class="toggle"><input type="checkbox" id="is_published" <?= (!$a || $a['is_published']) ? 'checked' : '' ?>><i></i></span></label>
      <label class="check" style="margin-left:12px"><span>추천(Featured)</span><span class="toggle"><input type="checkbox" id="is_featured" <?= !empty($a['is_featured']) ? 'checked' : '' ?>><i></i></span></label>
    </div>
  </div>
  <div class="card__body form-grid">
    <div class="field span-2"><label for="title">제목<i>*</i></label><input type="text" id="title" value="<?= $v('title') ?>" placeholder="예) 2026 GEO 완전 가이드: AI가 브랜드를 추천하게 만드는 7가지 전략" required></div>
    <div class="field span-2"><label for="meta_title">검색 결과 제목 (title 태그)</label><input type="text" id="meta_title" value="<?= $v('meta_title') ?>" placeholder="비우면 제목을 그대로 씁니다. 60자 안팎으로 검색 결과용 문구를 따로 쓸 때"></div>
    <div class="field"><label for="slug">URL 슬러그<i>*</i></label><input type="text" id="slug" value="<?= $v('slug') ?>" placeholder="영문 소문자-하이픈 (예: geo-complete-guide-2026)" pattern="[a-z0-9-]*"><span class="hint">공개 주소: /insights/<b id="slugPreview"><?= $v('slug', '…') ?></b>/ — 발행 후 바꾸면 기존 주소는 사라집니다.</span></div>
    <div class="field"><label for="category">카테고리<i>*</i></label><input type="text" id="category" list="catList" value="<?= $v('category', $cats[0] ?? '') ?>"><datalist id="catList"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist><span class="hint">목록에 없는 이름을 쓰면 새 카테고리가 됩니다. 순서는 설정에서.</span></div>
    <div class="field"><label for="published_at">발행일</label><input type="datetime-local" id="published_at" value="<?= e($a ? date('Y-m-d\TH:i', strtotime($a['published_at'])) : date('Y-m-d\TH:i')) ?>"></div>
    <div class="field"><label for="read_minutes">읽기 시간(분)</label><input type="number" id="read_minutes" min="1" max="60" value="<?= $v('read_minutes', 8) ?>"></div>
    <div class="field"><label for="author">작성자</label><input type="text" id="author" value="<?= $v('author', '글로파인드 전략팀') ?>"></div>
  </div>
</div>

<div class="card">
  <div class="card__head"><div><p class="micro">02</p><h2>대표 이미지</h2></div></div>
  <div class="card__body upload">
    <div class="upload__preview" id="heroPreview"><?php if (!empty($a['hero_image'])): ?><img src="<?= $v('hero_image') ?>" alt=""><?php else: ?>이미지 없음<?php endif; ?></div>
    <div style="display:grid; gap:16px">
      <div class="field"><label>이미지 파일</label><div class="btn-group"><button type="button" class="btn btn--ghost" onclick="document.getElementById('heroFile').click()">파일 선택 · 업로드</button><span class="hint" id="heroStatus">JPG · PNG 는 자동으로 WEBP 로 변환됩니다. 1600×900 권장.</span></div><input type="file" id="heroFile" accept="image/*" hidden></div>
      <div class="field"><label for="hero_image">이미지 경로</label><input type="text" id="hero_image" value="<?= $v('hero_image') ?>" placeholder="/assets/uploads/insights/…"></div>
      <div class="field"><label for="hero_alt">대체 텍스트(alt)</label><input type="text" id="hero_alt" value="<?= $v('hero_alt') ?>" placeholder="이미지가 보여주는 내용을 한 문장으로"></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card__head"><div><p class="micro">03</p><h2>요약 · 검색 메타</h2></div></div>
  <div class="card__body form-grid">
    <div class="field span-2"><label for="lead_text">한 줄 답변(리드)<i>*</i></label><textarea id="lead_text" rows="3" placeholder="제목의 질문에 두세 문장으로 바로 답합니다. 히어로 우측과 AI 검색용 abstract 에 쓰입니다."><?= $v('lead_text') ?></textarea></div>
    <div class="field span-2"><label for="excerpt">카드 요약<i>*</i></label><textarea id="excerpt" rows="3" placeholder="목록 카드와 관련 아티클에 보이는 2~3문장"><?= $v('excerpt') ?></textarea></div>
    <div class="field span-2"><label for="description">메타 설명 (검색 결과 노출)</label><textarea id="description" rows="2" placeholder="비우면 카드 요약 앞 160자를 씁니다"><?= $v('description') ?></textarea></div>
    <div class="field span-2"><label for="keywords">키워드</label><input type="text" id="keywords" value="<?= $v('keywords') ?>" placeholder="쉼표로 구분"></div>
    <div class="field span-2"><label for="summary">핵심 요약 (한 줄에 하나 · 강조는 &lt;strong&gt;)</label><textarea id="summary" rows="6" placeholder="본문 위 '핵심 요약' 박스에 들어갈 불릿 4~5개"><?= e($summary) ?></textarea></div>
  </div>
</div>

<div class="card">
  <div class="card__head"><div><p class="micro">04</p><h2>본문</h2></div><span class="hint">스타일 메뉴의 H2 로 섹션 제목을 만들면 좌측 목차가 자동으로 생깁니다. 이미지는 도구 모음의 사진 아이콘으로 업로드.</span></div>
  <div class="card__body card__body--flush"><textarea id="body_html"></textarea></div>
</div>

<div class="card">
  <div class="card__head"><div><p class="micro">05</p><h2>자주 묻는 질문</h2></div><button type="button" class="btn btn--ghost btn--sm" data-add="faq">+ 질문 추가</button></div>
  <div class="card__body repeater" id="faqList"></div>
</div>

<div class="card">
  <div class="card__head"><div><p class="micro">06</p><h2>함께 보면 좋은 페이지</h2></div><button type="button" class="btn btn--ghost btn--sm" data-add="link">+ 링크 추가</button></div>
  <div class="card__body repeater" id="linkList"></div>
</div>

<div class="card">
  <div class="card__head"><div><p class="micro">07</p><h2>본문 끝 CTA</h2></div></div>
  <div class="card__body form-grid">
    <div class="field"><label for="cta_text">문장</label><input type="text" id="cta_text" value="<?= $v('cta_text', '검색엔진과 AI, 두 채널 모두에서 발견되게 만듭니다.') ?>"></div>
    <div class="field"><label for="cta_label">버튼 문구</label><input type="text" id="cta_label" value="<?= $v('cta_label', '전략 세션 신청') ?>"></div>
  </div>
</div>
</form>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/lang/summernote-ko-KR.min.js"></script>
<style>
.note-editor.note-frame { border: 0; border-radius: 0; box-shadow: none; }
.note-editor .note-toolbar { background: var(--bg-2); border-bottom: 1px solid var(--line); border-radius: 0; padding: 8px 16px; }
.note-editor .note-editable { font-family: Pretendard, sans-serif; font-size: 16px; line-height: 1.75; padding: 24px; min-height: 480px; color: var(--ink); }
.note-editor .note-editable h2 { font-size: 24px; font-weight: 600; margin: 32px 0 12px; letter-spacing: -.02em; }
.note-editor .note-editable h3 { font-size: 18px; font-weight: 600; margin: 24px 0 8px; }
.note-editor .note-editable p.prose__lead { font-size: 18px; color: var(--ink-70); }
.note-editor .note-editable img { max-width: 100%; }
.note-editor .note-editable table { border-collapse: collapse; } .note-editor .note-editable td, .note-editor .note-editable th { border: 1px solid var(--line); padding: 6px 10px; }
.note-btn, .note-editor .note-statusbar { border-radius: 0 !important; }
.note-editor .note-statusbar { background: var(--bg-2); border-top: 1px solid var(--line); }
</style>
<script>
(function () {
  var ID = <?= (int)$id ?>;
  var body = <?= json_encode((string)($a['body_html'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  var faq = <?= json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  var links = <?= json_encode($links, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

  $('#body_html').summernote({
    lang: 'ko-KR', height: 520, styleTags: ['p', 'h2', 'h3', 'blockquote'],
    toolbar: [['style', ['style']], ['font', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol']], ['table', ['table']], ['insert', ['link', 'picture', 'hr']], ['view', ['codeview', 'fullscreen']]],
    callbacks: { onImageUpload: function (files) { for (var i = 0; i < files.length; i++) upload(files[i], function (url) { $('#body_html').summernote('insertImage', url); }); } }
  });
  $('#body_html').summernote('code', body);

  function upload(file, done, status) {
    var fd = new FormData(); fd.append('file', file);
    if (status) status.textContent = '업로드 중…';
    api('api/upload.php', fd).then(function (r) { done(r.url, r); if (status) status.textContent = (r.converted ? 'WEBP 변환 완료 · ' : '업로드 완료 · ') + r.width + '×' + r.height; })
      .catch(function () { if (status) status.textContent = '업로드 실패'; });
  }
  document.getElementById('heroFile').addEventListener('change', function () {
    if (!this.files[0]) return;
    upload(this.files[0], function (url) { document.getElementById('hero_image').value = url; document.getElementById('heroPreview').innerHTML = '<img src="' + url + '" alt="">'; }, document.getElementById('heroStatus'));
    this.value = '';
  });
  document.getElementById('hero_image').addEventListener('change', function () { document.getElementById('heroPreview').innerHTML = this.value ? '<img src="' + escapeHtml(this.value) + '" alt="">' : '이미지 없음'; });

  var slugEl = document.getElementById('slug');
  slugEl.addEventListener('input', function () { slugEl.value = slugEl.value.toLowerCase().replace(/[^a-z0-9-]+/g, '-'); document.getElementById('slugPreview').textContent = slugEl.value || '…'; });

  /* 반복 입력(FAQ · 링크) */
  function rowFaq(f) { f = f || {}; var d = document.createElement('div'); d.className = 'repeater__row'; d.innerHTML = '<div style="display:grid;gap:8px"><input type="text" class="faq-q" placeholder="질문" value="' + escapeHtml(f.q) + '"><textarea class="faq-a" placeholder="답변 (문단 2~4문장)">' + escapeHtml(f.a) + '</textarea></div><button type="button" class="btn btn--ghost btn--sm" data-remove aria-label="삭제">삭제</button>'; return d; }
  function rowLink(l) { l = l || {}; var d = document.createElement('div'); d.className = 'repeater__row repeater__row--2'; d.innerHTML = '<input type="text" class="link-t" placeholder="링크 제목" value="' + escapeHtml(l.title) + '"><input type="text" class="link-u" placeholder="/services/gtm/ 또는 https://…" value="' + escapeHtml(l.url) + '"><button type="button" class="btn btn--ghost btn--sm" data-remove aria-label="삭제">삭제</button>'; return d; }
  var faqList = document.getElementById('faqList'), linkList = document.getElementById('linkList');
  (faq.length ? faq : [{}]).forEach(function (f) { faqList.appendChild(rowFaq(f)); });
  (links.length ? links : [{}]).forEach(function (l) { linkList.appendChild(rowLink(l)); });
  document.querySelector('[data-add="faq"]').addEventListener('click', function () { faqList.appendChild(rowFaq()); });
  document.querySelector('[data-add="link"]').addEventListener('click', function () { linkList.appendChild(rowLink()); });
  document.addEventListener('click', function (e) { var b = e.target.closest('[data-remove]'); if (b) b.closest('.repeater__row').remove(); });

  var val = function (id) { return document.getElementById(id).value; };
  document.getElementById('btnSave').addEventListener('click', function () {
    var btn = this;
    var data = {
      id: ID, title: val('title'), meta_title: val('meta_title'), slug: val('slug'), category: val('category'), published_at: val('published_at'),
      read_minutes: val('read_minutes'), author: val('author'), hero_image: val('hero_image'), hero_alt: val('hero_alt'),
      lead_text: val('lead_text'), excerpt: val('excerpt'), description: val('description'), keywords: val('keywords'),
      summary: val('summary'), body_html: $('#body_html').summernote('code'),
      faq: Array.prototype.map.call(faqList.querySelectorAll('.repeater__row'), function (r) { return { q: r.querySelector('.faq-q').value, a: r.querySelector('.faq-a').value }; }),
      links: Array.prototype.map.call(linkList.querySelectorAll('.repeater__row'), function (r) { return { title: r.querySelector('.link-t').value, url: r.querySelector('.link-u').value }; }),
      cta_text: val('cta_text'), cta_label: val('cta_label'),
      is_published: document.getElementById('is_published').checked ? 1 : 0, is_featured: document.getElementById('is_featured').checked ? 1 : 0
    };
    if (!data.title.trim()) { toast('제목을 입력하세요.', true); document.getElementById('title').focus(); return; }
    if (!data.lead_text.trim() || !data.excerpt.trim()) { toast('한 줄 답변과 카드 요약은 필수입니다.', true); return; }
    btn.disabled = true; btn.textContent = '저장 중…';
    api('api/insights.php', { action: 'save', data: data }).then(function (r) {
      toast(r.published ? '저장하고 공개 페이지를 다시 생성했습니다.' : '임시저장했습니다.');
      if (!ID) location.href = 'insight_edit.php?id=' + r.id; else { ID = r.id; btn.disabled = false; btn.textContent = '저장 · 발행 반영'; }
    }).catch(function () { btn.disabled = false; btn.textContent = '저장 · 발행 반영'; });
  });
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
