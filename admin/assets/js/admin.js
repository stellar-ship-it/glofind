/* Glofind Admin 공통 — CSRF 포함 fetch, 모달, 토스트, 일괄 선택, 시계, 모바일 내비 */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var csrf = ($('meta[name="csrf-token"]') || {}).content || '';

  window.api = function (url, data, opts) {
    opts = opts || {};
    var init = { method: 'POST', headers: { 'X-CSRF-Token': csrf }, credentials: 'same-origin' };
    if (data instanceof FormData) { data.append('_csrf', csrf); init.body = data; }
    else { init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(data || {}); }
    return fetch(url, init).then(function (r) {
      return r.json().catch(function () { throw new Error('서버 응답을 읽을 수 없습니다.'); }).then(function (j) {
        if (!j.ok) throw new Error(j.error || '처리에 실패했습니다.');
        return j;
      });
    }).catch(function (e) { if (!opts.silent) toast(e.message, true); throw e; });
  };

  var toastT;
  window.toast = function (msg, isError) {
    var toastEl = $('#toast');
    if (!toastEl) { alert(msg); return; }
    toastEl.textContent = msg; toastEl.classList.toggle('is-error', !!isError); toastEl.classList.add('is-on');
    clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2600);
  };

  window.openModal = function (id) { var m = $('#' + id); if (m) { m.classList.add('is-open'); document.body.style.overflow = 'hidden'; } };
  window.closeModal = function (id) { var m = $('#' + id); if (m) { m.classList.remove('is-open'); document.body.style.overflow = ''; } };
  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-close]'); if (c) { closeModal(c.getAttribute('data-close')); return; }
    if (e.target.classList.contains('modal')) { e.target.classList.remove('is-open'); document.body.style.overflow = ''; }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') $$('.modal.is-open').forEach(function (m) { m.classList.remove('is-open'); document.body.style.overflow = ''; }); });

  window.escapeHtml = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
  window.fmtNum = function (n) { return Number(n || 0).toLocaleString('ko-KR'); };

  document.addEventListener('DOMContentLoaded', function () {
  /* 일괄 선택: [data-check-all] 마스터, .row-cb 행, [data-bulk] 버튼 활성/비활성 */
  var master = $('[data-check-all]');
  function syncBulk() {
    var n = $$('.row-cb:checked').length;
    $$('[data-bulk]').forEach(function (b) { b.disabled = n === 0; });
    var lab = $('[data-bulk-count]'); if (lab) lab.textContent = n;
  }
  if (master) master.addEventListener('change', function () { $$('.row-cb').forEach(function (c) { c.checked = master.checked; }); syncBulk(); });
  document.addEventListener('change', function (e) { if (e.target.classList.contains('row-cb')) syncBulk(); });
  window.checkedIds = function () { return $$('.row-cb:checked').map(function (c) { return c.value; }); };
  syncBulk();

  /* 필터 폼 — select 변경 시 즉시 제출 */
  $$('form[data-autosubmit] select').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });

  /* 시계 · 모바일 내비 */
  var clock = $('[data-clock]');
  if (clock) { var tick = function () { var d = new Date(); clock.textContent = d.toLocaleString('ko-KR', { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }); }; tick(); setInterval(tick, 30000); }
  var burger = $('[data-burger]'), frame = $('.adm');
  if (burger && frame) burger.addEventListener('click', function () { var on = frame.classList.toggle('is-nav-open'); burger.setAttribute('aria-expanded', String(on)); });
  document.addEventListener('click', function (e) { if (frame && frame.classList.contains('is-nav-open') && !e.target.closest('.adm-side') && !e.target.closest('[data-burger]')) frame.classList.remove('is-nav-open'); });

  });
})();
